#!/bin/bash
# Railway pre-deploy command for the web service.
# Runs once per deploy, before traffic switches to the new version.
# Set in Railway: Settings > Deploy > Pre-deploy command:
#   bash railway/init-app.sh
set -e

php artisan migrate --force
php artisan optimize:clear
