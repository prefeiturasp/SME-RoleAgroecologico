docker_compose('docker-compose.yaml')

docker_build(
    'wordpress/roleagroecologico',
    '.', target="base",
    dockerfile='Dockerfile.development',
    live_update=[
        sync('./wordpress/wp-content/themes', '/var/www/html/wp-content/themes'),
        run('chown -R www-data:www-data /var/www/html/wp-content/themes'),
    ]
)

docker_build(
    'wordpress/roleagroecologico-waf',
    '.',target="apache",
    dockerfile='Dockerfile.development',
    live_update=[
        sync('./wordpress/wp-content/themes', '/var/www/html/wp-content/themes'),
        run('chown -R www-data:www-data /var/www/html/wp-content/themes'),
    ]
)