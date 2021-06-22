#!/bin/bash

cd /app
su application -c 'php artisan migrate --force'
