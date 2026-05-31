<?php

return [
    'sites_path'    => env('WP_SITES_PATH',        '/var/www/wordpress'),
    'nginx_conf'    => env('WP_NGINX_CONF_PATH',    '/etc/nginx/sites-available'),
    'nginx_enabled' => env('WP_NGINX_ENABLED_PATH', '/etc/nginx/sites-enabled'),
    'backups_path'  => env('WP_BACKUPS_PATH',       '/var/www/html/gestor_wp/backups'),
    'project_root'  => env('WP_PROJECT_ROOT',       '/var/www/html/gestor_wp'),
    'base_domain'   => env('WP_BASE_DOMAIN',        'wp.devconecta.com.br'),
    'panel_email'    => env('PANEL_EMAIL',           ''),
    'panel_password' => env('PANEL_PASSWORD',        ''),

    // Evolution API (WhatsApp)
    'evolution_api_url'        => env('EVOLUTION_API_URL',        ''),
    'evolution_global_api_key' => env('EVOLUTION_GLOBAL_API_KEY', ''),
];
