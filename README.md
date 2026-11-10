# Luxe Vault

A mobile-first warehouse application for interior designers. Laravel 12, Filament 5, Livewire, Spatie roles and permissions, and a custom gold/black/white theme.

## Run locally

Requirements: PHP 8.3+ with Laravel's standard extensions plus intl, mbstring, PDO SQLite (or PDO MySQL), XML/DOM; Composer 2; Node 22+.

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan filament:assets
npm ci
npm run build
php artisan luxe:superadmin
php artisan serve
```

Visit `/` for the landing page and `/admin` for the application. Public registration always creates an active **Free warehouse owner**. Password resets require a working mail transport; the local default logs email. Set `APP_URL` to your actual HTTPS origin before sending reset links.

No demo users, fixed passwords, or privileged public registration. The superadmin command prompts for a password without echoing it. Roles are installed by `DatabaseSeeder`; run it before registering users.

## Included

- Warehouse CRUD, with account ownership and address/notes.
- Two-level inventory organization: **project → room/custom group**. A project belongs to one warehouse. Items may remain ungrouped.
- Item CRUD: category, location, condition, availability, quantity, threshold, cost, value, notes, automatic SKU and immutable QR identity.
- Optional manufacturer barcode, printable Code 128 SKU barcode and QR label. Repeated manufacturer barcodes are allowed; SKU and internal QR identity are unique.
- Three-step quick add: identify → describe → place. ZXing camera scanning is loaded on demand, with manual entry, camera error handling, and cleanup on component destruction/backgrounding. Scans only look up accessible items. An existing match warns without automatically changing stock.
- Dashboard with quantity/value/cost aggregates, warehouse counts, category and status breakdowns, recent inventory events, and a searchable/filterable editable item table.
- Owner/staff administration, active-account control, warehouse assignments, and superadmin plan management.
- One-page marketing site, Free/Premium comparison, login, registration, password reset, and profile editing.
- Self-hosted fonts, rounded controls, responsive layouts, mobile bottom navigation, and reduced-motion support.

## Roles and plans

| Role | Scope |
| --- | --- |
| Superadmin | All accounts and warehouses; create owners/staff/admins; manage plans |
| Warehouse owner | Own warehouses and their projects/groups/items; own guest staff |
| Guest staff | Inventory operations in explicitly assigned warehouses belonging to their owner; cannot create warehouses, manage people, or delete records |

Free: **one warehouse, zero guest accounts**. Premium: multiple warehouses and guest accounts (no additional numeric cap in this foundation). Owners cannot upgrade themselves. Superadmins activate Premium in People; there is no checkout or recurring billing yet, and no invented Premium price on the landing page. A downgrade is blocked while multiple warehouses or active guests remain. Guest access also stops if the owner is inactive or no longer Premium.

Currency is application-wide (`LUXE_CURRENCY=USD` by default). Item cost/value are unit amounts; dashboard totals multiply them by quantity. Mixed currencies are not supported.

## Security and architecture

Resource queries, relationship options, scan lookups, dashboard aggregates, and labels are scoped by ownership/membership. Laravel policies authorize individual records. `InventoryWriter` and `AccountWriter` validate submitted IDs and enforce plan rules inside transactions. Owner-row locking serializes warehouse creation. Spatie defines the permission sets; platform role and owner identity are immutable after account creation in the UI.

Use these services for future write integrations. Do not bypass them with raw model writes in HTTP endpoints. Foreign keys restrict deleting populated warehouses/projects/groups. Cross-warehouse group assignments are rejected. Inventory events record item additions, changes, and deletions. IDs and QR values identify records; they do not grant access.

`/items/{item}/label` requires authentication and record authorization. Staff/owner activation is checked by persistent panel middleware, including Livewire requests. Generic administrative bulk deletion is disabled.

## Production

- Serve the `public/` directory over HTTPS; set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, secure session settings, database credentials and mail transport.
- Use MySQL/Postgres for concurrent production teams. SQLite is convenient for local development and tests.
- Run `composer install --no-dev --optimize-autoloader`, `php artisan migrate --force`, `php artisan db:seed --force`, `php artisan filament:assets`, `npm ci`, `npm run build`, and `php artisan optimize` during deployment.
- Make `storage/` and `bootstrap/cache/` writable. Back up the database and retain `APP_KEY` securely.
- Camera access requires HTTPS (localhost is also supported). Test real iOS Safari and Android Chrome camera permission, focus, and scanning before rollout.
- No AI, billing provider, offline synchronization, label printer driver, or third-party warehouse integration is implemented in this foundation.

## Validation

```sh
php artisan test
vendor/bin/pint --test
npm run build
```

Feature tests cover Free quotas, guest restrictions, foreign IDs, group/warehouse consistency, label authorization, inactive accounts, downgrade constraints, registration, resource rendering, Filament create/edit actions, and quick add. GitHub Actions runs the PHP suite and asset build.
