<?php
$short_opts  = '';
$short_opts .= 'n:'; // Required value: sandbox name.
$short_opts .= 'd';
$short_opts .= 'h';

$long_opts = array(
  'nodb',
  'name:',
  'delete',
  'help'
);

$cli_opts = getopt($short_opts, $long_opts);

if (empty($cli_opts) || isset($cli_opts['h']) || isset($cli_opts['help'])) {
  print_help();
  exit;
}
if (empty($cli_opts['n']) && empty($cli_opts['name'])) {
  print_error('You must specify sandbox name with "-n" or "--name"!');
  exit(1);
}
$name = isset($cli_opts['name']) ? $cli_opts['name'] : $cli_opts['n'];
$sandbox = new WebDevHelper();
$sandbox->setSandboxName($name);
if (isset($cli_opts['d']) || isset($cli_opts['delete'])) {
  print_msg('Deleting sandbox: ' . $name);
  $sandbox->removeSandbox();
  exit;
}
if (isset($cli_opts['nodb'])){
  $sandbox->noDB();
}

$sandbox->createSandbox();









class WebDevHelper {
  private $name;
  private $dir;
  private $suffix;
  private $dbName;

  public function __construct() {
    $this->dir = '/home/rosko/websites';
    $this->suffix = '.dev';
  }

  public function setSandboxName($name) {
    if (strpos($name, ' ') == FALSE) {
      $this->name = $this->dbName = $name;
    }
    else {
      print_error('There must be NO SPACES in sandbox name.');
      exit(1);
    }
  }

  public function createSandbox() {
    $this->createSandboxDir($this->getSandboxDir());
    if ($this->dbName) {
      $this->createDB($this->getSandboxName());
    }
    $this->createHTTPDConf();
    $this->enableSandbox();
  }

  public function createSandboxDir() {
    if (!is_dir($this->getSandboxDir())) {
      system('mkdir -p ' . $this->getSandboxDir(), $ret);
      if ($ret != 0) {
        print_error('Fatal error!!! Could not create directory for sandbox.');
        exit(1);
      }
      else {
        print_msg('Directory "' . $this->getSandboxDir() . '" successfully created.');
      }
      $ret = chown($this->getSandboxDir(), $_SERVER['SUDO_USER']);
      if ($ret) {
        print_msg('Owner for directory "' . $this->getSandboxDir() . '" changed to "' . $_SERVER['SUDO_USER'] .'"');
      }
      else {
        print_error('Owner for directory "' . $this->getSandboxDir() . '" didn\'t changed.');
      }
    }

  }

  public function createDB() {
    system('mysqladmin create ' . $this->getSandboxName(), $ret);
    if ($ret != 0) {
      print_error('Fatal error!!! Could not create DB for sandbox.');
      exit(1);
    }
    else {
      print_msg('DB "' . $this->getSandboxName() . '" successfully created.');
    }
  }

  public function createHTTPDConf() {
    $name = '/etc/apache2/sites-available/' . $this->getSandboxName();
    $configFile = new SplFileObject($name,'w');
    $ret = $configFile->fwrite($this->apacheConfig());
    if ($ret) {
      print_msg('HTTPD conf written successfully.');
    }
    else {
      print_error('Could not write HTTPD config.');
    }
  }

  public function getWebSitesDir() {
    return $this->dir;
  }

  public function getSandboxDir() {
    return $this->dir . '/' . $this->name . $this->suffix;
  }

  public function getSandboxName() {
    return $this->name . $this->suffix;
  }


  public function apacheConfig() {
    $template = $this->apacheConfigTemplate();
    $replacement = array(
      '[sandboxname]' => $this->getSandboxName(),
      '[sandbox_dir]' => $this->getSandboxDir(),
    );
    return strtr($template,$replacement);
  }

  public function apacheConfigTemplate() {
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

  public function noDB() {
    $this->dbName = FALSE;
  }

  public function enableSandbox() {
    system('a2ensite ' . $this->getSandboxName(), $ret);
    if ($ret != 0) {
      print_error('Fatal error!!! Could not enable sandbox config in HTTPD.');
      exit(1);
    }
    else {
      print_msg('Site "' . $this->getSandboxName() . '" successfully enabled.');
    }
    system('service apache2 reload', $ret);
    if ($ret != 0) {
      print_error('Fatal error!!! Could not reload HTTPD configs.');
      exit(1);
    }
    else {
      print_msg('HTTPD config successfully reloaded.');
    }
  }

  public function removeSandbox() {
    $this->disableSandbox();
    $this->removeHTTPDConf();
    $this->removeDB();
    $this->removeSandboxDir();
  }

  public function disableSandbox() {
    system('a2dissite ' . $this->getSandboxName(), $ret);
    if ($ret != 0) {
      print_error('Fatal error!!! Could not disable sandbox config in HTTPD.');
    }
    else {
      print_msg('Site "' . $this->getSandboxName() . '" successfully disabled.');
    }
    system('service apache2 reload', $ret);
    if ($ret != 0) {
      print_error('Fatal error!!! Could not reload HTTPD configs.');
    }
    else {
      print_msg('HTTPD config successfully reloaded.');
    }
  }

  public function removeHTTPDConf() {
    $name = '/etc/apache2/sites-available/' . $this->getSandboxName();
    $ret = unlink($name);
    if ($ret) {
      print_msg('Config for sandbox "' .$this->getSandboxName() . '" successfully removed.');
    }
    else {
      print_error('Could not delete config for sandbox "' .$this->getSandboxName() . '".');
    }
  }

  public function removeDB() {
    system('mysqladmin drop -f ' . $this->getSandboxName(), $ret);
    if ($ret != 0) {
      print_error('Fatal error!!! Could not remove DB for sandbox "' .$this->getSandboxName() . '".');
      }
  }

  public function removeSandboxDir() {
    system('rm -rf ' . $this->getSandboxDir(), $ret);
    if ($ret != 0) {
      print_error('Fatal error!!! Could not remove directory for sandbox "' .$this->getSandboxName() . '".');
      exit(1);
    }
    else {
      print_msg('Directory "' . $this->getSandboxDir() . '" successfully deleted.');
    }
  }

}

function print_help() {
  print_msg('HELP');
  print_msg('HELP');
  print_msg('HELP');
  
  //var_dump($_SERVER['SCRIPT_NAME']);
}

function print_error($message) {
  print('ERROR: ' . $message . PHP_EOL);
}

function print_msg($message) {
  print($message . PHP_EOL);
}
