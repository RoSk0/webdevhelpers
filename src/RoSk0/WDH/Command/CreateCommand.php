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

class CreateCommand extends Command {

  protected function configure() {
    $this
      ->setName('create')
      ->setDescription('Creates directory, Apache vhost and DB for new sandbox.')
      ->addArgument('name', InputArgument::REQUIRED, 'Name of sandbox to create. Note that it shouldn\'t contain ".dev" suffix.')
      ->addOption('nodb', NULL, InputOption::VALUE_NONE, 'Use this option to disable creation of DB for sandbox. ');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $name = $input->getArgument('name');
    $output->writeln($name);
    var_dump($input->getOption('nodb'));
  }
}
