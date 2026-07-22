<?php

return [
    'enabled' => (bool) env('AI_ENABLED', false),
    'provider' => env('AI_PROVIDER', 'openai'),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'job_timeout' => env('AI_JOB_TIMEOUT') !== null ? (int) env('AI_JOB_TIMEOUT') : null,
    'max_retries' => (int) env('AI_MAX_RETRIES', 2),
    'queue' => env('AI_QUEUE', 'default'),
    'dispatch_mode' => env('AI_DISPATCH_MODE', env('APP_ENV') === 'local' ? 'after_response' : 'queue'),
    'fallback' => [
        'provider' => env('AI_FALLBACK_PROVIDER'),
        'transient_statuses' => array_values(array_filter(array_map(
            static fn ($value) => is_numeric(trim((string) $value)) ? (int) trim((string) $value) : null,
            explode(',', (string) env('AI_FALLBACK_TRANSIENT_STATUSES', '408,429,500,502,503,504'))
        ), static fn ($value) => $value !== null)),
    ],
    'max_plans_per_event' => (int) env('AI_MAX_PLANS_PER_EVENT', 1),
    'max_context_chars' => (int) env('AI_MAX_CONTEXT_CHARS', 18000),
    'max_knowledge_chunks' => (int) env('AI_MAX_KNOWLEDGE_CHUNKS', 6),
    'prompt_version' => env('AI_PROMPT_VERSION', 'washer-action-plan-v1'),
    'knowledge' => [
        'chunk_size' => (int) env('AI_KNOWLEDGE_CHUNK_SIZE', 1200),
        'chunk_overlap' => (int) env('AI_KNOWLEDGE_CHUNK_OVERLAP', 200),
        'pdf_ocr_enabled' => (bool) env('AI_KNOWLEDGE_PDF_OCR_ENABLED', true),
        'pdf_ocr_detail' => env('AI_KNOWLEDGE_PDF_OCR_DETAIL', 'high'),
        'pdf_ocr_model' => env('AI_KNOWLEDGE_PDF_OCR_MODEL'),
    ],
    'chat' => [
        'model' => env('AI_CHAT_MODEL', env('AI_MODEL')),
        'history_window' => (int) env('AI_CHAT_HISTORY_WINDOW', 8),
        'max_stored_messages' => (int) env('AI_CHAT_MAX_STORED_MESSAGES', 30),
        'max_context_items' => (int) env('AI_CHAT_MAX_CONTEXT_ITEMS', 5),
    ],
    'platform_context' => [
        'schema_table_limit' => (int) env('AI_PLATFORM_SCHEMA_TABLE_LIMIT', 8),
        'schema_column_limit' => (int) env('AI_PLATFORM_SCHEMA_COLUMN_LIMIT', 14),
        'recent_activity_limit' => (int) env('AI_PLATFORM_RECENT_ACTIVITY_LIMIT', 4),
        'recent_evidence_limit' => (int) env('AI_PLATFORM_RECENT_EVIDENCE_LIMIT', 4),
        'query_match_limit' => (int) env('AI_PLATFORM_QUERY_MATCH_LIMIT', 8),
    ],
    'rules' => [
        'elongacion_warning_threshold' => (float) env('WASHER_ELONGACION_WARNING_THRESHOLD', 1.30),
        'elongacion_critical_threshold' => (float) env('WASHER_ELONGACION_CRITICAL_THRESHOLD', 1.46),
        'elongacion_trend_min_delta' => (float) env('WASHER_ELONGACION_TREND_MIN_DELTA', 0.05),
        'rodaja_max_mm' => env('WASHER_RODAJA_MAX_MM'),
        'high_cost_threshold' => (float) env('WASHER_HIGH_COST_THRESHOLD', 0),
        'cost_growth_ratio' => (float) env('WASHER_COST_GROWTH_RATIO', 1.25),
    ],
    'providers' => [
        'openai' => [
            'base_url' => rtrim((string) env('AI_BASE_URL', 'https://api.openai.com/v1'), '/'),
            'model' => env('OPENAI_MODEL', env('AI_MODEL', 'gpt-5.6')),
            'embedding_model' => env('OPENAI_EMBEDDING_MODEL', env('AI_EMBEDDING_MODEL', 'text-embedding-3-small')),
            'api_key' => env('OPENAI_API_KEY', env('AI_API_KEY')),
            'fallback_models' => array_values(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                explode(',', (string) env('OPENAI_FALLBACK_MODELS', ''))
            ))),
        ],
        'gemini' => [
            'base_url' => rtrim((string) env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'), '/'),
            'model' => env('GEMINI_MODEL', env('AI_MODEL', 'gemini-3.5-flash')),
            'embedding_model' => env('GEMINI_EMBEDDING_MODEL', env('AI_EMBEDDING_MODEL', 'gemini-embedding-2')),
            'api_key' => env('GEMINI_API_KEY', env('AI_API_KEY')),
            'fallback_models' => array_values(array_filter(array_map(
                static fn ($value) => trim((string) $value),
                explode(',', (string) env('GEMINI_FALLBACK_MODELS', ''))
            ))),
        ],
    ],
];
