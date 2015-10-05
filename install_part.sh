#!/bin/bash

echo 'Installing Composer...'
curl -sS https://getcomposer.org/installer | tail -n +2 | php -- --quiet
