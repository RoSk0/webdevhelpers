# Web developer help tools

## Description


This is web developer helper(WDH) tools. It would be useful in situations when
you just install Ubuntu(and others in future) system and have no time for
tuning it.
It will install and configure LAMP stack, particularly Apache web-server, MySQL
server, PHP, drush and composer.

## What it provides


### Some configuration

* SUDO — set `sudo` not to ask for your pass.
* MySQL — configures MySQL not to ask for root pass for local server and configures prompt for client to show user, host
  and database to which you are connected
* PHP — too much to describe, take a look yourself:


    memory_limit = 1G
    post_max_size = 128M
    upload_max_filesize = 128M
    max_execution_time = 300
    max_input_time = 300
    date.timezone = UTC
    xdebug.remote_enable = 1
    xdebug.cli_color = 1
    xdebug.coverage_enable = 0
    disable_functions =
    display_errors = On
    error_reporting = E_ALL
    session.gc_maxlifetime = 86400
    apc.shm_size = 256M

* Apache — configurable docroot inside you $HOME and same level of access to it for you and Apache process so no need in
  `sudo`, also virtual host configuration files will live in $HOME/.wdh/vhost

### Utilities

* DNSmasq — this utility will redirect all request to `*.dev` hosts to `localhost` and also will be a local DNS cache
  for you
* Drush — will help to interact with Drupal sites, also set to be updated on weekly basis
* Drupal console — "The new CLI for Drupal. A tool to generate boilerplate code, interact with and debug Drupal.", also
  set to be updated on weekly basis
* Composer — PHP dependency manager, also set to be updated on weekly basis
* vim — console text editor
* git — version control system. Installed together with GUI to help blame people
* diffuse — great tool to visually compare code differences, also could be used as diff &
  [merge tool](https://git-scm.com/docs/git-mergetool) by default for git
* geany - simple yet powerful text editor which could be extended with plugins


## Installation

* Download and archive for you version of Ubuntu.
* Unpack it somewhere
* Open terminal and navigate to it
* Run `sudo ./install.sh`

That's it, you are ready to create you first development website with
webdevhelper, just type wdh or webdevhelper in console to get help.

## Usage

* `wdh create test` — will create a virtual host `test.dev` in Apache and `test.dev` MySQL DB
* `wdh delete test` — will delete what was created
