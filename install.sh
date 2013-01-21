#!/bin/bash

#########################
#######  CONF  ##########
#########################
#TZ=UTC
TZ=Europe/Kiev

echo '%adm ALL=(ALL)  NOPASSWD: ALL' >> /etc/sudoers
echo "Defaults:${SUDO_USER}  !requiretty" >> /etc/sudoers
chmod 755 /home/$SUDO_USER
apt-get install -y apache2 apache2-doc php5 php5-cli php5-gd php-apc php5-mysql curl php5-curl mysql-server-5.5 mysql-client-5.5 git git-doc gitk php-pear php5-xdebug dnsmasq
# Setted password 'toor' for MySQL root user

a2enmod rewrite

### PHP CONF
sed -i "s/^memory_limit =.*$/memory_limit = 512M/" /etc/php5/apache2/php.ini
sed -i "s/^memory_limit =.*$/memory_limit = 256M/" /etc/php5/cli/php.ini
sed -i "s/^max_execution_time =.*$/max_execution_time = 300/" /etc/php5/cli/php.ini
sed -i "s/^max_execution_time =.*$/max_execution_time = 300/" /etc/php5/cli/php.ini
sed -i "s/^max_input_time =.*$/max_input_time = 300/" /etc/php5/cli/php.ini
sed -i "s/^max_input_time =.*$/max_input_time = 300/" /etc/php5/cli/php.ini

echo "date.timezone = $TZ" >> /etc/php5/apache2/php.ini
echo "date.timezone = $TZ" >> /etc/php5/cli/php.ini
echo 'xdebug.remote_enable=1' >> /etc/php5/conf.d/20-xdebug.ini
echo 'xdebug.cli_color=1' >> /etc/php5/conf.d/20-xdebug.ini




### HTTPD CONF
sed -i "s/^export APACHE_RUN_USER=.*$/export APACHE_RUN_USER=$SUDO_USER/" /etc/apache2/envvars
sed -i "s/^export APACHE_RUN_GROUP=.*$/export APACHE_RUN_GROUP=$SUDO_USER/" /etc/apache2/envvars

echo 'address=/dev/127.0.0.1' >> /etc/dnsmasq.d/localdev
service dnsmasq restart

pear channel-discover pear.drush.org
pear install drush/drush-4.6.0
pear install Console_Table

