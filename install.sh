#!/bin/bash

#########################
#######  CONF  ##########
#########################
#TZ=UTC
TZ=Europe/Kiev

echo 'This is WEB developer helper(WDH) tool.'
echo 'WDH will install and configure LAMP stack, particulary Apache webserver,'
echo 'MySQL server, and PHP.'
echo 'Press ANY key to continue or Ctrl+C to exit.'

ROOT_UID="0"

#Check if run as root
if [ "$UID" -ne "$ROOT_UID" ] ; then
  echo "In order to install WDH you MUST run this command with 'sudo'."
  exit 1
fi

echo 'Setting up sudo config...'
echo "Defaults:${SUDO_USER}  !requiretty" >> /etc/sudoers.d/wdh
echo "${SUDO_USER} ALL=(ALL)  NOPASSWD: ALL" > /etc/sudoers.d/wdh
echo '/etc/sudoers.d/wdh created.'

echo 'Granting access to home dir'
chmod -v 755 /home/$SUDO_USER

echo 'Creating WDH configuration directory...'
mkdir -vp /home/$SUDO_USER/.wdh

echo 'Now we will install and configure LAMP stack.'
echo 'Installer will ask you to enter MySQL root password. You can choose any.'
echo 'But remember it. You will need it one more time during installation.'
read -p 'Press ANY key to continue...'
apt-get install -y apache2 apache2-doc php5 php5-cli php5-gd php-apc php5-mysql curl php5-curl mysql-server-5.5 mysql-client-5.5 git git-doc gitk php-pear php5-xdebug dnsmasq vim vim-common diffuse geany aptitude

echo 'Generating MySQL config file'
ret=1
while [[ $ret != 0 ]]
do
  read -p 'Enter your MySQL password: ' mysql_pass
  mysql -uroot -p$mysql_pass -e 'show databases;' &> /dev/null
  ret=$?
  if [[ $ret != 0 ]]
  then
    echo 'You entered wrong password!'
  else
      echo '[client]' > /home/$SUDO_USER/.my.cnf
      echo 'user=root' >> /home/$SUDO_USER/.my.cnf
      echo "password=$mysql_pass" >> /home/$SUDO_USER/.my.cnf
      echo '[mysql]' >> /home/$SUDO_USER/.my.cnf
      echo 'prompt=(\\u@\\h) [\\d]>\\_' >> /home/$SUDO_USER/.my.cnf
  fi
done

echo 'Genarating php config'
echo 'memory_limit = 512M' > /etc/php5/conf.d/99-wdh.ini
echo 'max_execution_time = 300' > /etc/php5/conf.d/99-wdh.ini
echo 'max_input_time = 300' > /etc/php5/conf.d/99-wdh.ini
echo 'date.timezone = $TZ' > /etc/php5/conf.d/99-wdh.ini
echo 'xdebug.remote_enable=1' > /etc/php5/conf.d/99-wdh.ini
echo 'xdebug.cli_color=1' > /etc/php5/conf.d/99-wdh.ini
echo '/etc/php5/conf.d/99-wdh.ini created.'

echo 'Generating APACHE configuration'
echo 'Enabling apache mod_rewrite'
a2enmod rewrite
echo 'By default websites would be created inside "websites" your home directory.'
echo 'You can change it name if you want. Press "Enter" to leave default name.'
read -p 'Please enter webroot name[websites] : ' WEBROOT
if [[ -z $WEBROOT ]]
then
  mkdir /home/$SUDO_USER/websites
else
  mkdir /home/$SUDO_USER/$WEBROOT
  echo "WEBROOT=$WEBROOT" > /home/$SUDO_USER/.wdh/config
fi
sed -i "s/^export APACHE_RUN_USER=.*$/export APACHE_RUN_USER=$SUDO_USER/" /etc/apache2/envvars
sed -i "s/^export APACHE_RUN_GROUP=.*$/export APACHE_RUN_GROUP=$SUDO_USER/" /etc/apache2/envvars
mkdir -v /home/$SUDO_USER/.wdh/vhost
echo "Include /home/$SUDO_USER/.wdh/vhost/*.conf"  > /etc/apache2/conf.d/wdh
echo 'Configuration file for Apache "/etc/apache2/conf.d/wdh" created.'
sudo service apache2 restart

echo 'Generation configuration for DNSmasq...'
echo 'address=/dev/127.0.0.1' >> /etc/dnsmasq.d/wdh
echo 'Configuration file for DNSmasq "/etc/dnsmasq.d/wdh" created.'
service dnsmasq restart

echo 'Installing Drush...'
pear channel-discover pear.drush.org
pear install drush/drush
pear install Console_Table

# TODO: Install webdevhelper to /usr/local/bin and create symlink /usr/local/bin/wdh.
