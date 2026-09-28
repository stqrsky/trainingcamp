<p align="center">
  <img src="public/assets/images/tc-trainingcamp.jpg" alt="Training Camp hero image" width="100%">
</p>

<h1 align="center">Training Camp</h1>

<p align="center">
  A mobile-first Laravel app for managing a sports club's members, sparring sessions, schedules, and tasks.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-red" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-777bb4" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/PHPUnit-12-0d5c63" alt="PHPUnit 12">
  <img src="https://img.shields.io/badge/Bootstrap-5-7952b3" alt="Bootstrap 5">
</p>

## Overview

Training Camp is a graduation project built to make daily club coordination easier for coaches and athletes. It combines team management, sparring schedules, profile management, and a lightweight task planner in one responsive web app.

## Table of contents

- [Features](#features)
- [Screenshots](#screenshots)
- [Tech stack](#tech-stack)
- [Requirements](#requirements)
- [Quick start](#quick-start)
- [Useful routes](#useful-routes)
- [Testing and code quality](#testing-and-code-quality)
- [Automation](#automation)
- [Team assistant (AI)](#team-assistant-ai)
- [Styling and front-end workflow](#styling-and-front-end-workflow)
- [Project structure](#project-structure)

## Features

### Core product features

- **Authentication**: sign up, sign in, sign out, account settings, and profile setup.
- **Team management**: create your team, add coaches and athletes, manage roles and skills.
- **Member profiles**: avatars, personal details, and team-based profile views.
- **Notifications / dashboard**: quick overview of club activity and reminders.
- **Team assistant (optional)**: ask questions about the active team in plain language; off until an Anthropic API key is set.

### Scheduling features

- **Daily sparring schedule** with time slots and paired athletes.
- **Multiple calendar views**: List, Day, Week, Month, and Planner.
- **Schedule metadata**: title, location, notes, color, video URL, and video type.
- **Fast filtering**: search schedules for athletes on the selected day.
- **Planner view**: combines the day's sparring schedule with open tasks.

### Task management

- Create, edit, delete, and complete tasks.
- Organize tasks by **overdue**, **today**, **upcoming**, **no date**, and **done**.
- Track optional notes, labels, priorities, due dates, and due times.
- Team scoping ensures users only manage tasks from their own team.

### UX and design

- **Responsive mobile-first UI** built for quick use at the gym.
- **Light and dark mode** with persistent theme selection.
- Refreshed card-based interface with branded Training Camp styling.

## Screenshots

### Before → after (mobile)

| | Login | Overview | Schedule |
|---|---|---|---|
| **Before** | <img src="docs/screenshots/before-login.png" width="190" alt="Old login"> | <img src="docs/screenshots/before-overview.png" width="190" alt="Old overview"> | <img src="docs/screenshots/before-schedule.png" width="190" alt="Old schedule"> |
| **After** | <img src="docs/screenshots/after-login.png" width="190" alt="New login"> | <img src="docs/screenshots/after-overview.png" width="190" alt="New overview"> | <img src="docs/screenshots/after-schedule.png" width="190" alt="New schedule"> |

### Dark mode

<p align="center">
  <img src="docs/screenshots/after-members-dark.png" width="220" alt="Members screen in dark mode">
</p>

## Tech stack

- **PHP 8.3+**
- **Laravel 13**
- **Blade** templates
- **Eloquent ORM**
- **Bootstrap 5**
- **Sass**
- **Vite**
- **SQLite** by default, **MySQL** optional
- **PHPUnit 12**
- **PHP_CodeSniffer** with PSR-12
- **GitHub Actions** CI on PHP 8.3 and 8.4

## Requirements

### To run the app

- PHP **8.3+**
- Composer

### To work on styles / front-end assets

- Node.js **18+**
- npm

> The compiled CSS in `public/css/` is committed, so Node.js is not required just to run the app.

## Quick start

```bash
git clone https://github.com/stqrsky/trainingcamp.git
cd trainingcamp

composer install
composer setup
php artisan serve
```

The app will be available at `http://127.0.0.1:8000`.

### What `composer setup` does

- creates `.env` if needed
- creates `database/database.sqlite` if needed
- generates `APP_KEY`
- links uploaded images into `public/` (`php artisan storage:link`)
- runs `php artisan migrate --seed --force`

### Seeded data

- The seeders create demo users, roles, skills, and teams.
- Seeded demo users use the password `secret`.
- You can also simply create your own account at `/signup`.

### Using MySQL instead of SQLite

Update your `.env` with:

- `DB_CONNECTION=mysql`
- your database host, port, database name, username, and password

Then run:

```bash
php artisan migrate --seed
```

## Useful routes

| Area | Route |
|---|---|
| Home | `/` |
| Sign up | `/signup` |
| Sign in | `/login` |
| Athletes / members | `/user/athletes` |
| Profile | `/user/profile` |
| Schedules list | `/schedules` |
| Calendar day view | `/schedules/day` |
| Calendar week view | `/schedules/week` |
| Calendar month view | `/schedules/month` |
| Planner view | `/schedules/planner` |
| Tasks | `/tasks` |
| Team assistant | `/assistant` |

## Testing and code quality

Run the test suite:

```bash
composer test
```

Or:

```bash
php artisan test
```

Run PHP_CodeSniffer:

```bash
./vendor/bin/phpcs --standard=PSR12 app tests
```

CI runs automatically on pushes and pull requests against `master` and `main` using GitHub Actions.

## Automation

### Daily summary email

Account holders can switch on a morning summary under **Profile → Reminders**. It is sent at 07:00 by the scheduler and only when there is something to do (overdue or due tasks, sparrings that day). The scheduler needs the usual cron entry:

```bash
* * * * * cd /path/to/trainingcamp && php artisan schedule:run >> /dev/null 2>&1
```

Configure a real mailer in `.env` (`MAIL_MAILER`, `MAIL_HOST`, …). For local testing use `MAIL_MAILER=log` and run the command by hand:

```bash
php artisan trainingcamp:daily-digest
```

### Activity webhook (n8n)

Every activity (tasks, sparrings, projects, announcements, member changes) can be pushed to an automation tool such as an n8n **Webhook** trigger. Set in `.env`:

```dotenv
N8N_WEBHOOK_URL=http://localhost:5678/webhook/trainingcamp
N8N_WEBHOOK_SECRET=a-long-random-string
```

Each request is a JSON `POST` with the headers `X-Trainingcamp-Event` (e.g. `task.created`) and `X-Trainingcamp-Signature: sha256=<HMAC-SHA256 of the raw body with the secret>`. Verify the signature in the receiving workflow before acting on the data. The URL is set by the operator only, never through the UI.

## Team assistant (AI)

The assistant at `/assistant` answers questions about the active team ("What is overdue?", "Who should Anna spar with?", "How are our projects doing?") and turns meeting notes into task drafts. It uses the Claude API through the official Anthropic PHP SDK.

It is **off by default**. As long as `ANTHROPIC_API_KEY` is empty, the page only shows a setup hint, the header and command palette hide it, and no data is sent anywhere. To turn it on, set in `.env`:

```dotenv
ANTHROPIC_API_KEY=sk-ant-...
# optional, defaults to claude-opus-5
ANTHROPIC_MODEL=claude-opus-5
```

What it can and cannot do:

- **Read only, one team.** Claude looks data up through a fixed set of tools (tasks, sparrings, sparring partner suggestions, projects, members). Every lookup is limited to the active team. Contact details, birth dates, weight, height and profile texts are never sent.
- **Drafts, not writes.** Tasks proposed from meeting notes appear as drafts under the answer. **Review** opens the normal task form pre-filled; nothing is created until you save it there.
- **What leaves the server:** your question, the last few questions and answers of the chat (for follow-ups) and the tool results for the active team go to Anthropic's API.
- **Limits:** 10 questions per minute per account, at most 6 lookups per question. The chat lives in the session and can be cleared with **Clear chat**.
- Requests use adaptive thinking, prompt caching and server-side fallbacks (`fallbacks: "default"`, beta `server-side-fallback-2026-07-01`): if the model declines a request, Anthropic retries it on its recommended fallback model.

The tests never call the real API: `tests/Fakes/FakeClaudeTransport.php` plugs a fake HTTP transport into the SDK and records every request.

## Styling and front-end workflow

This project currently loads compiled CSS files directly from `public/css/`:

- `public/css/app.css`
- `public/css/signin.css`
- `public/css/athlete.css`

The source styles live in `resources/sass/`.

Install front-end dependencies first if you have not already:

```bash
npm install
```

If you change Sass files, recompile them manually:

```bash
npx sass resources/sass/app.scss public/css/app.css --load-path=. --style=compressed
npx sass resources/sass/signin.scss public/css/signin.css --style=compressed
npx sass resources/sass/athlete.scss public/css/athlete.css --style=compressed
```

Useful Sass files include:

- `_variables.scss` - Bootstrap overrides
- `_tokens.scss` - theme-aware design tokens
- `_components.scss` - shared UI components
- `_calendar_tasks.scss` - calendar and task styling

## Project structure

| Path | Purpose |
|---|---|
| `app/Http/Controllers` | request handling for auth, schedules, tasks, teams, notifications |
| `app/Models` | Eloquent models such as `User`, `Team`, `Schedule`, and `Task` |
| `database/migrations` | schema changes including schedules and tasks |
| `database/seeders` | demo data for users, teams, roles, and skills |
| `resources/views/frontend` | Blade views for the user-facing interface |
| `resources/sass` | Sass source files |
| `routes/web.php` | web routes for auth, team, schedule, and task features |
| `tests/Feature` | feature coverage for schedules and tasks |

---

<p align="center">
  <img src="public/assets/images/Gesellenstück.jpeg" alt="Training Camp project image" width="70%">
</p>
