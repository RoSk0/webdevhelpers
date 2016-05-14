#!/usr/bin/env bash

function install_sudo_config() {
  echo 'Setting up sudo config...'
  echo "Defaults:${SUDO_USER} !requiretty" > /etc/sudoers.d/wdh
  echo "${SUDO_USER} ALL=(ALL) NOPASSWD: ALL" >> /etc/sudoers.d/wdh
  echo '/etc/sudoers.d/wdh created.'
}

function install_directories() {
  echo 'Granting access to home directory..'
  chmod 750 /home/${SUDO_USER}

  echo 'Creating WDH configuration directory...'
  mkdir /home/${SUDO_USER}/.wdh

  echo 'Creating bin directory...'
  mkdir /home/${SUDO_USER}/bin/
}

function install_mysql {
  echo 'Installing MySQL server and client...'

  read -p 'Enter your MySQL root password: ' MYSQL_PASS

  debconf-set-selections <<< "mysql-server mysql-server/root_password password ${MYSQL_PASS}"
  debconf-set-selections <<< "mysql-server mysql-server/root_password_again password ${MYSQL_PASS}"
  apt-get install -y mysql-server mysql-client


  echo 'Generating MySQL configuration files...'
  ret=1
  while [[ ${ret} != 0 ]]
  do
    mysql -uroot -p${MYSQL_PASS} -e 'show databases;' &> /dev/null
    ret=$?
    if [[ ${ret} != 0 ]]
    then
      echo 'You entered wrong password!'
    else
      echo '[client]' > /home/${SUDO_USER}/.my.cnf
      echo 'user=root' >> /home/${SUDO_USER}/.my.cnf
      echo "password=${MYSQL_PASS}" >> /home/${SUDO_USER}/.my.cnf
      echo '[mysql]' >> /home/${SUDO_USER}/.my.cnf
      echo 'prompt=(\\u@\\h) [\\d]>\\_' >> /home/${SUDO_USER}/.my.cnf
      chown ${SUDO_USER}:${SUDO_USER} /home/${SUDO_USER}/.my.cnf
    fi
  done

  echo '[mysqld]' >> /etc/mysql/mysql.conf.d/wdh.cnf
  echo 'innodb_file_per_table = 1' >> /etc/mysql/mysql.conf.d/wdh.cnf
  echo 'open_files_limit = 20000' >> /etc/mysql/mysql.conf.d/wdh.cnf
}

function install_php {
  echo 'Installing PHP...'
  apt-get install -y php php-cli php-gd php-json php-mcrypt php-mysql curl php-curl php-pear php-xdebug php-mbstring

  echo 'Genarating php configuration...'
  echo 'memory_limit = 1G' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'post_max_size = 128M' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'upload_max_filesize = 128M' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'max_execution_time = 300' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'max_input_time = 300' >> /etc/php/7.0/mods-available/wdh.ini
  echo "date.timezone = ${TZ}" >> /etc/php/7.0/mods-available/wdh.ini
  echo 'xdebug.remote_enable = 1' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'xdebug.cli_color = 1' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'xdebug.coverage_enable = 0' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'disable_functions = ' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'display_errors = On' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'error_reporting = E_ALL' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'session.gc_maxlifetime = 86400' >> /etc/php/7.0/mods-available/wdh.ini
  echo 'apc.shm_size = 256M' >> /etc/php/7.0/mods-available/wdh.ini
  phpenmod wdh
  echo '/etc/php/7.0/mods-available/wdh.ini created.'
}

function install_apache {
  apt-get install -y apache2 apache2-doc libapache2-mod-php

  echo 'Generating Apache configuration...'
  echo 'Enabling apache mod_rewrite.'
  a2enmod rewrite
  echo 'By default websites would be created inside "websites" your home directory.'
  echo 'You can change it name if you want. Press "Enter" to leave default name.'
  read -p 'Please enter webroot name[websites] : ' WEBROOT
  if [[ -z ${WEBROOT} ]]
  then
    mkdir /home/${SUDO_USER}/websites
    chown ${SUDO_USER}:${SUDO_USER} /home/${SUDO_USER}/websites
    echo "webroot=websites" > /home/${SUDO_USER}/.wdh/config
  else
    mkdir /home/${SUDO_USER}/${WEBROOT}
    chown ${SUDO_USER}:${SUDO_USER} /home/${SUDO_USER}/${WEBROOT}
    echo "webroot=${WEBROOT}" > /home/${SUDO_USER}/.wdh/config
  fi
  sed -i "s/^export APACHE_RUN_USER=.*$/export APACHE_RUN_USER=${SUDO_USER}/" /etc/apache2/envvars
  sed -i "s/^export APACHE_RUN_GROUP=.*$/export APACHE_RUN_GROUP=${SUDO_USER}/" /etc/apache2/envvars
  chown ${SUDO_USER} /var/lock/apache2/
  mkdir /home/${SUDO_USER}/.wdh/vhost
  echo "IncludeOptional /home/${SUDO_USER}/.wdh/vhost/*.conf"  > /etc/apache2/sites-available/wdh.conf
  echo 'Configuration file for Apache "/etc/apache2/sites-available/wdh.conf" created.'
  a2ensite wdh.conf
  sudo service apache2 restart

}

function install_dnsmasq() {
  apt-get install -y dnsmasq

  echo 'Generation configuration for DNSmasq...'
  echo 'nameserver 8.8.8.8' >> /etc/dnsmasq.resolv.conf
  echo 'nameserver 8.8.4.4' >> /etc/dnsmasq.resolv.conf
  echo 'resolv-file=/etc/dnsmasq.resolv.conf' >> /etc/dnsmasq.d/wdh
  echo 'address=/dev/127.0.0.1' >> /etc/dnsmasq.d/wdh
  echo 'Configuration file for DNSmasq "/etc/dnsmasq.d/wdh" created.'
  service dnsmasq restart
}

function install_additional_helpers() {
  apt-get install -y git git-doc git-gui gitk vim vim-common diffuse geany
}

function install_drush() {

  echo 'Installing Drush...'
  # Download latest stable release using the code below or browse to github.com/drush-ops/drush/releases.
  php -r "readfile('https://github.com/drush-ops/drush/releases/download/8.1.2/drush.phar');" > /home/${SUDO_USER}/bin/drush
#  php -r "readfile('http://files.drush.org/drush.phar');" > /home/${SUDO_USER}/bin/drush

  # Test your install.
  php /home/${SUDO_USER}/bin/drush core-status
  # Make `drush` executable as a command from anywhere. Destination can be anywhere on $PATH.
  chmod +x /home/${SUDO_USER}/bin/drush

  # We installing Drush in users bin directory which added to path later in .profile
  # so we are adding this dir to PATH ourselves.
  cat >> /home/${SUDO_USER}/.bashrc <<path

  # set PATH so it includes user's private bin if it exists
  if [ -d "\$HOME/bin" ] ; then
    PATH="\$HOME/bin:\$PATH"
  fi

path

  # Enrich the bash startup file with completion and aliases.
  /home/${SUDO_USER}/bin/drush -y init

  chown -vR ${SUDO_USER}:${SUDO_USER} /home/${SUDO_USER}/.drush

  echo 'Adding weekly task to update Drush...'
  install_weekly_task 'drush' "php -r \"readfile('http://files.drush.org/drush.phar');\" > /home/${SUDO_USER}/bin/drush && chmod +x /home/${SUDO_USER}/bin/drush"
}

function install_drupal_console() {
  echo 'Downloading Drupal console installer...'
  curl https://drupalconsole.com/installer -L -o /home/${SUDO_USER}/bin/drupal
  chmod +x /home/${SUDO_USER}/bin/drupal
  /home/${SUDO_USER}/bin/drupal init --override
  echo "source \$HOME/.console/console.rc 2>/dev/null" >> /home/${SUDO_USER}/.bashrc
  /home/${SUDO_USER}/bin/drupal check
  install_weekly_task 'drupal_console' "/home/${SUDO_USER}/bin/drupal self-update"
}

function install_composer() {

  echo 'Installing Composer...'
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php -r "if (hash_file('SHA384', 'composer-setup.php') === '92102166af5abdb03f49ce52a40591073a7b859a86e8ff13338cf7db58a19f7844fbc0bb79b2773bf30791e935dbd938') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
  php composer-setup.php --install-dir=/home/${SUDO_USER}/bin/ --filename=composer
  chmod +x /home/${SUDO_USER}/bin/composer
  php -r "unlink('composer-setup.php');"

  echo 'Adding weekly task to update Composer...'
  install_weekly_task 'composer' "/home/${SUDO_USER}/bin/composer self-update"
}

function install_weekly_task() {
  TASK_NAME=$1
  if [[ -z ${TASK_NAME} ]]; then
    echo 'Task name required!'
    exit 1;
  fi

  TASK_CONTENT=$2
  if [[ -z ${TASK_CONTENT} ]]; then
    echo 'Task content required!'
    exit 1;
  fi

  echo "#!/usr/bin/env bash" > /etc/cron.weekly/${TASK_NAME}
  echo "" >> /etc/cron.weekly/${TASK_NAME}
  echo ${TASK_CONTENT} >> /etc/cron.weekly/${TASK_NAME}

  chmod +x /etc/cron.weekly/${TASK_NAME}
}

function install_fs_inotify_fix() {
  # http://confluence.jetbrains.com/display/IDEADEV/Inotify+Watches+Limit
  # To check /proc/sys/fs/inotify/max_user_watches
  echo "fs.inotify.max_user_watches = 524288" > /etc/sysctl.d/20-wdh.conf
}

function install_wdh_requirements() {
  echo 'Installing WDH requirements...'
  /home/${SUDO_USER}/bin/composer install

  echo 'Changing owner of WDH configuration directory...'
  chown -R ${SUDO_USER}:${SUDO_USER} /home/${SUDO_USER}/.wdh

  PWD=${PWD##*/}
  mv ../$PWD /home/${SUDO_USER}/bin/webdevhelpers
  echo 'Creating WDH links...'
  ln -sf /home/${SUDO_USER}/bin/webdevhelpers/app/webdevhelper.php /home/${SUDO_USER}/bin/wdh
  ln -sf /home/${SUDO_USER}/bin/webdevhelpers/app/webdevhelper.php /home/${SUDO_USER}/bin/webdevhelper

  echo 'Changing owner of bin directory...'
  chown -R ${SUDO_USER}:${SUDO_USER} /home/${SUDO_USER}/bin
}
