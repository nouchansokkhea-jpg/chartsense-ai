<?php

namespace App\Services\Security;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecureUploadService
{
    /**
     * Allowed image MIME types and binary magic signatures.
     */
    protected static array $magicSignatures = [
        'png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'jpg' => ["\xFF\xD8\xFF"],
        'jpeg' => ["\xFF\xD8\xFF"],
        'webp' => ["RIFF"], // Followed by WEBP at offset 8
    ];

    /**
     * Maximum permissible image width and height in pixels (prevents pixel flood / decompression bombs).
     */
    protected static int $maxWidth = 4096;
    protected static int $maxHeight = 4096;

    /**
     * Inspect, sanitize, and securely store an uploaded chart screenshot.
     *
     * @param UploadedFile $file
     * @param string $directory Storage folder relative to storage/app
     * @return array{stored_path: string, absolute_path: string, original_name: string, mime_type: string, size_bytes: int}
     * @throws Exception
     */
    public static function processAndStoreScreenshot(UploadedFile $file, string $directory = 'scans/screenshots'): array
    {
        if (!$file->isValid()) {
            throw new Exception('Uploaded file is corrupted or failed during upload transfer.');
        }

        $realPath = $file->getRealPath();
        if (!$realPath || !file_exists($realPath)) {
            throw new Exception('File path is invalid or unreadable.');
        }

        // 1. Verify binary magic bytes using Fileinfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_file($finfo, $realPath);
        finfo_close($finfo);

        $allowedMimes = ['image/png', 'image/jpeg', 'image/webp'];
        if (!in_array($detectedMime, $allowedMimes, true)) {
            throw new Exception("Rejected file: detected MIME type [{$detectedMime}] is not an authorized image format.");
        }

        // 2. Validate file header magic bytes directly
        $handle = fopen($realPath, 'rb');
        $header = fread($handle, 16);
        fclose($handle);

        $validSignature = false;
        if (str_starts_with($header, "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A")) {
            $validSignature = true;
            $extension = 'png';
        } elseif (str_starts_with($header, "\xFF\xD8\xFF")) {
            $validSignature = true;
            $extension = 'jpg';
        } elseif (str_starts_with($header, "RIFF") && substr($header, 8, 4) === "WEBP") {
            $validSignature = true;
            $extension = 'webp';
        }

        if (!$validSignature) {
            throw new Exception('Rejected file: file header binary signature does not match legitimate PNG, JPG, or WebP image data.');
        }

        // 3. Inspect image dimensions to prevent decompression bombs (pixel flood attack)
        $dimensions = @getimagesize($realPath);
        if ($dimensions === false) {
            throw new Exception('Rejected file: cannot decode image dimensions. File may be malformed or corrupt.');
        }

        [$width, $height] = $dimensions;
        if ($width <= 0 || $height <= 0) {
            throw new Exception('Rejected file: image dimensions are invalid.');
        }

        if ($width > self::$maxWidth || $height > self::$maxHeight) {
            throw new Exception("Rejected file: image dimensions ({$width}x{$height}px) exceed the maximum security threshold of " . self::$maxWidth . "x" . self::$maxHeight . "px.");
        }

        // 4. Sanitize original filename (strip path traversal characters and control symbols)
        $clientName = $file->getClientOriginalName();
        $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', basename($clientName));

        // 5. Generate cryptographically randomized storage filename outside web root
        $randomName = Str::random(40) . '.' . $extension;
        $storedPath = $file->storeAs($directory, $randomName, 'local');

        $absolutePath = Storage::disk('local')->path($storedPath);

        // 6. Strip EXIF metadata / Re-encode if GD is enabled
        if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
            try {
                $rawContent = file_get_contents($absolutePath);
                $gdImg = @imagecreatefromstring($rawContent);
                if ($gdImg !== false) {
                    // Re-save without metadata
                    if ($extension === 'png') {
                        imagepng($gdImg, $absolutePath, 6);
                    } elseif ($extension === 'jpg') {
                        imagejpeg($gdImg, $absolutePath, 90);
                    } elseif ($extension === 'webp') {
                        imagewebp($gdImg, $absolutePath, 90);
                    }
                    imagedestroy($gdImg);
                }
            } catch (\Throwable $e) {
                // If GD re-save fails, keep original validated image
            }
        }

        return [
            'stored_path' => $storedPath,
            'absolute_path' => $absolutePath,
            'original_name' => $safeOriginalName,
            'mime_type' => $detectedMime,
            'size_bytes' => filesize($absolutePath),
        ];
    }
}
