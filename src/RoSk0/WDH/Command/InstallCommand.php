<?php
/**
 * @file
 * Installs apache include file command.
 */

namespace RoSk0\WDH\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class InstallCommand extends Command {

  protected function configure() {
    $description = 'Creates file to include Apache vhost definitions for your sandboxes. It must execute `sudo`, so '
      . 'be ready to inter your password.';
    $this
      ->setName('install')
      ->setDescription($description);
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $shell = $_SERVER['SHELL'];
    $user = $_SERVER['USER'];
    $user_home = $_SERVER['HOME'];
    $wdh_dir = '.webdevhelper';
    $vhost_dir = 'vhost';
    $deb_conf_path = '/etc/apache2/conf.d';
    $fs = new Filesystem();
    if ($fs->exists($deb_conf_path)) {
      try {
        $conf_dir = $user_home . '/' . $wdh_dir . '/' . $vhost_dir;
        $fs->mkdir($conf_dir);
        $output->writeln(array(
          '<info>Configuration directory:</info>',
          "\t" . $conf_dir,
          '<info>was successfully created.</info>',
        ));
      } catch (IOException $e) {
        $output->writeln('<error>An error occurred while creating config directories.</error>');
      }
      system("sudo $shell -c 'echo \"Include $conf_dir/*.conf\" > " . $deb_conf_path . '/webdevhelper\'');
      $output->writeln('<comment>You shouldn\'t see any error messages above!</comment>');
      $output->writeln('<comment>If there was an error message, you shouldn\'t rely on WDH correct work.</comment>');
      system('sudo service apache2 restart');
      $output->writeln('<info>Congratulations! Now you are ready to create your first development sandbox with '
        . '"create" command.</info>');
      $output->write("<info>Check command help with: </info>");
      $output->writeln('webdevhelper help create');
    }
  }
}
