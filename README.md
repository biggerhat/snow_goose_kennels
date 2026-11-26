# Snow Goose Kennels

Website and private admin for a small family-run Labrador retriever kennel (black, chocolate and yellow Labs).

- **Public site:** home, litters, our dogs, gallery, contact (with how it works), puppy application, share-your-story form, privacy.
- **Admin (phone-first):** dogs with private and public fields and health tests, litters and puppies, contacts with a conversation log, waitlist with pick order and deposits, photo library, puppy schedule, expenses, AKC checklists and forms, printable agreements and receipts, email updates to a litter's waitlist.

Built with Laravel 13, SQLite, Inertia v3, Vue 3, TypeScript and Tailwind 4. Tests use Pest.

## Run it locally

Needs PHP 8.4+, Composer and Node.

```bash
composer run setup            # installs, creates .env, key, migrates, builds assets
php artisan db:seed --class=DemoSeeder   # demo dogs and litters (local only)
composer run dev              # dev server and Vite
```

The demo login is `test@example.com` / `password`. Never seed or use that login in production.

## Everyday commands

| Task | Command |
| --- | --- |
| Tests | `php artisan test --compact` |
| PHP style | `vendor/bin/pint` |
| JS lint and format | `npx vp check --fix` |
| Type check | `npx vue-tsc --noEmit` |
| Regenerate typed routes | `php artisan wayfinder:generate --with-form` |

Kennel commands (run on the server): `kennel:check` (setup health), `kennel:owner` (create or reset the owner login),
`kennel:move-files` (copy photos and documents to Backblaze B2), `kennel:reminders` (daily reminder email).

## Deploying

Hosting is Laravel Forge on a DigitalOcean droplet. Environment values, upload limits, the scheduler, Resend email,
Backblaze B2 storage and backups are all in [DEPLOY.md](DEPLOY.md).

## Things to know before changing code

- The owner is not technical and uses a phone. Keep tap targets at least 44px and keep the admin help
  (Help page, page help, tooltips) in step with any feature change.
- Public pages only show a whitelist of fields. Registration numbers, microchips, private notes and documents must never be sent to visitors.
- The site never invents policy wording. Policies come from the editable Website text page.
- Registration is switched off. Logins are created with `php artisan kennel:owner`.

## Security

- Secrets live only in `.env` on the server (never committed). `.env.example` lists every key with no values.
- Report a suspected vulnerability privately to the repository owner rather than opening a public issue.
