#!/bin/bash

set -e

echo; echo "updating composer dependencies"
/usr/local/bin/composer.phar install --prefer-dist --no-progress

echo; echo "copying sample environment"
cp -v .env.example .env

