#!/usr/bin/env php
<?php

$file = new SplFileObject('test_mail.log', 'w');
$input = '';
while ($str = fgets(STDIN)) {
  $input .= $str;
}
$file->fwrite($input);