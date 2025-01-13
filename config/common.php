<?php

return [
    // 表集合
    'db_collection' => [
        // 包含 HasApiTokens 的表名 
        'has_api_token' => [
            'admins'
        ],
        // 包含 HasRoles 的表名
        'has_roles' => [
            'admins'
        ],
        // 包含 NodeTrait 的表名
        'has_node_trait' => [
            'sys_permissions'
        ],
        // 跳过表名
        'skip_tables' => [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'migrations',
            'password_reset_tokens',
            'personal_access_tokens',
            'sessions',
            'sys_model_has_permissions',
            'sys_model_has_roles',
            'sys_role_has_permissions'
        ]
    ],
    'pagination' => [
        // 分页大小选项
        'per_page_options' => [10, 20, 50, 100, 200],
        // 默认分页大小
        'default_per_page' => 20,
    ],
    'exception_handler' => [
        // 不在调试信息中显示的敏感字段
        'dont_flash' => [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key'
        ],
        // 在请求信息中需要过滤的敏感键名
        'sensitive_keys' => [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key'
        ],
        // 允许在调试信息中显示的请求头
        'allowed_headers' => [
            'accept',
            'content-type',
            'user-agent',
            'referer',
            'origin',
            'host'
        ]
    ]
];