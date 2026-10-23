<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ChartSense AI Multi-AI Trading Intelligence Configuration
    |--------------------------------------------------------------------------
    |
    | Defines supported providers, models, default assignments per analytical
    | role, timeouts, concurrency limits, and fallback strategies.
    |
    */

    'default_provider' => env('AI_DEFAULT_PROVIDER', 'gemini'),

    'timeouts' => [
        'per_model_seconds' => (int) env('AI_MODEL_TIMEOUT', 45),
        'total_analysis_seconds' => (int) env('AI_TOTAL_TIMEOUT', 120),
    ],

    'concurrency' => [
        'max_parallel_models' => (int) env('AI_MAX_PARALLEL_MODELS', 4),
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Assignments
    |--------------------------------------------------------------------------
    |
    | Maps each analytical role to a preferred provider and model.
    | If an API key is absent, the system seamlessly falls back to 'deterministic'.
    |
    */
    'role_assignments' => [
        'price_action' => [
            'provider' => env('AI_PRICE_ACTION_PROVIDER', 'gemini'),
            'model' => env('AI_PRICE_ACTION_MODEL', 'gemini-2.5-flash'),
        ],
        'market_structure' => [
            'provider' => env('AI_MARKET_STRUCTURE_PROVIDER', 'gemini'),
            'model' => env('AI_MARKET_STRUCTURE_MODEL', 'gemini-2.5-flash'),
        ],
        'smc' => [
            'provider' => env('AI_SMC_PROVIDER', 'gemini'),
            'model' => env('AI_SMC_MODEL', 'gemini-2.5-flash'),
        ],
        'fibonacci_patterns' => [
            'provider' => env('AI_FIBONACCI_PROVIDER', 'gemini'),
            'model' => env('AI_FIBONACCI_MODEL', 'gemini-2.5-flash'),
        ],
        'quantitative_analyst' => [
            'provider' => env('AI_QUANT_PROVIDER', 'deterministic'),
            'model' => env('AI_QUANT_MODEL', 'rule-engine-v2'),
        ],
        'market_regime' => [
            'provider' => env('AI_REGIME_PROVIDER', 'deterministic'),
            'model' => env('AI_REGIME_MODEL', 'rule-engine-v2'),
        ],
        'technical_indicators' => [
            'provider' => env('AI_INDICATORS_PROVIDER', 'deterministic'),
            'model' => env('AI_INDICATORS_MODEL', 'rule-engine-v2'),
        ],
        'multi_timeframe' => [
            'provider' => env('AI_MTF_PROVIDER', 'gemini'),
            'model' => env('AI_MTF_MODEL', 'gemini-2.5-flash'),
        ],
        'fundamental_news' => [
            'provider' => env('AI_FUNDAMENTAL_PROVIDER', 'gemini'),
            'model' => env('AI_FUNDAMENTAL_MODEL', 'gemini-2.5-flash'),
        ],
        'strategy_selector' => [
            'provider' => env('AI_STRATEGY_SELECTOR_PROVIDER', 'gemini'),
            'model' => env('AI_STRATEGY_SELECTOR_MODEL', 'gemini-2.5-flash'),
        ],
        'independent_reviewer' => [
            'provider' => env('AI_REVIEWER_PROVIDER', 'gemini'),
            'model' => env('AI_REVIEWER_MODEL', 'gemini-2.5-flash'),
        ],
        'trading_risk' => [
            'provider' => env('AI_RISK_PROVIDER', 'gemini'),
            'model' => env('AI_RISK_MODEL', 'gemini-2.5-flash'),
        ],
        'final_decision' => [
            'provider' => env('AI_DECISION_PROVIDER', 'gemini'),
            'model' => env('AI_DECISION_MODEL', 'gemini-2.5-flash'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Credentials & Base URLs
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'gemini' => [
            'name' => 'Google Gemini',
            'api_key' => env('GEMINI_API_KEY', env('AI_API_KEY')),
            'default_model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
            'supports_vision' => true,
        ],
        'claude' => [
            'name' => 'Anthropic Claude',
            'api_key' => env('ANTHROPIC_API_KEY'),
            'default_model' => env('CLAUDE_MODEL', 'claude-3-7-sonnet-20250219'),
            'supports_vision' => true,
        ],
        'openai' => [
            'name' => 'OpenAI',
            'api_key' => env('OPENAI_API_KEY'),
            'default_model' => env('OPENAI_MODEL', 'gpt-4o'),
            'supports_vision' => true,
        ],
        'deepseek' => [
            'name' => 'DeepSeek',
            'api_key' => env('DEEPSEEK_API_KEY'),
            'default_model' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            'supports_vision' => false,
        ],
        'deterministic' => [
            'name' => 'Deterministic Algorithmic Engine',
            'api_key' => 'internal',
            'default_model' => 'rule-engine-v2',
            'supports_vision' => true,
        ],
    ],
];
