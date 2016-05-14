#!/usr/bin/env bash

ROOT_UID="0"

# Check if run as root.
if [[ "$UID" -ne "$ROOT_UID" ]] ; then
  echo "In order to install WDH you MUST run this command with 'sudo'."
  exit 1
fi


#########################
#######  CONF  ##########
#########################
TZ=UTC

source functions.sh

echo 'This is WEB developer helper(WDH) tool.'
echo 'WDH will install and configure LAMP stack, particularly Apache webserver,'
echo 'MySQL server, and PHP.'
read -p 'Press ANY key to continue or Ctrl+C to exit.'

install_fs_inotify_fix
install_sudo_config
install_weekly_tasks
install_directories
apt-get update
install_mysql
install_php
install_apache
install_dnsmasq
install_additional_helpers
install_drush
install_drupal_console
install_composer
install_wdh_requirements
