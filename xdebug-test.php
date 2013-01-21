<?php
  $addr = '127.0.0.1';
  $port = 9000;
  $socket = socket_create(AF_INET, SOCK_STREAM, 0);
  socket_bind($socket,$addr,$port) or die("Can't bind socket!");
  socket_listen($socket);
  $client = socket_accept($socket);
  echo "Connection established: $client";
  socket_close($client);
  socket_close($socket);
