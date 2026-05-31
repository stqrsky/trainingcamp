![img](public/assets/images/tc-trainingcamp.jpg)

## Local development (quick start)

Requirements: **PHP 8.0+**, **Composer**, **Node.js 18+**, **npm**.

```bash
composer install
npm install
cp .env.example .env   # skip if .env already exists
php artisan key:generate
touch database/database.sqlite   # SQLite — no MySQL required locally
php artisan migrate --seed
npm run dev                    # compile CSS/JS (once, or use `npm run watch`)
php artisan serve              # http://127.0.0.1:8000
```

| Page | URL |
|------|-----|
| Sign up | http://127.0.0.1:8000/signup |
| Sign in | http://127.0.0.1:8000/login |

After `db:seed`, demo users use password **`secret`**.

One-command setup (after `composer install`):

```bash
composer setup && npm install && npm run dev
```

**MySQL instead of SQLite:** set `DB_CONNECTION=mysql` and database credentials in `.env`, create the database, then run `php artisan migrate --seed`.

Run tests: `composer test` or `php vendor/bin/phpunit`

---

## About "Training Camp"

This is my graduation project from the web development bootcamp at neuefische Hamburg.

The Trainingcamp App has been developed to make the club life in a sport-bootcamp easier for you. Use the Trainingcamp App to get a quick overview of your member list or plan sparring assignments of athletes anytime and anywhere.

---

## Used technologies:

-   **PHP 7.x**
-   **Laravel**
-   **Eloquent ORM**
-   **Vagrant 2.2.7**
-   **HTML5**
-   **CSS3**
-   **mySQL**
-   **Bootstrap4**
-   **OOP**
-   **npm**
-   **GitHub**
-   **Git-Workflow**
-   **composer**
-   [**Uberspace-Hosting**](https://star.uber.space)
-   **Kanban**
-   **Javascript**
-   **PHPUnit**
---
![img](public/assets/images/Gesellenstück.jpeg)
