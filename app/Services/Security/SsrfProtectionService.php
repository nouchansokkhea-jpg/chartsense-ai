<?php

namespace App\Services\Security;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SsrfProtectionService
{
    /**
     * Forbidden CIDR ranges (Loopback, Private RFC1918, Link-Local/Cloud Metadata RFC3927).
     */
    protected static array $blockedCidrs = [
        '0.0.0.0/8',          // Current network
        '10.0.0.0/8',         // Private-use networks (RFC 1918)
        '100.64.0.0/10',      // Shared Address Space / Carrier-grade NAT
        '127.0.0.0/8',        // Loopback addresses
        '169.254.0.0/16',     // Link-local / Cloud Metadata (169.254.169.254)
        '172.16.0.0/12',      // Private-use networks (RFC 1918)
        '192.0.0.0/24',       // IETF Protocol Assignments
        '192.0.2.0/24',       // TEST-NET-1
        '192.168.0.0/16',     // Private-use networks (RFC 1918)
        '198.18.0.0/15',      // Network benchmark tests
        '198.51.100.0/24',    // TEST-NET-2
        '203.0.113.0/24',     // TEST-NET-3
        '224.0.0.0/4',        // Multicast
        '240.0.0.0/4',        // Reserved for future use
        '255.255.255.255/32', // Limited broadcast
    ];

    /**
     * Validate a target destination URL against SSRF vulnerabilities.
     *
     * @param string $url
     * @return string The validated URL
     * @throws Exception
     */
    public static function validateUrl(string $url): string
    {
        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            throw new Exception("SSRF Guard: Malformed or unparseable target URL [{$url}].");
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new Exception("SSRF Guard: Prohibited scheme [{$scheme}]. Only HTTP and HTTPS are permitted.");
        }

        $host = strtolower($parts['host']);

        // Block literal loopback hostnames
        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw new Exception("SSRF Guard: Access to internal or loopback hostname [{$host}] is prohibited.");
        }

        // Resolve DNS to verify all destination IP addresses
        $ips = gethostbynamel($host);
        if ($ips === false || empty($ips)) {
            // Check if host itself is a literal IP address
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                $ips = [$host];
            } else {
                throw new Exception("SSRF Guard: Unable to resolve hostname [{$host}] via DNS.");
            }
        }

        foreach ($ips as $ip) {
            if (self::isBlockedIp($ip)) {
                \App\Services\Security\SecurityEventLogger::log(
                    'ssrf_attempt_blocked',
                    'CRITICAL',
                    "Prohibited internal destination or cloud metadata reached: [{$host}] ({$ip})",
                    ['host' => $host, 'ip' => $ip, 'target_url' => $url]
                );
                throw new Exception("SSRF Guard: Target host [{$host}] resolves to prohibited internal/metadata IP address [{$ip}]. Request blocked.");
            }
        }

        return $url;
    }

    /**
     * Determine if an IP address belongs to blocked internal, private, or metadata subnets.
     */
    public static function isBlockedIp(string $ip): bool
    {
        // IPv6 Loopback & Link-local checks
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($ip === '::1' || str_starts_with(strtolower($ip), 'fe80:') || str_starts_with(strtolower($ip), 'fc00:')) {
                return true;
            }
            return false;
        }

        $ipLong = ip2long($ip);
        if ($ipLong === false) {
            return true; // Reject unparseable IP
        }

        foreach (self::$blockedCidrs as $cidr) {
            [$subnet, $maskBits] = explode('/', $cidr);
            $subnetLong = ip2long($subnet);
            $mask = ~((1 << (32 - (int)$maskBits)) - 1);

            if (($ipLong & $mask) === ($subnetLong & $mask)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch an SSRF-protected, TLS-verified HTTP GET request.
     */
    public static function safeGet(string $url, array $headers = [], int $timeout = 8): Response
    {
        $safeUrl = self::validateUrl($url);

        return Http::timeout($timeout)
            ->withHeaders($headers)
            ->withOptions([
                'verify' => true, // Enforce TLS certificate verification
                'allow_redirects' => [
                    'max' => 3,
                    'strict' => true,
                    'referer' => false,
                    'protocols' => ['https', 'http'],
                ],
            ])
            ->get($safeUrl);
    }

    /**
     * Dispatch an SSRF-protected, TLS-verified HTTP POST request.
     */
    public static function safePost(string $url, array $data = [], array $headers = [], int $timeout = 8): Response
    {
        $safeUrl = self::validateUrl($url);

        return Http::timeout($timeout)
            ->withHeaders($headers)
            ->withOptions([
                'verify' => true,
            ])
            ->post($safeUrl, $data);
    }
}
