#!/bin/bash

if [ -f wait-for-it.sh ]; then
  ./wait-for-it.sh ${MARIADB_HOST}:3306 -t 0 -- php vendor/bin/doctrine orm:schema-tool:create

  rm wait-for-it.sh cli-config.php
fi

php -S 0.0.0.0:8080