#!/bin/bash
# Railway start command for the "cron" service (same repo, no public domain).
#   bash railway/run-cron.sh
#
# Runs Laravel's scheduler every minute. app/Console/Kernel.php already
# starts `queue:work --stop-when-empty` every minute and the daily
# `backup:database` at 02:00, so this one service covers both the
# scheduler and the queue that Laravel Cloud ran for you.

mkdir -p storage/framework/{cache,sessions,views} storage/logs storage/app/backups

while true; do
    php artisan schedule:run --verbose --no-interaction &
    sleep 60
done
