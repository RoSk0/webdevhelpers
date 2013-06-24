<?php
/**
 * @file
 * Delete sandbox command.
 */

namespace RoSk0\WDH\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class DeleteCommand extends Command {

  /**
   * @var string Sandbox name.
   */
  private $sanboxName;
  private $dir;
  private $suffix;
  private $user;
  private $user_home;
  private $wdh_dir;
  private $vhost_dir;


  function __construct($name = NULL) {
    parent::__construct($name = NULL);
    $this->user = $_SERVER['USER'];
    $this->user_home = $_SERVER['HOME'];
    $config = parse_ini_file($this->user_home . '/.wdh/config');
    $this->dir = $this->user_home . '/' . $config['webroot'];
    $this->suffix = '.dev';
    $this->wdh_dir = '.wdh';
    $this->vhost_dir = 'vhost';
  }

  protected function configure() {
    $this
      ->setName('delete')
      ->setDescription('Deletes directory, Apache vhost and DB for sandbox.')
      ->addArgument('name', InputArgument::REQUIRED, 'Name of sandbox to delete. Note that it shouldn\'t contain ".dev" suffix.')
      ->addOption('db', NULL, InputOption::VALUE_NONE, 'Use this option to clear DB for sandbox. Content of sandbox directory will remain unchanged.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setSandboxName($input->getArgument('name'));
    $db = $input->getOption('db');
    $my_cnf = parse_ini_file($this->user_home . '/.my.cnf', NULL, INI_SCANNER_RAW);
    if(!$my_cnf) {
      throw new \Exception('An error occurred reading database configuration file.');
    }

    if ($db) {
      $link = mysqli_connect('localhost', $my_cnf['user'], $my_cnf['password']);
      if (!mysqli_query($link, 'DROP DATABASE `' . $this->getSandboxName() . '`')) {
        throw new \Exception('An error occurred while creating database for sandbox.');
      }
      if (!mysqli_query($link, 'CREATE DATABASE `' . $this->getSandboxName() . '`')) {
        throw new \Exception('An error occurred while creating database for sandbox.');
      }
    }
    else {
      $link = mysqli_connect('localhost', $my_cnf['user'], $my_cnf['password']);
      if (!mysqli_query($link, 'DROP DATABASE `' . $this->getSandboxName() . '`')) {
        throw new \Exception('An error occurred while creating database for sandbox.');
      }
      $fs = new Filesystem();
      try {
        $fs->remove($this->getSandboxDir());
      } catch (IOException $e) {
        $output->write('<error>An error occurred while removing sandbox directory: </error>');
        $output->writeln($this->getSandboxDir());
      }
      $conf_name = $this->getHTTPDConfDir() . '/' . $this->getSandboxName() . '.conf';
      try {
        $fs->remove($conf_name);
        system('sudo service apache2 reload');
      } catch (IOException $e) {
        $output->write('<error>An error occurred while removing sandbox HTTPd config file: </error>');
        $output->writeln($conf_name);
      }
    }
  }

  protected function setSandboxName($name) {
    $this->sanboxName = $name;
  }

  protected function getSandboxDir() {
    return $this->dir . '/' . $this->getSandboxName();
  }

  protected function getSandboxName() {
    return $this->sanboxName . $this->suffix;
  }

  protected function getHTTPDConfDir() {
    return $this->user_home. '/' . $this->wdh_dir . '/' . $this->vhost_dir;
  }
}
