![img](public/assets/images/tc-trainingcamp.jpg)

## Installation (quick start)

You need just two things to **run** the app: **PHP 8.2+** and **Composer**.
(Node.js 18+ is only needed if you want to *change the styling* — see [Changing the look](#changing-the-look-styling).)

```bash
git clone https://github.com/stqrsky/trainingcamp.git
cd trainingcamp

composer install
cp .env.example .env        # skip if .env already exists
php artisan key:generate
php artisan migrate --seed   # creates the SQLite database + demo data

php artisan serve            # → http://127.0.0.1:8000
```

> 💡 The CSS is **already compiled and committed** in `public/css/`, so you do **not** need `npm` just to run the app.
> To start on a different port: `php artisan serve --port=8007`.

Even shorter — after `composer install`, the `setup` script does the key + database steps for you:

```bash
composer setup && php artisan serve
```

### First login

| Page | URL |
|------|-----|
| Sign up | http://127.0.0.1:8000/signup |
| Sign in | http://127.0.0.1:8000/login |

After `migrate --seed`, all demo users have the password **`secret`** (e.g. `alena.johnston@example.net`).

### Useful extras

- **MySQL instead of SQLite:** set `DB_CONNECTION=mysql` + DB credentials in `.env`, create the database, then `php artisan migrate --seed`.
- **Run tests:** `composer test` (or `php vendor/bin/phpunit`).
- **CI:** PHPUnit runs on every push/PR to `master`/`main` using in-memory SQLite (`.github/workflows/tests.yml`).

---

## About "Training Camp"

This is my graduation project from the web development bootcamp at neuefische Hamburg.

The Trainingcamp App has been developed to make the club life in a sport-bootcamp easier for you. Use the Trainingcamp App to get a quick overview of your member list or plan sparring assignments of athletes anytime and anywhere.

---

## What it does (features)

- 🏠 **Overview** – team announcements, a welcome banner and an insurance reminder.
- 🥊 **Sparring schedule** – plan who spars whom, on which day and at what time.
- 👥 **Members** – your coaches and athletes with avatars, roles and skills.
- 👤 **Profile** – your account and team settings.
- 🌙 **Light & dark mode** – switch any time with the toggle in the top bar (your choice is remembered).
- 📱 **Mobile-first & responsive** – built to be used on the phone, at the gym.

---

## Usage (in 4 steps)

1. **Create an account** at `/signup` (or sign in with a demo user — password `secret`).
2. **Complete your profile.** This also creates **your team** — you become the coach.
3. **Add members** in the **Athletes** tab (coaches & athletes, with skills).
4. **Plan sparring** in the **Schedule** tab: pick a date, add a slot and choose two athletes.

> Tip: use the 🔍 filter on the Schedule page to find an athlete instantly, and the 🌙/☀️ icon (top right) to switch dark mode.

---

## Old vs. new (frontend redesign)

The frontend was rebuilt into a clean, card-based design system with the Trainingcamp gold brand colours.

| Area | Old version | New version |
|------|-------------|-------------|
| **UI base** | Bootstrap 4, mostly unstyled HTML | Bootstrap 5 + custom design-token layer |
| **Look & feel** | plain text, flat lists | card layout, clear hierarchy, **gold** brand accents |
| **Login / signup** | text-only links | branded card on a dark "gym" backdrop, gold button |
| **Schedule** | raw list of names & times | date header, slot cards, avatars, **"VS"** pairing, role badges, instant name filter |
| **Members** | basic name list | initials avatars, role & skill badges, quick **"Assign sparring"** |
| **Theme** | light only | **light + dark mode** (remembered) |
| **Accessibility** | minimal | visible focus states, form labels, contrast-checked colours |

---

## Changing the look (styling)

The styles live in `resources/sass/` and are compiled into `public/css/` — and **that compiled CSS is what the app loads**. So after editing any `.scss`, recompile:

```bash
npx sass resources/sass/app.scss     public/css/app.css     --load-path=. --style=compressed
npx sass resources/sass/signin.scss  public/css/signin.css  --style=compressed
npx sass resources/sass/athlete.scss public/css/athlete.css --style=compressed
```

The design system is organised as:

- `_variables.scss` – Bootstrap overrides (brand gold, fonts, radii)
- `_tokens.scss` – semantic colours + dark-mode values
- `_components.scss` – shared components (cards, buttons, badges, banners, nav)

---

## Git workflow

- Main branch: **`master`**.
- Create a feature branch, edit the views (`resources/views/`) and/or styles (`resources/sass/`), **recompile the CSS**, then commit.
- Open a pull request — the test suite runs automatically in CI.

---

## Used technologies:

-   **PHP 8.2+**
-   **Laravel 11**
-   **Eloquent ORM**
-   **Blade** templates
-   **Bootstrap 5** + **Sass** (custom design-token system)
-   **HTML5 / CSS3 / JavaScript**
-   **SQLite** (default) · **MySQL** (optional)
-   **OOP**
-   **Composer** · **npm**
-   **Git / GitHub** · Git-Workflow · **Kanban**
-   **PHPUnit** (CI on every push/PR)
-   [**Uberspace-Hosting**](https://star.uber.space)
---
![img](public/assets/images/Gesellenstück.jpeg)
