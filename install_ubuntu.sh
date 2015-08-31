#!/bin/bash

# install node.js
sudo apt-get install nodejs-legacy -y

# get composer
curl -sS https://getcomposer.org/installer | php

# install composer dependencies
php composer.phar install
