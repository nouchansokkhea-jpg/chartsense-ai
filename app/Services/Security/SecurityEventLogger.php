<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class SecurityEventLogger
{
    /**
     * Keys to redact automatically from security logs.
     */
    protected static array $redactedKeys = [
        'password',
        'current_password',
        'password_confirmation',
        'api_key',
        'token',
        'id_token',
        'bot_token',
        'secret',
        'client_secret',
        'authorization',
    ];

    /**
     * Record a high-priority security event.
     *
     * @param string $eventType e.g. 'prompt_injection_detected', 'ssrf_blocked', 'malicious_upload'
     * @param string $severity 'INFO' | 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL'
     * @param string $description
     * @param array $context
     * @param int|null $userId
     * @return AuditLog
     */
    public static function log(
        string $eventType,
        string $severity = 'HIGH',
        string $description = '',
        array $context = [],
        ?int $userId = null
    ): AuditLog {
        $safeContext = self::redactSensitiveData($context);
        $safeContext['severity'] = strtoupper($severity);
        $safeContext['description'] = $description;
        $safeContext['event_timestamp'] = now()->toIso8601String();

        // Write to structured AuditLog
        $audit = AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'admin_id' => auth()->user()?->isAdmin() ? auth()->id() : null,
            'action' => 'security.' . $eventType,
            'entity_type' => 'SecurityFortress',
            'entity_id' => $severity,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => substr((string)request()->userAgent(), 0, 255),
            'payload' => $safeContext,
            'created_at' => now(),
        ]);

        // Write to Laravel application logger
        $logMessage = "[SECURITY_FORTRESS] [{$severity}] {$eventType}: {$description}";
        if (in_array(strtoupper($severity), ['HIGH', 'CRITICAL'])) {
            Log::alert($logMessage, $safeContext);
        } else {
            Log::warning($logMessage, $safeContext);
        }

        return $audit;
    }

    /**
     * Recursively scrub sensitive credentials from payloads.
     */
    protected static function redactSensitiveData(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string)$key);
            $shouldRedact = false;
            foreach (self::$redactedKeys as $rk) {
                if (str_contains($lowerKey, $rk)) {
                    $shouldRedact = true;
                    break;
                }
            }

            if ($shouldRedact) {
                $sanitized[$key] = '[REDACTED_SECRET]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::redactSensitiveData($value);
            } elseif (is_string($value) && strlen($value) > 500) {
                $sanitized[$key] = substr($value, 0, 500) . '... [TRUNCATED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
