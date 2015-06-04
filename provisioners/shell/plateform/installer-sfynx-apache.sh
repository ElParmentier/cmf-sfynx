#!/bin/bash
DIR=$1
PLATEFORM_INSTALL_NAME=$2
PLATEFORM_INSTALL_TYPE=$3
PLATEFORM_INSTALL_VERSION=$4
PLATEFORM_PROJET_NAME=$5
PLATEFORM_PROJET_GIT=$6
source $DIR/provisioners/shell/env.sh

#if var is empty
if [ -z "$PLATEFORM_PROJET_GIT" ]; then
    $PLATEFORM_PROJET_GIT="https://github.com/pigroupe/cmf-sfynx.git"
fi

# we create directories
if [ ! -d $INSTALL_USERWWW ]; then
    mkdir -p $INSTALL_USERWWW
fi
cd $INSTALL_USERWWW

# we create project
if [ ! -d $PLATEFORM_PROJET_NAME ]; then
    #git clone $PLATEFORM_PROJET_GIT $PLATEFORM_PROJET_NAME
    mkdir -p $PLATEFORM_PROJET_NAME
fi
cd $PLATEFORM_PROJET_NAME

# we create default directories
mkdir -p app/cache
mkdir -p app/logs
mkdir -p app/cachesfynx/loginfailure
mkdir -p web/uploads/media
mkdir -p web/yui
if [ ! -f app/config/parameters.yml ]; then
    cp app/config/parameters.yml.dist app/config/parameters.yml
fi

# we add env var
if ! grep -q "SYMFONY__DATABASE__NAME__ENV" ~/.profile; then
cat <<EOT >> ~/.profile

# env vars for SFYNX platform
export SYMFONY__DATABASE__NAME__ENV=sfynx$PLATEFORM_PROJET_NAME_dev;
export SYMFONY__DATABASE__USER__ENV=root;
export SYMFONY__DATABASE__PASSWORD__ENV=pacman;
export SYMFONY__TEST__DATABASE__NAME__ENV=sfynx$PLATEFORM_PROJET_NAME_test;
export SYMFONY__TEST__DATABASE__USER__ENV=root;
export SYMFONY__TEST__DATABASE__PASSWORD__ENV=pacman;
EOT
source ~/.profile
fi

# we create the virtualhiost of sfynx for apache
mkdir -p /tmp
cat <<EOT >/tmp/$PLATEFORM_PROJET_NAME
<VirtualHost *:80>
        ServerName  dev.$PLATEFORM_PROJET_NAME.local
        ServerAlias dev.$PLATEFORM_PROJET_NAME.local             
        DocumentRoot $INSTALL_USERWWW/$PLATEFORM_PROJET_NAME/web/
        <Directory "$INSTALL_USERWWW/$PLATEFORM_PROJET_NAME/web">
                Options Indexes FollowSymLinks MultiViews
                AllowOverride None
                RewriteEngine On

                RewriteCond %{REQUEST_FILENAME} !-f
                RewriteRule ^(.*)\$ app_dev.php [QSA,L]

                #php_value auto_prepend_file "$INSTALL_USERWWW/xhprof/external/header.php"
                #php_value auto_append_file "$INSTALL_USERWWW/xhprof/external/footer.php"

                #Require all granted
                Order allow,deny
                allow from all
        </Directory>

        ErrorLog \${APACHE_LOG_DIR}/error_$PLATEFORM_PROJET_NAME_dev.log
        LogFormat "%{X-Forwarded-For}i %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" varnishcombined
        CustomLog \${APACHE_LOG_DIR}/access-dev.log varnishcombined
</VirtualHost>

<VirtualHost *:80>
        ServerName  test.$PLATEFORM_PROJET_NAME.local
        DocumentRoot $INSTALL_USERWWW/$PLATEFORM_PROJET_NAME/web/

        <Directory "$INSTALL_USERWWW/$PLATEFORM_PROJET_NAME/web">
                Options Indexes FollowSymLinks MultiViews
                AllowOverride None
                RewriteEngine On

                RewriteCond %{REQUEST_FILENAME} !-f
                RewriteRule ^(.*)\$ app_test.php [QSA,L]

                #php_value auto_prepend_file "$INSTALL_USERWWW/xhprof/external/header.php"
                #php_value auto_append_file "$INSTALL_USERWWW/xhprof/external/footer.php"

                #Require all granted
                Order allow,deny
                allow from all
        </Directory>

        ErrorLog \${APACHE_LOG_DIR}/error_$PLATEFORM_PROJET_NAME_test.log
        LogFormat "%{X-Forwarded-For}i %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" varnishcombined
        CustomLog \${APACHE_LOG_DIR}/access-test.log varnishcombined
</VirtualHost>

<VirtualHost *:80>
        ServerName prod.$PLATEFORM_PROJET_NAME.local
        DocumentRoot $INSTALL_USERWWW/$PLATEFORM_PROJET_NAME/web/

        <Directory $INSTALL_USERWWW/$PLATEFORM_PROJET_NAME/web>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride None
                RewriteEngine On

	        RewriteCond %{REQUEST_FILENAME} !-f
                RewriteRule ^(.*)\$ app.php [QSA,L]

	         # Desactiver l'utilistion des logiciels de type rapidLink
                RewriteCond %{HTTP_REFERER} .*kristjanlilleoja.com.*\$  [OR]
                RewriteCond %{HTTP_REFERER} .*megaupload.byethost7.com.*\$  [OR]
                RewriteCond %{HTTP_REFERER} .*openurls.eu.*\$  [OR]
                RewriteCond %{HTTP_REFERER} .*urlopener.com.*\$  [OR]
                RewriteCond %{HTTP_REFERER} .*rapidlinkr.com.*\$  [OR]
                RewriteCond %{HTTP_REFERER} .*multilinkr.com.*\$  [OR]
                RewriteCond %{HTTP_REFERER} .*openmultipleurl.com.*\$  [OR]
                RewriteCond %{HTTP_REFERER} .*pastebin.com.*\$
                RewriteCond %{REQUEST_URI} !^/404error\$\$
                RewriteRule ^(.*)\$ http://prod.$PLATEFORM_PROJET_NAME.local/404error\$                
		  
		# autorize all IPs                
	        Order allow,deny
                allow from all

		<IfModule mpm_worker_module>
                    StartServers            5
                    MinSpareServers         32  #5
                    MaxSpareServers         64  #10
                    ServerLimit             500
                    MaxRequestWorkers       500
                    MaxConnectionsPerChild  0
                    ThreadsPerChild         256      #25
                    MaxClients              256      #150
                </IfModule>
        </Directory>

        ErrorLog \${APACHE_LOG_DIR}/error_$PLATEFORM_PROJET_NAME_prod.log
        LogFormat "%{X-Forwarded-For}i %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" varnishcombined
        CustomLog \${APACHE_LOG_DIR}/access-prod.log varnishcombined

</VirtualHost>
EOT
sudo mv /tmp/$PLATEFORM_PROJET_NAME /etc/apache2/sites-available/$PLATEFORM_PROJET_NAME

# we create the symbilic link
sudo ln -s /etc/apache2/sites-available/$PLATEFORM_PROJET_NAME /etc/apache2/sites-enabled/$PLATEFORM_PROJET_NAME

# we add host in the /etc/hosts file
if ! grep -q "dev.$PLATEFORM_PROJET_NAME.local" /etc/hosts; then
    echo "Adding hostname to your /etc/hosts"
    echo "127.0.0.1    dev.$PLATEFORM_PROJET_NAME.local" | sudo tee --append /etc/hosts
    echo "127.0.0.1    test.$PLATEFORM_PROJET_NAME.local" | sudo tee --append /etc/hosts
    echo "127.0.0.1    prod.$PLATEFORM_PROJET_NAME.local" | sudo tee --append /etc/hosts
fi

# we restart apache server
sudo service apache2 restart

# we install the composer file
if [ ! -f composer.phar ]; then
    wget https://getcomposer.org/composer.phar -O ./composer.phar
    # curl -s https://getcomposer.org/installer | php
fi
php -d memory_limit=1024M composer.phar install --no-interaction
php composer.phar dump-autoload --optimize

#
rm -rf app/cache/*
rm -rf app/logs/*

# Utiliser l'ACL sur un système qui supporte chmod +a
HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo chmod +a "$HTTPDUSER allow delete,write,append,file_inherit,directory_inherit" app/cache app/logs
sudo chmod +a "`whoami` allow delete,write,append,file_inherit,directory_inherit" app/cache app/logs

# Utiliser l'ACL sur un système qui ne supporte pas chmod +a
HTTPDUSER=`ps aux | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX app/cache app/logs
sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX app/cache app/logs

# create database
php app/console doctrine:database:create
php app/console doctrine:schema:create
php app/console doctrine:fixtures:load
php app/console assets:install
php app/console assetic:dump
php app/console clear:cache

# we run the phing script to initialize the sfynx project
vendor/bin/phing -f app/phing/initialize.xml rebuild
