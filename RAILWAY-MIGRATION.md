# admincart: move from Laravel Cloud to Railway

What this app needs (checked in the repo):
- PHP 8.2 or 8.3 (composer.lock needs 8.2+; Laravel 10 supports up to 8.3). composer.json now pins `>=8.2 <8.4` and lists the PHP extensions the packages need (gd, zip, intl, pdo_mysql, bcmath).
- MySQL (`DB_CONNECTION=mysql`).
- Files on the `s3` disk (images, section videos, nightly DB backups in `db-backups/`).
- Scheduler: `backup:database` daily 02:00 and `queue:work --stop-when-empty` every minute. So one cron service covers the scheduler and the queue.
- Pusher (live order events), Stripe/Cashier, JWT, GeoIP file in `storage/app/geoip` (in the repo, nothing to do).
- TrustProxies already trusts `*`, so HTTPS behind Railway's proxy works.

## 1. Before you start, copy from Laravel Cloud
- Every environment variable (Cloud › Environment › Variables). Keep `APP_KEY` and `JWT_SECRET` exactly the same, or passwords reset links, encrypted values and customer logins break.
- The database connection details (host, port, user, password, database).
- The bucket's `AWS_*` values (key, secret, bucket, region, `AWS_ENDPOINT`, `AWS_URL`).

## 2. Railway project: 3 services + MySQL

| Service | Source | Settings |
|---|---|---|
| **MySQL** | Railway MySQL database | — |
| **web** | GitHub `truewebpro/admincart`, branch main | Pre-deploy command: `bash railway/init-app.sh`. Generate a domain. Healthcheck path `/` (optional). |
| **cron** | Same repo and branch | Start command: `bash railway/run-cron.sh`. No domain. Don't add the pre-deploy command (web runs migrations). |

Railway builds the app with Railpack: it detects Laravel, installs PHP from composer.json, runs `composer install` and `npm run build` (Vite), and serves `public/`.

## 3. Variables (set on web and cron; use a shared variable group or copy both)

```
APP_NAME=...                 # from Cloud
APP_ENV=production
APP_KEY=...                  # SAME as Cloud
APP_DEBUG=false
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}   # web only; later your real domain
LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

QUEUE_CONNECTION=database    # whatever Cloud used; must NOT be sync for the cron worker to matter
CACHE_STORE=database         # file also works (one cron container); database is safer across web replicas
SESSION_DRIVER=database      # or cookie; not file, the disk is wiped on every deploy

JWT_SECRET=...               # SAME as Cloud
STRIPE_KEY / STRIPE_SECRET / STRIPE_WEBHOOK_SECRET / CASHIER_CURRENCY
PUSHER_APP_ID / PUSHER_APP_KEY / PUSHER_APP_SECRET / PUSHER_APP_CLUSTER
BROADCAST_DRIVER=pusher
MAIL_* (Mailtrap etc.)
AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY / AWS_DEFAULT_REGION / AWS_BUCKET / AWS_ENDPOINT / AWS_URL / AWS_USE_PATH_STYLE_ENDPOINT
SANCTUM_STATEFUL_DOMAINS=... # admin domain(s)
```

`VITE_PUSHER_*` values are read at build time, so they must be set before the first deploy.
If the session/cache/queue tables don't exist yet: `php artisan session:table`, `cache:table`, `queue:table` (check `database/migrations` first).

## 4. Files (the s3 disk)
- If the bucket is **your own** (AWS S3, Cloudflare R2): keep the same `AWS_*` values. Nothing to move.
- If it is a **Laravel Cloud bucket**: it goes away with the Cloud environment. Create your own Cloudflare R2 (or S3) bucket with public access, copy everything across with `rclone sync cloud:bucket r2:bucket`, then set the new `AWS_*` values. File paths in the database stay the same; only `AWS_URL` changes. (Railway's own buckets are private, so product images would need signed URLs. R2/S3 is the simpler choice here.)

## 5. Move the database
1. Put Cloud in maintenance mode or stop writes (no orders being placed).
2. Dump from Cloud:
   `mysqldump -h CLOUD_HOST -P PORT -u USER -p --single-transaction --routines --triggers --no-tablespaces DB > admincart.sql`
3. In Railway, MySQL › Connect › copy the **public** connection command, then:
   `mysql -h PUBLIC_HOST -P PUBLIC_PORT -u root -p railway < admincart.sql`
4. Spot-check row counts (orders, products, customers, users) against Cloud.
5. Deploy web. `migrate --force` should report "Nothing to migrate".

## 6. Test on the Railway domain
Admin login, storefront API with JWT, product image upload (lands in the bucket), a Stripe test payment and webhook, a Pusher order event, Mailtrap email. Cron logs show `schedule:run` every minute; run `php artisan backup:database` once in the cron service shell.

## 7. Cut over
- Railway › web › Settings › Networking › Custom domain › add your domain, then point the DNS CNAME to the target Railway shows. Wait for the certificate.
- Update `APP_URL`, `SANCTUM_STATEFUL_DOMAINS` and the Next.js storefronts' API URL if it changes.
- Update the Stripe webhook endpoint URL (Stripe dashboard) and Sendcloud webhook URL to the new domain.
- Keep the Cloud environment (stopped) and its database for a few days, then delete.
