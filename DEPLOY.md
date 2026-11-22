# Putting Snow Goose Kennels online (Laravel Forge + DigitalOcean)

The smallest DigitalOcean droplet Forge offers is plenty for this site. When you are done, run
`php artisan kennel:check` on the server (step 9). It lists what is still wrong, in plain words.

## 1. Create the server and site in Forge

1. **Servers → Create**: provider DigitalOcean, an App Server, **PHP 8.4** (8.3 also works), database
   **none needed** (the site uses SQLite). Pick the region closest to her buyers.
2. **New Site**: domain `snowgoosekennels.example`, project type Laravel, web directory `/public`.
3. Point the domain's DNS at the droplet, then **SSL → Let's Encrypt** (one click).
4. Connect the repository (or deploy another way, see the last section).

## 2. Keep the database outside the project folder

Forge's zero-downtime deployments swap the project folder on every deploy, and a SQLite file inside it would be
left behind. Put the file somewhere permanent, with SSH on the server:

```bash
mkdir -p /home/forge/kennel-data && touch /home/forge/kennel-data/database.sqlite
```

Then use that absolute path in `.env` (step 4). This is safe with or without zero-downtime deploys.

## 3. Deploy script (Site → Deployments)

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

npm ci
npm run build

$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan storage:link
$FORGE_PHP artisan optimize
```

Do **not** run `db:seed` on the live site: it adds demo dogs and a `test@example.com` login.
Forge servers include Node, so the build runs on the server. If you would rather not, build locally and commit or
upload `public/build` instead.

## 4. Environment (Site → Environment)

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://snowgoosekennels.example
APP_TIMEZONE=America/Boise          # her local time zone: dates and the 8:00 reminder follow it

DB_CONNECTION=sqlite
DB_DATABASE=/home/forge/kennel-data/database.sqlite

MAIL_MAILER=...                      # see the mail note below
MAIL_FROM_ADDRESS="hello@snowgoosekennels.example"
```

Generate the app key from the Forge environment page or run `php artisan key:generate --force` once.

**Mail note.** New applications and the daily reminder go out by email, and `MAIL_MAILER=log` only writes them
to a file. DigitalOcean blocks the usual SMTP ports on droplets (25, and often 465 and 587 on newer accounts), so
use a mail service with an HTTP API (Postmark, Resend, Mailgun or Amazon SES) or an SMTP port they allow, such as 2525. Each API mailer needs one small composer package; tell me which service she will use and I will wire it up.

## 5. PHP upload limits (the one that bites)

Phone photos are 3 to 8 MB and PHP's default limit is 2 MB, so uploads fail until this is raised.
In Forge: **Server → PHP → Max File Upload Size** (this sets Nginx and PHP together): `60` MB. Also raise
`memory_limit` to `512M` (Forge's **Edit PHP configuration**, then restart PHP). The admin uploads photos five at
a time, so these numbers are comfortable. `kennel:check` verifies all of it.

## 6. Create the owner login

Registration is switched off, so make the login yourself (Forge: **Server → SSH**, or the site's Commands tab):

```bash
cd /home/forge/snowgoosekennels.example && php artisan kennel:owner "Her Name" her@email.com
```

It asks you to type a password (at least 8 characters). Run it again with the same email to reset a forgotten
password. Every login receives the application alerts and the daily reminder, so add family members the same way.

## 7. Scheduler (daily reminder email)

Forge: **Site → Scheduler** (or **Server → Scheduler → New job**): command
`php8.4 /home/forge/snowgoosekennels.example/artisan schedule:run`, frequency **every minute**.
No queue worker is needed: emails are sent immediately.

## 8. Caching and backups

**Caching.** In **Site → Files → Edit Nginx Configuration**, add inside the `server` block so photos and built
files are cached by browsers:

```nginx
location /build/ { add_header Cache-Control "public, max-age=31536000, immutable"; try_files $uri =404; }
location /storage/ { add_header Cache-Control "public, max-age=2592000"; try_files $uri =404; }
```

(Built files have hashed names, and every photo gets a new name on upload, so long caching is safe.)

**Backups.** Forge's database backups only cover MySQL and Postgres, so they do **not** cover this SQLite
database or the photos. Everything she has entered lives in three places:

- `/home/forge/kennel-data/database.sqlite`
- `storage/app/public` (every photo; with zero-downtime deploys Forge keeps it in the site's shared storage)
- `storage/app/private` (her private documents: registration papers, pedigrees, OFA and DNA reports, agreements)

Do both of these:

1. Turn on **DigitalOcean droplet backups** (droplet → Backups). It covers the whole server, photos included.
2. Add a nightly copy off the server (Forge **Scheduler**, run as `forge`), for example to a DigitalOcean Space:

```bash
#!/bin/bash
# /home/forge/kennel-backup.sh   (needs: sudo apt install sqlite3 rclone, and an rclone remote for your Space)
set -e
DEST=/home/forge/backups/$(date +%F)
mkdir -p "$DEST"
sqlite3 /home/forge/kennel-data/database.sqlite ".backup '$DEST/database.sqlite'"   # safe while the site runs
rclone sync /home/forge/snowgoosekennels.example/storage/app/public space:kennel-backups/photos
rclone sync /home/forge/snowgoosekennels.example/storage/app/private space:kennel-backups/documents   # private papers
rclone copy "$DEST/database.sqlite" space:kennel-backups/db/$(date +%F)/
find /home/forge/backups -maxdepth 1 -mtime +14 -exec rm -rf {} +                   # keep two weeks locally
```

She can also download spreadsheets of dogs, puppies, contacts and the waitlist from **Website text → Download
your records**. A handy extra copy, but not a full backup (no photos).

## 9. Before you hand it over

```bash
cd /home/forge/snowgoosekennels.example && php artisan kennel:check
```

Everything should say OK. WARN lines are decisions (for example mail), FAIL lines must be fixed.

- [ ] Delete the demo user if it was ever created (`test@example.com`). The check fails until it is gone.
- [ ] Log in, open **Website text**, fill in phone, email, town and Facebook.
- [ ] Upload a few real photos. Photos added in the **Photos** library are private until she ticks
      "Show on website".
- [ ] Settle the policy text with her (deposits, go-home age, health guarantee, contracts). The site never
      invents policies, so those sections stay hidden until she writes them.
- [ ] Submit a test application at `/apply` and confirm the email arrives.
- [ ] Check whether iPhone HEIC photos are supported (`kennel:check` says). If not, tell iPhone users to use
      Settings → Camera → Formats → Most Compatible.
- [ ] Submit `https://snowgoosekennels.example/sitemap.xml` in Google Search Console, and paste the home page
      into a Facebook post to check the share image.

## Updating later

Push to the branch and click **Deploy Now** (or turn on Quick Deploy). The deploy script runs migrations and
rebuilds the site. If you deploy by hand instead, run the same steps from the script in step 3.
