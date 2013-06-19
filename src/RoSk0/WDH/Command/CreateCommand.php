<?php
/**
 * @file
 * Create sandbox command.
 */

namespace RoSk0\WDH\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class CreateCommand extends Command {

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
      ->setName('create')
      ->setDescription('Creates directory, Apache vhost and DB for new sandbox.')
      ->addArgument('name', InputArgument::REQUIRED, 'Name of sandbox to create. Note that it shouldn\'t contain ".dev" suffix.')
      ->addOption('nodb', NULL, InputOption::VALUE_NONE, 'Use this option to disable creation of DB for sandbox. ');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->setSandboxName($input->getArgument('name'));
    $noDB = $input->getOption('nodb');
    $fs = new Filesystem();
    try {
      $fs->mkdir($this->getSandboxDir());
    } catch (IOException $e) {
      $output->write('<error>An error occurred while creating sandbox directory: </error>');
      $output->writeln($this->getSandboxDir());
    }
    if(!$noDB){
      $my_cnf = parse_ini_file($this->user_home . '/.my.cnf', NULL, INI_SCANNER_RAW);
      if($my_cnf) {
        $link = mysqli_connect('localhost', $my_cnf['user'], $my_cnf['password']);
        if (!mysqli_query($link, 'CREATE DATABASE IF NOT EXISTS `' . $this->getSandboxName() . '`')) {
          $output->writeln('<error>An error occurred while creating database for sandbox.</error>');
          throw new \Exception('An error occurred while creating database for sandbox.');
        }
      }
      else{
        $output->writeln('<error>An error occurred reading database configuration file.</error>');
        throw new \Exception('An error occurred reading database configuration file.');
      }
    }
    $this->createHTTPDConf($output);
    system('sudo service apache2 reload');
  }

  protected function setSandboxName($name) {
    // @TODO: Add validation.
    $this->sanboxName = $name;
  }

  protected function createHTTPDConf(OutputInterface $output) {
    $name = $this->getHTTPDConfDir() . '/' . $this->getSandboxName() . '.conf';
    $configFile = new \SplFileObject($name,'w');
    $ret = $configFile->fwrite($this->apacheConfig());
    if ($ret) {
      $output->writeln('HTTPD conf written successfully.');
    }
    else {
      $output->writeln('<error>An error occurred while creating HTTPD config for sandbox.</error>');
    }
  }

  protected function apacheConfig() {
    $template = $this->apacheConfigTemplate();
    $replacement = array(
      '[sandboxname]' => $this->getSandboxName(),
      '[sandbox_dir]' => $this->getSandboxDir(),
    );
    return strtr($template,$replacement);
  }

  protected function apacheConfigTemplate() {
    return "<VirtualHost *:80>
  ServerName [sandboxname]
	ServerAdmin webmaster@localhost

	DocumentRoot [sandbox_dir]
  <Directory [sandbox_dir]>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		allow from all
  </Directory>

  # Possible values include: debug, info, notice, warn, error, crit,
  # alert, emerg.
  LogLevel warn
  #CustomLog [sandbox_dir]/access.log combined
  #ErrorLog  [sandbox_dir]/error.log
</VirtualHost>
";
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
