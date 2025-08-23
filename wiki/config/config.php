<?php
/**
 * Wiki Configuration
 */

return [
    'database' => [
        'driver' => 'sqlite',
        'database' => __DIR__ . '/../database/wiki.sqlite',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    
    'app' => [
        'name' => 'BizDir Wiki',
        'version' => '1.0.0',
        'url' => getenv('WIKI_URL') ?: 'http://localhost:8080',
        'timezone' => 'Asia/Kolkata',
        'debug' => getenv('WIKI_DEBUG') === 'true',
        'environment' => getenv('WIKI_ENV') ?: 'development',
    ],
    
    'security' => [
        'jwt_secret' => getenv('WIKI_JWT_SECRET') ?: 'your-256-bit-secret-key-here-change-in-production',
        'jwt_expire' => 86400, // 24 hours
        'session_expire' => 3600, // 1 hour
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // 15 minutes
        'password_min_length' => 8,
        'require_2fa' => false,
    ],
    
    'features' => [
        'auto_sync' => true,
        'real_time_notifications' => true,
        'executive_dashboard' => true,
        'version_control' => true,
        'comments' => true,
        'likes' => true,
        'file_uploads' => true,
        'search' => true,
        'analytics' => true,
    ],
    
    'sync' => [
        'source_project_path' => '../mvp',
        'sync_interval' => 300, // 5 minutes
        'auto_generate_docs' => true,
        'watch_files' => [
            '*.php',
            '*.js',
            '*.css',
            '*.md',
            '*.json',
            'composer.json',
            'package.json'
        ],
        'ignore_paths' => [
            'vendor/',
            'node_modules/',
            '.git/',
            'tmp/',
            'cache/'
        ]
    ],
    
    'notifications' => [
        'email_enabled' => true,
        'slack_enabled' => false,
        'webhook_url' => getenv('WIKI_WEBHOOK_URL'),
        'from_email' => 'wiki@bizdir.local',
        'admin_email' => 'admin@bizdir.local',
    ],
    
    'uploads' => [
        'max_file_size' => '10M',
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
        'upload_path' => __DIR__ . '/../public/uploads/',
        'url_path' => '/uploads/',
    ],
    
    'cache' => [
        'driver' => 'file',
        'path' => __DIR__ . '/../cache/',
        'expire' => 3600,
    ],
    
    'logging' => [
        'level' => 'info',
        'path' => __DIR__ . '/../logs/',
        'max_files' => 30,
    ],
    
    'executive_dashboard' => [
        'refresh_interval' => 60, // seconds
        'metrics' => [
            'project_progress',
            'team_activity',
            'documentation_coverage',
            'quality_metrics',
            'deployment_status',
            'revenue_projections',
            'user_engagement',
            'system_health'
        ],
        'charts' => [
            'progress_timeline',
            'team_contribution',
            'documentation_growth',
            'bug_trends',
            'performance_metrics'
        ]
    ],
    
    'roles' => [
        'developer' => [
            'access_level' => 3,
            'can_edit' => ['technical', 'api'],
            'dashboard_widgets' => ['code_metrics', 'bug_count', 'deployment_status']
        ],
        'qa' => [
            'access_level' => 3, 
            'can_edit' => ['qa-testing', 'user-guides'],
            'dashboard_widgets' => ['test_coverage', 'bug_trends', 'quality_score']
        ],
        'operations' => [
            'access_level' => 4,
            'can_edit' => ['operations', 'deployment', 'security'],
            'dashboard_widgets' => ['system_health', 'deployment_status', 'performance']
        ],
        'project_manager' => [
            'access_level' => 5,
            'can_edit' => ['project-management', 'user-guides'],
            'dashboard_widgets' => ['project_progress', 'team_activity', 'timeline']
        ],
        'product_owner' => [
            'access_level' => 5,
            'can_edit' => ['product', 'user-guides'],
            'dashboard_widgets' => ['feature_progress', 'user_feedback', 'roadmap']
        ],
        'c_level' => [
            'access_level' => 10,
            'can_edit' => ['executive'],
            'dashboard_widgets' => ['kpi_overview', 'revenue_metrics', 'strategic_goals']
        ]
    ]
];
