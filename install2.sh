#!/bin/bash
ln -vfs /home/$SUDO_USER/config/apache.conf /etc/apache2/conf.d/99-aegir.conf
#echo 'umask 022' >> /home/$USER/.bashrc
source /home/$USER/.bashrc
#mysql_secure_installation
drush dl --destination=/home/$USER/.drush provision-6.x
drush hostmaster-install --web_group=$USER
