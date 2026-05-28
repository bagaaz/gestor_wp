<?php

return [
    'sites_path'    => env('WP_SITES_PATH',        '/var/www/wordpress'),
    'nginx_conf'    => env('WP_NGINX_CONF_PATH',    '/etc/nginx/sites-available'),
    'nginx_enabled' => env('WP_NGINX_ENABLED_PATH', '/etc/nginx/sites-enabled'),
    'backups_path'  => env('WP_BACKUPS_PATH',       '/var/www/html/gestor_wp/backups'),
    'project_root'  => env('WP_PROJECT_ROOT',       '/var/www/html/gestor_wp'),
    'base_domain'   => env('WP_BASE_DOMAIN',        'wp.devconecta.com.br'),
];
