#!/usr/bin/env bash
set -Eeuo pipefail

export PATH="/usr/bin:$PATH"

# Função para atualizar core do WordPress mantendo uploads e plugins
update_wordpress_core() {
    echo >&2 "Atualizando WordPress core..."
    rsync -a --delete --inplace \
          --exclude='wp-content/uploads/' \
          --exclude='wp-content/plugins/' \
          --exclude='wp-content/languages/' \
          --exclude='wp-config.php' \
          /usr/src/wordpress/ /var/www/html/
    mkdir -p /var/www/html/wp-content/{uploads,plugins}
    chown www-data:www-data /var/www/html/wp-content/{uploads,plugins}
    echo >&2 "WordPress core atualizado!"
}

update_wordpress_plugins_privados(){
        # lógica de plugins privados
        if [ -d "/tmp/plugins-privados" ]; then
        echo "Copiando plugins privados..."
        cp -r /tmp/plugins-privados/ /var/www/html/
        rm -f /var/www/html/wp-content/plugins/aviso-secretario /var/www/html/wp-content/plugins/config-role /var/www/html/wp-content/plugins/coressoapi /var/www/html/wp-content/plugins/email-admin /var/www/html/wp-content/plugins/grupo-editores
        ln -s /var/www/html/plugins-privados/aviso-secretario /var/www/html/wp-content/plugins/aviso-secretario
        ln -s /var/www/html/plugins-privados/config-role /var/www/html/wp-content/plugins/config-role
        ln -s /var/www/html/plugins-privados/coressoapi /var/www/html/wp-content/plugins/coressoapi
        ln -s /var/www/html/plugins-privados/email-admin /var/www/html/wp-content/plugins/email-admin
        ln -s /var/www/html/plugins-privados/grupo-editores /var/www/html/wp-content/plugins/grupo-editores
        chown -R www-data:www-data /var/www/html/wp-content/plugins/aviso-secretario
        rm -Rf /tmp/plugins-privados
        echo >&2 "WordPress Plugins privados executado!"
        fi
}

if [[ "$1" == apache2* ]] || [ "$1" = 'php-fpm' ] || { self="$(basename "$0")" && [ "$self" = 'docker-ensure-installed.sh' ]; }; then
        uid="$(id -u)"
        gid="$(id -g)"
        if [ "$uid" = '0' ]; then
                case "$1" in
                        apache2*)
                                user="${APACHE_RUN_USER:-www-data}"
                                group="${APACHE_RUN_GROUP:-www-data}"
                                pound='#'
                                user="${user#$pound}"
                                group="${group#$pound}"
                                ;;
                        *) # php-fpm
                                user='www-data'
                                group='www-data'
                                ;;
                esac
        else
                user="$uid"
                group="$gid"
        fi

        # Lógica original de inicialização
        if [ ! -e index.php ] && [ ! -e wp-includes/version.php ]; then
                if [ "$uid" = '0' ] && [ "$(stat -c '%u:%g' .)" = '0:0' ]; then
                        chown "$user:$group" .
                fi

                echo >&2 "WordPress not found in $PWD - copying now..."
                if [ -n "$(find -mindepth 1 -maxdepth 1 -not -name wp-content)" ]; then
                        echo >&2 "WARNING: $PWD is not empty! (copying anyhow)"
                fi
                sourceTarArgs=(
                        --create
                        --file -
                        --directory /usr/src/wordpress
                        --owner "$user" --group "$group"
                )
                targetTarArgs=(
                        --extract
                        --file -
                )
                if [ "$uid" != '0' ]; then
                        targetTarArgs+=( --no-overwrite-dir )
                fi
                for contentPath in \
                        /usr/src/wordpress/.htaccess \
                        /usr/src/wordpress/wp-content/*/*/ \
                ; do
                        contentPath="${contentPath%/}"
                        [ -e "$contentPath" ] || continue
                        contentPath="${contentPath#/usr/src/wordpress/}"
                        if [ -e "$PWD/$contentPath" ]; then
                                echo >&2 "WARNING: '$PWD/$contentPath' exists! (not copying the WordPress version)"
                                sourceTarArgs+=( --exclude "./$contentPath" )
                        fi
                done
                tar "${sourceTarArgs[@]}" . | tar "${targetTarArgs[@]}"
                echo >&2 "Complete! WordPress has been successfully copied to $PWD"
        fi

        # Lógica original do wp-config
        wpEnvs=( "${!WORDPRESS_@}" )
        if [ ! -s wp-config.php ] && [ "${#wpEnvs[@]}" -gt 0 ]; then
                for wpConfigDocker in \
                        wp-config-docker.php \
                        /usr/src/wordpress/wp-config-docker.php \
                ; do
                        if [ -s "$wpConfigDocker" ]; then
                                echo >&2 "No 'wp-config.php' found in $PWD, but 'WORDPRESS_...' variables supplied; copying '$wpConfigDocker' (${wpEnvs[*]})"
                                awk '
                                     /put your unique phrase here/ {
                                       cmd = "head -c1m /dev/urandom | sha1sum | cut -d\\  -f1"
                                       cmd | getline str
                                       close(cmd)
                                       gsub("put your unique phrase here", str)
                                     }
                                     { print }
                                ' "$wpConfigDocker" > wp-config.php
                                if [ "$uid" = '0' ]; then
                                     chown "$user:$group" wp-config.php || true
                                fi
                                break
                        fi
                done
        fi
fi

update_wordpress_core
update_wordpress_plugins_privados

# Instalar e ativar idioma pt_BR
if command -v wp > /dev/null; then
    echo "Instalando idioma pt_BR..."
    wp language core install pt_BR --activate --allow-root || true
fi

exec "$@"