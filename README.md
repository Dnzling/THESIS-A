<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Furniture Stores Platform Deployment (Hostinger)

### Quick Deploy Checklist

1. Build frontend assets locally:

```bash
pnpm install
pnpm run build
```

2. Create a deployment zip from project root and exclude paths listed in `.deployignore`.
3. Upload zip to Hostinger `public_html` (or your app directory), then extract.
4. Ensure your domain document root points to `public/`.
5. Create/update production `.env` on server (do not upload local `.env`).
6. Set correct write permissions for `storage/` and `bootstrap/cache/`.
7. Run Laravel optimization/migration commands (if SSH terminal is available):

```bash
php artisan migrate --force
php artisan optimize
```

### Git + SSH Deploy Flow (Recommended)

Local machine:

```bash
git add .
git commit -m "your update"
git push origin develop
```

Server (SSH):

```bash
cd ~/domains/yourdomain.com/public_html
chmod +x scripts/deploy.sh
./scripts/deploy.sh develop
```

The deploy script will pull latest code, install PHP dependencies, run migrations, optimize Laravel, and build assets if Node tooling exists on the server.

### Important Asset Note

`public/build` is currently ignored by git in this project. This is correct only if the server can run `pnpm run build` or `npm run build` during deploy.

If your server cannot build frontend assets, remove `/public/build` from `.gitignore` and commit built assets so `git pull` includes them.

### Uploaded Images 404 Fix (Production)

If images show in local but return 404 on live, usually one of these is missing:

1. `public/storage` symlink on server:

```bash
php artisan storage:link
```

2. Uploaded files are not present on server:

- Your uploads are stored in `storage/app/public/` and are not pushed by git.
- Sync/copy local uploaded files to server path `storage/app/public/`.

3. Production `.env` has wrong values:

- Set `APP_URL` to your real domain.
- Set `FILESYSTEM_DISK=public`.

### Include In Upload

- `app/`
- `bootstrap/`
- `config/`
- `database/`
- `public/` (must include `public/build/`)
- `resources/`
- `routes/`
- `storage/`
- `vendor/` (if not running composer on server)
- `artisan`
- `composer.json`
- `composer.lock`
- `vite.config.js`
- `.htaccess`

### Exclude From Upload

- `.git/`
- `.env` and local env variants
- `node_modules/`
- `tests/`
- `android/`
- `.gradle/`, `.pnpm-store/`
- SQL dumps (`*.sql`, `Dump*.sql`)
- temp/debug scripts (`_tmp_*.php`, `test_*.php`, `verify_*.php`, `tmp_*.txt`)

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
