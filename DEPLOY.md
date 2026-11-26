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
APP_TIMEZONE=America/New_York       # her local time zone (Eastern): dates and the 8:00 reminder follow it. Not the fixed "EST"
INERTIA_SSR_ENABLED=false           # no page-rendering service on this server
SESSION_LIFETIME=480                # stay logged in for 8 hours
SESSION_SECURE_COOKIE=true          # the site is https, so cookies are https-only

DB_CONNECTION=sqlite
DB_DATABASE=/home/forge/kennel-data/database.sqlite

MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxx               # Resend → API Keys, "Sending access"
MAIL_FROM_ADDRESS="hello@snowgoosekennels.com"   # must be on the domain verified in Resend
MAIL_FROM_NAME="Snow Goose Kennels"
```

Generate the app key from the Forge environment page or run `php artisan key:generate --force` once.

**Mail note.** New applications, the daily reminder and the litter update emails she sends to her waitlist (Litter → Waitlist tab) go out by email, and `MAIL_MAILER=log` only writes them
to a file. The site uses **Resend** over its HTTP API (the `resend/resend-php` package is installed), because
DigitalOcean blocks the usual SMTP ports. Before it will send, add the domain in Resend (Domains → Add Domain) and
put the DNS records it shows into the domain's DNS (DigitalOcean → Networking → Domains), then wait for it to
say Verified. The from address must be on that domain. Resend only sends; it does not give her a mailbox.
After changing `.env`, run `php artisan config:cache`.

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

**Photos and documents on Backblaze B2 (optional, recommended).** By default photos and documents live in this
server's folders. To keep them in Backblaze instead (cheap, off the droplet, and the droplet becomes disposable):

1. In Backblaze, create **two buckets**:
    - `snowgoose-photos`, set to **Public** (the website links straight to these, as it does to `/storage` today).
    - `snowgoose-documents`, set to **Private** (registration papers, agreements and the like; only the app, for a
      logged-in owner, ever reads them).
2. Create one **Application Key** (App Keys → Add) with read and write access to both buckets. Copy the keyID and
   the key now; the key is shown once.
3. Note the bucket's **S3 endpoint** and region from the bucket page (for example `s3.us-west-004.backblazeb2.com`,
   region `us-west-004`) and the **friendly URL** of the public bucket (`https://f004.backblazeb2.com/file/snowgoose-photos`).
4. Add to the site's environment, then `php artisan config:cache`:

```
PHOTO_DISK=b2_photos
DOCUMENT_DISK=b2_documents
B2_KEY_ID=...
B2_APPLICATION_KEY=...
B2_REGION=us-west-004
B2_ENDPOINT=https://s3.us-west-004.backblazeb2.com
B2_PHOTOS_BUCKET=snowgoose-photos
B2_PHOTOS_URL=https://f004.backblazeb2.com/file/snowgoose-photos
B2_DOCUMENTS_BUCKET=snowgoose-documents
```

5. Run `php artisan kennel:check`. **Photo storage** and **Document storage** should say OK (it writes and deletes a
   tiny test file in each bucket).
6. Copy what is already uploaded: `php artisan kennel:move-files`. It is safe to run again, and keeps the originals.
   Open a few photos and documents to confirm, then run `php artisan kennel:move-files --delete-source` to clear
   the old copies from the server.

New uploads go straight to Backblaze from then on. Nothing in the database changes (file names stay the same), so
switching back is just changing the two `*_DISK` values. With B2 in use, the `/storage/` caching block above no longer
matters (the photos carry their own long cache header), and the droplet only needs the database backed up below.

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

She can also download spreadsheets of dogs, puppies, contacts, the waitlist and expenses from **Records → Spreadsheets**
(and the expenses file from **Money**). A handy extra copy, but not a full backup (no photos).

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
- [ ] Send a test litter update: add yourself to a litter's waitlist with your own email, open the litter's
      **Waitlist** tab and use **Send an update by email**. Confirm it arrives and replies come to the kennel
      email. Replies go to the **Kennel owner** email under Website text (or the contact email if blank).
- [ ] Read the public **/privacy** page with her. It says only that the site collects what the application
      form asks, shares nothing, uses one form-security cookie, no analytics, and loads fonts from Bunny Fonts.
      Change the wording in `resources/js/pages/public/Privacy.vue` if any of that stops being true (for
      example if you add analytics or a mailing-list service).
- [ ] In **Website text → Buyer papers**, help her write how buyers pay, what the puppies eat, her vet, and any
      extra terms. Printed agreements and receipts contain only wording she wrote.
- [ ] Send past buyers the **/share-your-story** link. Their stories wait under Happy families for her OK, and an
      email (real mail settings needed) tells her when one arrives.
- [ ] Mark each stud's and dam's clearances **Show on the website** on the dog's Health tab, so they appear
      on that dog's public page under **Health clearances**.
- [ ] Check whether iPhone HEIC photos are supported (`kennel:check` says). If not, tell iPhone users to use
      Settings → Camera → Formats → Most Compatible.
- [ ] Submit `https://snowgoosekennels.example/sitemap.xml` in Google Search Console, and paste the home page
      into a Facebook post to check the share image.

## What was added on the `breeder-tools` branch

No new environment values and no new scheduled jobs. Deploying runs one new migration
(`create_breeder_tools_tables`: puppy schedule steps, expenses, litter update emails, and whelping-log columns
on puppies). The new features and where she finds them:

| Feature                                                                                                                 | Where                                           |
| ----------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------- |
| Puppy schedule (builds itself from the birth date, shows on the dashboard)                                              | Litter → Health & diary                         |
| Email everyone waiting for a litter                                                                                     | Litter → Waitlist                               |
| Go-home packet, sales agreement                                                                                         | Litter → Puppies → open a puppy                 |
| Deposit receipt                                                                                                         | Waitlist entry                                  |
| Stud agreement                                                                                                          | Litter → Breeding & AKC                         |
| Whelping log                                                                                                            | Litter → Puppies → open a puppy → Birth details |
| Money (expenses, yearly summary, per-litter results)                                                                    | Menu → Money                                    |
| AKC programs checklist                                                                                                  | Records → AKC programs                          |
| Mother's litter history                                                                                                 | Litter → Details, and the Pairing planner       |
| Privacy page; How it works on the Contact page; Happy families on the home page, with a story form at /share-your-story | Public site (Privacy in the footer)             |

Online payments are not built in. Buyers pay however she says (check, Venmo, Zelle). Her instructions print on
deposit receipts and sales agreements (**Website text → Buyer papers → How buyers pay**). A card-payment
integration would need a payment account (for example Stripe) and its keys.

## Updating later

Push to the branch and click **Deploy Now** (or turn on Quick Deploy). The deploy script runs migrations and
rebuilds the site. If you deploy by hand instead, run the same steps from the script in step 3.
