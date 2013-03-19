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

class InstallCommand extends Command
{
  protected function configure()
  {
    $description = 'Creates file to include Apache vhost definitions for your sandboxes. It must execute `sudo`, so '
      . 'be ready to inter your password.';
    $this
      ->setName('install')
      ->setDescription($description);
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln('Executed!');
  }
}
