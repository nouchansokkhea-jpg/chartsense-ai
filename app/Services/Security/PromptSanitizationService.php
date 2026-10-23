<?php

namespace App\Services\Security;

class PromptSanitizationService
{
    /**
     * Forbidden prompt injection tokens and patterns.
     */
    protected static array $injectionTokens = [
        '/\[(?:system(?:\s+instruction)?|system_instruction|sys|inst|\/inst)\]/i',
        '/<<(?:sys|\/sys)>>/i',
        '/<\|(?:im_start|im_end|system|user|assistant)\|>/i',
        '/ignore\s+(?:all\s+)?(?:previous|prior|above)\s+(?:instructions|rules|prompts|commands)/i',
        '/disregard\s+(?:all\s+)?(?:previous|prior|above)\s+(?:instructions|rules|prompts)/i',
        '/override\s+(?:system|developer|security)\s+(?:rules|prompt|mode|instructions)/i',
        '/(?:you\s+are\s+now|act\s+as)\s+(?:a\s+system|dan|jailbreak|root|admin)/i',
        '/bypass\s+(?:security|safety|filter|guardrails)/i',
    ];

    /**
     * Sanitize user-provided text (e.g. notes, commentary) before embedding in LLM prompts.
     */
    public static function sanitizeUserInput(?string $text, int $maxLength = 1000): string
    {
        if (empty($text)) {
            return '';
        }

        // 1. Strip non-printable and zero-width characters (homoglyphs / invisible tokens)
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);

        // 2. Neutralize known prompt injection override patterns
        $injectionDetected = false;
        foreach (self::$injectionTokens as $pattern) {
            if (preg_match($pattern, $clean)) {
                $injectionDetected = true;
                $clean = preg_replace($pattern, '[FILTERED_TOKEN]', $clean);
            }
        }

        if ($injectionDetected) {
            \App\Services\Security\SecurityEventLogger::log(
                'prompt_injection_detected',
                'HIGH',
                'Neutralized hostile prompt injection payload in user commentary notes',
                ['sample' => substr($text, 0, 80)]
            );
        }

        // 3. Truncate to maximum allowed input length
        if (mb_strlen($clean) > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength) . '... [TRUNCATED]';
        }

        $clean = trim($clean);
        if (empty($clean)) {
            return '';
        }

        // 4. Enclose in strict Zero-Trust XML boundary
        return "<untrusted_user_notes data_source=\"user_input\" security_status=\"UNTRUSTED_CONTENT\">\n"
            . htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . "\n</untrusted_user_notes>";
    }

    /**
     * Sanitize OCR text extracted from chart screenshots.
     */
    public static function sanitizeOcrText(?string $text, int $maxLength = 4000): string
    {
        if (empty($text)) {
            return '';
        }

        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', '', $text);

        foreach (self::$injectionTokens as $pattern) {
            $clean = preg_replace($pattern, '[FILTERED_OCR_TOKEN]', $clean);
        }

        if (mb_strlen($clean) > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength) . '... [OCR_TRUNCATED]';
        }

        $clean = trim($clean);
        if (empty($clean)) {
            return '';
        }

        return "<untrusted_chart_ocr data_source=\"screenshot_ocr\" security_status=\"UNTRUSTED_CONTENT\">\n"
            . htmlspecialchars($clean, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . "\n</untrusted_chart_ocr>";
    }

    /**
     * Sanitize external financial news headlines and summaries.
     */
    public static function sanitizeNewsText(?string $text, int $maxLength = 500): string
    {
        if (empty($text)) {
            return '';
        }

        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);

        foreach (self::$injectionTokens as $pattern) {
            $clean = preg_replace($pattern, '[FILTERED_NEWS_TOKEN]', $clean);
        }

        if (mb_strlen($clean) > $maxLength) {
            $clean = mb_substr($clean, 0, $maxLength);
        }

        return trim(strip_tags($clean));
    }

    /**
     * Strict schema and range validation for LLM structured output.
     * Prevents arbitrary code execution, XSS, or malformed numerical ranges.
     */
    public static function validateAndSanitizeOutput(array $plan): array
    {
        // 1. Sanitize direction (strictly whitelist)
        $direction = strtoupper(trim((string)($plan['direction'] ?? 'WAIT')));
        if (!in_array($direction, ['BUY', 'SELL', 'WAIT', 'BULLISH', 'BEARISH', 'NEUTRAL'])) {
            $direction = 'WAIT';
        }
        $plan['direction'] = $direction;

        // 2. Sanitize confidence
        $confidence = strtolower(trim((string)($plan['confidence'] ?? 'moderate')));
        if (!in_array($confidence, ['low', 'moderate', 'high'])) {
            $confidence = 'moderate';
        }
        $plan['confidence'] = $confidence;

        // 3. Cast numeric trade levels to safe floats
        $numericFields = [
            'entry_target', 'entry_min', 'entry_max',
            'stop_loss', 'take_profit_1', 'take_profit_2', 'take_profit_3',
            'risk_reward_ratio', 'risk_distance', 'reward_distance_tp1',
        ];

        foreach ($numericFields as $field) {
            if (isset($plan[$field]) && is_numeric($plan[$field])) {
                $plan[$field] = (float) $plan[$field];
            } else {
                $plan[$field] = null;
            }
        }

        // 4. Sanitize strings and explanations against XSS
        if (isset($plan['technical_explanation']) && is_string($plan['technical_explanation'])) {
            $plan['technical_explanation'] = strip_tags($plan['technical_explanation']);
        }

        // Sanitize string arrays
        $arrayFields = ['reasoning', 'confirmation_conditions', 'invalidation_conditions', 'warnings', 'support_levels', 'resistance_levels'];
        foreach ($arrayFields as $af) {
            if (isset($plan[$af]) && is_array($plan[$af])) {
                $plan[$af] = array_map(function ($item) {
                    return is_string($item) ? strip_tags(trim($item)) : $item;
                }, $plan[$af]);
            }
        }

        return $plan;
    }
}
