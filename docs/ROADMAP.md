# Trainingcamp: Bestandsaufnahme und Roadmap

Stand: 2026-09-28 · Basis: Commit `6dd5f92` · Tests: 16 grün (48 Assertions)

Ziel: Trainingcamp schrittweise zu einer Team-Management- und Collaboration-Plattform ausbauen, ohne funktionierende Teile neu zu schreiben. Dieses Dokument hält den Ist-Zustand fest und priorisiert die nächsten Schritte.

**Kernbefund:** Die App ist heute im Kern eine Ein-Personen-App für den Team-Owner. Mitglieder können sich einloggen, sehen aber praktisch nichts. Bevor Collaboration-Features Sinn ergeben, braucht es ein Team-Membership-Modell (siehe P0-2). Das ist ein Breaking Change und wird erst nach Freigabe umgesetzt.

---

## 1. Tech Stack

| Bereich | Stand |
|---|---|
| Backend | PHP 8.3+ (lokal 8.4.11), Laravel 13.15, Eloquent, Blade, server-rendered |
| API | keine, `routes/api.php` ist leer |
| Datenbank | SQLite als Default, MySQL optional, 11 Migrations |
| Frontend | Bootstrap 5.3, Sass, Material Icons, jQuery slim, Select2 (nur 4 Formulare), Inline-Vanilla-JS in Blade |
| Build | Sass per CLI nach `public/css` (kompiliertes CSS committed). `vite.config.js` vorhanden, aber ungenutzt (kein `@vite`). `public/js/app.js` und `mix-manifest.json` sind Laravel-Mix-Reste |
| Auth | Eigene Session-Auth in `UserController`. Kein Breeze/Fortify, kein Passwort-Reset, keine E-Mail-Verifikation |
| Tests / CI | PHPUnit 12, 16 Tests, GitHub Actions auf PHP 8.3 und 8.4, PHPCS nach PSR-12 |
| Integrationen | keine. Mail-Config ist Standard-Mailtrap, Queue `sync`, Scheduler leer |
| Struktur | Noch im Laravel-7-Stil: `app/Http/Kernel.php`, `RouteServiceProvider` mit Namespace-String-Routes (`'HomeController@index'`), klassenbasierte Migrations. Funktioniert, ist aber veraltet |

## 2. Seiten und Features

| Bereich | Zustand |
|---|---|
| Auth | Signup, Login, Logout. Nach Registrierung ist man immer Rolle `coach` |
| Profil-Setup | Legt beim ersten Setup automatisch das eigene Team an |
| Team ("Athletes") | Coaches und Athletes auflisten, Suche nur nach `first_name`, Athlete anlegen/bearbeiten/entfernen, Coaches nicht editierbar, Detailseite |
| Profil | Nickname, Geburtsdatum, Gewicht, Größe, About, Avatar, Skills |
| Schedules (= Sparring) | CRUD mit fest 2 Teilnehmern, Titel, Ort, Notizen, Farbe, Video-Link. Ansichten List, Day, Week, Month, Planner |
| Tasks | CRUD, Toggle, Gruppierung overdue/today/upcoming/no date/done, Label, Priorität 0/1, Status 0/1 |
| "Notifications" | In Wirklichkeit Posts/Ankündigungen mit Bild. Home zeigt nur die eigenen Posts |
| Design | Token-System in `resources/sass/_tokens.scss`, Gold-Branding, Dark Mode, Bottom-Nav mit 5 Tabs |

**Fehlt komplett:** Dashboard-Daten, Kanban, Projekte, echte Notifications, Activity Feed, Kommentare, Suche, Command Palette, Statistiken, Einladungen, Policies.

## 3. Datenmodell

```
users ─┬─ user_detail (1:1, image_id → images)
       ├─ user_role ── roles (coach/athlete/admin, global, nicht pro Team)
       ├─ user_skill ── skills (ohne Level)
       └─ teams.user_id  (hasOne = Owner)
teams ─┬─ team_coach   (Pivot)
       ├─ team_athlete (Pivot)
       ├─ schedules ── schedule_participant (Pivot, ohne RSVP-Status)
       └─ tasks (user_id = Ersteller, kein Assignee, kein Projekt)
notifications (Posts: user_id, team_id, image_id, title, description)
```

## 4. Kernproblem: Team-Zugehörigkeit

Alle Controller holen das Team über `User::find(Auth::id())->team`. Das ist `hasOne` über `teams.user_id` und liefert nur beim Owner ein Team.

- Athletes und zusätzliche Coaches sehen nach dem Login leere Seiten.
- `schedules/{id}/edit|update|destroy` wirft einen 500er, weil `$team` null ist (`ScheduleController.php:97`).
- Rollen sind global statt pro Team. `admin` wird nirgends verwendet, `RoleController` ist leer.
- Der Custom-Table `notifications` kollidiert mit Laravels `Notifiable`-Trait im `User`-Model. Echte Laravel-Database-Notifications gehen erst, wenn die Posts umbenannt sind.

## 5. Security (P0)

1. **Stored XSS.** `{!! !!}` auf Nutzereingaben in `frontend/home.blade.php:63`, `frontend/athletes/detail.blade.php:42`, `frontend/users/profile.blade.php:27`. Textarea-Ausbruch möglich in `frontend/athletes/form.blade.php:107` und `frontend/users/formprofile.blade.php:77`. Heute meist Self-XSS, mit Team-Sichtbarkeit echtes XSS.
2. **Sparring-Teilnehmer nicht team-gescoped.** Validierung nur `exists:users,id` (`ScheduleController.php:53`). Beliebige User fremder Teams lassen sich eintragen, Name und Avatar werden dann angezeigt.
3. **Session beim Logout.** Kein `invalidate()` und kein neues CSRF-Token beim Logout. (Korrektur: Beim Login regeneriert Laravels `SessionGuard::login()` die Session bereits selbst, Session Fixation lag dort nicht vor.)
4. **Kein Login-Throttling**, keine Passwortregeln bei Signup, Mitglied anlegen und Account-Änderung.
5. **Leaks über Fehlermeldungen.** `$th->getMessage()` geht direkt an den User (Schedule, Notification, `TeamController::updateUser`, Profil). Der Helper `userFacingError()` existiert, wird aber nur teilweise genutzt.
6. **Unvalidierte Datums-Parameter.** `Carbon::createFromFormat` auf `?date=` und `due_date` ohne Validierung, falsche Eingabe führt zu 500.
7. **`public/images`-Symlink** ist mit absolutem lokalem Pfad committed. Auf jedem anderen Rechner und bei jedem Deploy sind Bilder kaputt.
8. `composer audit` meldet 22 Advisories: PHP_CodeSniffer (Dev), sowie transitiv über Laravel `guzzlehttp/guzzle`, `guzzlehttp/psr7` und `league/commonmark`.

## 6. Technische Schulden

- Validierung inline in Controllern und mehrfach dupliziert (Profil- und Athlete-Formular fast identisch). Keine FormRequests, keine Policies.
- `User::find(Auth::user()->id)` überall, eine unnötige Query pro Request.
- Bootstrap wird doppelt geladen: per CDN in `frontend/layouts/app.blade.php:19` und im kompilierten `app.css` (167 KB, importiert Bootstrap selbst).
- Drei Build-Ansätze parallel: Sass-CLI, ungenutztes Vite, Mix-Reste.
- Tote Dateien: `database/seeds/` (Duplikat von `seeders/`), `frontend/create.blade.php` und `frontend/signup.blade.php` (Angular/MDL-Reste), veraltetes `IMPROVEMENTS.md` (spricht noch von Laravel 7).
- `UploadImage` crasht, wenn `userDetail` fehlt. Beim Notification-Upload fehlt das `max`-Limit. Dateiname ist nur die ID, dadurch Cache-Probleme.
- Keine Indizes auf `schedules.date`, `tasks.due_date`, `tasks.status`. Keine Pagination bei Team und Tasks.

## 7. UX und Accessibility

- Home ("Bootcamp Overview") zeigt nur eigene Posts, nichts zu Aufgaben oder Sparrings.
- Desktop ist eine zentrierte Handy-Spalte (`col-lg-5`), keine Sidebar.
- Team-Suche findet nur Vornamen. "Assign" öffnet das Sparring-Formular ohne Vorauswahl. Coaches nicht bearbeitbar. Der Empty-State verlinkt aufs Profil statt aufs Setup.
- Alerts verschwinden automatisch nach 3 Sekunden, auch Validierungsfehler.
- In der Bottom-Nav steht das Label außerhalb des Links. Kleine Klickfläche, Screenreader lesen den Icon-Namen vor ("format_list_bulleted"). Dropdown-Trigger sind `<a>` ohne `href`.

## 8. Tests

Abgedeckt: Auth (Signup, Login, Redirect), Calendar-Views (Smoke), Tasks (CRUD, Toggle, fremdes Team gibt 403), User-Unit-Tests.

Nicht abgedeckt: Team-CRUD, Schedule store/update, Uploads, Posts, Autorisierung von Schedules, Nicht-Owner-Szenarien.

---

# Roadmap

Reihenfolge nach Priorität. P0-2 blockiert fast alles, was Zusammenarbeit im Team voraussetzt.

## P0

### P0-1 Security-Hardening (ohne Schemaänderung) ✅ erledigt 2026-09-28

Umgesetzt auf Branch `fix/p0-1-security-hardening`: Output-Escaping (inkl. Textarea-Ausbruch), Login-Throttling (5 Versuche pro E-Mail und IP), Signup-Throttle, `Password::defaults()` (min. 8 Zeichen) bei Signup, Mitglied anlegen und Passwortwechsel, keine Passwörter mehr im Old-Input, Logout mit `invalidate()`, Sparring-Teilnehmer auf das eigene Team beschränkt, vollständige Validierung von Sparrings und Tasks (`end` nach `start`, Farben, Video-Typ, Datumsformate), ungültige Datums-Parameter fallen auf die Standardansicht zurück, Null-Guards für User ohne Team, `userFacingError()` überall, `public/images` nicht mehr versioniert (`storage:link` im Setup), Dependencies gepatcht (`composer audit` sauber). Nebenbei gefixt: Monatsansicht sprang am 29.–31. in den Folgemonat, Athlete-Detail zeigte Größe statt Gewicht. Tests: 35 grün (vorher 16).


- **Aktuell:** Lücken aus Abschnitt 5.
- **Ziel:** keine XSS, sichere Sessions, alle Eingaben validiert.
- **Änderungen:** `{{ }}` bzw. `nl2br(e())` statt `{!! !!}`. `session()->regenerate()` und `invalidate()`. `throttle` auf der Login-Route. `Password::defaults()`. Teilnehmer per `Rule::exists` aufs Team beschränken. Null-Guards für `$team`. Datums-Parameter validieren. `userFacingError()` überall nutzen. Symlink durch `storage:link` bzw. Public Disk ersetzen. PHPCS updaten.
- **Dateien:** alle Controller, 5 Blade-Dateien, `routes/web.php`, `app/Http/Libraries/UploadImage.php`.
- **Abhängigkeiten:** keine.
- **Tests:** XSS-Escaping, Session-Regeneration, fremde Teilnehmer abgelehnt, falsches Datum gibt 422 statt 500.

### P0-2 Multi-Team und Login nur für Account-Inhaber ✅ erledigt 2026-09-28

Ersetzt den ursprünglichen Membership-Plan unten (siehe Entscheidungen). Umgesetzt auf Branch `feat/p0-2-multi-team`: Migration macht `users.email`/`users.password` nullable und ergänzt `login_enabled` und `current_team_id` (Backfill: Login bleibt für alle außer Mitgliedern fremder Teams). Login nur mit `login_enabled`. Mitglieder werden ohne Passwort angelegt, E-Mail optional. `User::teams()` (hasMany) und `User::currentTeam()` mit Fallback aufs älteste eigene Team, alle Controller über `Controller::currentTeam()`. Team-Switcher im Header, Seiten "New team" und "Edit team", Wechsel nur in eigene Teams (sonst 404). Posts, Tasks und Sparrings sind auf das aktive Team gescoped. Teamname nicht mehr in den Profil-Einstellungen. Nebenbei gefixt: Profil-Update entfernte per `sync()` alle anderen Coaches aus dem Team, fehlendes `about` führte zu einem Fehler, erneutes Onboarding legte doppelte Profile an. Tests: 49 grün.

Ursprünglicher Plan (verworfen):


- **Aktuell:** Owner über `teams.user_id`, Pivots `team_coach` und `team_athlete`, globale Rollen.
- **Ziel:** neue Tabelle `team_user` (`team_id`, `user_id`, `role` owner/admin/coach/member/guest, `status` active/inactive/invited, `joined_at`) plus `User::currentTeam()` als zentrale Auflösung (Session-Key bei mehreren Teams).
- **Migration:** `team_user` befüllen aus `teams.user_id` (owner), `team_coach` (coach) und `team_athlete` (member). `coaches()` und `athletes()` bleiben als gefilterte Relationen auf `team_user` erhalten, damit Views unverändert laufen. Alte Pivots erst in einem späteren Schritt droppen.
- **Mapping:** athlete wird `member`, coach bleibt `coach`. `user_role` bleibt vorerst bestehen, steuert aber keine Rechte mehr.
- **Dateien:** neue Migration, `Team.php`, `User.php`, alle 5 Controller (`->team` wird `currentTeam()`), `TeamSeeder`, `UserSeeder`, `TaskTest`, `ScheduleCalendarTest`.
- **Risiken:** Datenmigration auf bestehenden DBs. Views, die `$team->athletes` erwarten, laufen über die Kompatibilitäts-Relationen weiter.
- **Abhängigkeiten:** blockiert Einladungen, RSVP, Notifications, Activity, Kommentare und das team-weite Dashboard.

### P0-3 Authorization über Policies ✅ durch P0-2 abgedeckt

Ohne Mitglieder-Logins reichen Ownership-Checks über `currentTeam()`. Eine Policies-Matrix wird erst mit Co-Manager-Logins nötig.


- **Aktuell:** manuelle Owner-Checks.
- **Ziel:** `TeamPolicy`, `TaskPolicy`, `SchedulePolicy`, `PostPolicy` auf Basis der Rolle in `team_user`. Rechte-Matrix: Member sehen alles und bearbeiten Eigenes, Coach und Admin verwalten Sparrings und Tasks, nur der Owner verwaltet Rollen.
- **Dateien:** `app/Policies/*`, `AuthServiceProvider`, alle Controller.
- **Abhängigkeit:** P0-2.

## P1

### P1-1 Asset-Pipeline aufräumen

- CDN-Bootstrap raus, eine Pipeline. Empfehlung: Vite (schon installiert) mit `@vite`.
- Mix-Reste und tote Views löschen, `database/seeds` entfernen.
- **Dateien:** `frontend/layouts/app.blade.php`, `backend/layout.blade.php`, `vite.config.js`, `public/`.
- **Risiko:** Deploy braucht dann `npm run build`. Alternative: kompiliertes CSS weiter committen.

### P1-2 Tasks und Kanban

- **Aktuell:** Status 0/1, Priorität 0/1, `user_id` ist der Ersteller.
- **Ziel:** `status` als String (backlog/todo/in_progress/review/done), `priority` low/medium/high/urgent, neue Spalten `assignee_id` und `created_by`. Tabs "Meine Aufgaben" und Liste. Kanban mit nativem HTML5 Drag-and-Drop und `PATCH /tasks/{id}/status`, keine neue Dependency.
- **Migration:** Status 0 wird `todo`, 1 wird `done`. Priorität 0 wird `medium`, 1 wird `high`. `toggle` bleibt kompatibel.
- **Dateien:** Migration, `Task.php`, `TaskController`, `frontend/tasks/*`, `_calendar_tasks.scss`, `TaskTest`.
- **Abhängigkeit:** Assignee braucht P0-2.

### P1-3 Sparring ausbauen

- **Aktuell:** fest 2 Teilnehmer, `status` immer 1, kein RSVP.
- **Ziel:** Status geplant/bestätigt/läuft/abgeschlossen/abgesagt. Felder `goal` und `result`. Im Pivot `schedule_participant` zusätzlich `rsvp_status` und `responded_at`. Bestätigen/Ablehnen für Teilnehmer. Bei Termin- oder Zeitänderung wird das RSVP zurückgesetzt (später mit Notification).
- **Dateien:** Migration, `Schedule.php`, `ScheduleController`, `frontend/schedules/*`.
- **Abhängigkeiten:** P0-2, P0-3.

### P1-4 Teamverwaltung

- Suche über Voll- und Nickname, Filter nach Rolle, Skill und Status, Sortierung.
- Coaches bearbeitbar. Aktive/inaktive Mitglieder über Membership-Status statt Löschen.
- Einladung per Signed URL und Mail, statt dass der Coach Passwörter vergibt.
- "Assign" setzt den Athlete im Sparring-Formular vor.
- **Dateien:** `TeamController`, `frontend/athletes/*`, neu `InvitationController` und Mailable.
- **Abhängigkeiten:** P0-2, Mail-Config.

### P1-5 Dashboard

- **Aktuell:** nur eigene Posts.
- **Ziel:** kompakte Abschnitte für heute fällige Tasks, überfällige Tasks, die nächsten 3 Sparrings und team-weite Ankündigungen. Nicht mehr.
- **Dateien:** `HomeController`, `frontend/home.blade.php`.
- **Abhängigkeit:** P0-2.

### P1-6 Kalender vereinheitlichen

- **Aktuell:** Kalender zeigt nur Schedules, Deadlines nur im Planner.
- **Ziel:** `CalendarFeed`-Service bildet Schedules und Task-Deadlines auf ein gemeinsames Item-Format ab. Bestehende Views bleiben, "List" wird zu "Agenda". Eine eigene `events`-Tabelle (Meetings, persönliche Termine) erst, wenn sie gebraucht wird.
- **Dateien:** neu `app/Services/CalendarFeed.php`, `ScheduleController`, Kalender-Views.

### P1-7 Profile

- `level` im Pivot `user_skill`, `availability` (Wochentag plus Zeitfenster, eigene kleine Tabelle), Social-Links, Username.
- Profilseite mit Task-Statistik und kommenden Sparrings.
- **Dateien:** Migration, `User.php`, `UserController`, `frontend/users/*`, `frontend/athletes/detail.blade.php`.

## P2

- **Posts umbenennen:** `notifications` wird `posts`. Danach echte Laravel Database Notifications mit Preferences, Glocke und Badge. Events: Zuweisung, Sparring-Einladung, Terminänderung, überfällig per Scheduler-Job.
- **Activity Feed:** polymorphe `activities`-Tabelle, geschrieben nur über Model-Observer (eine Stelle, keine doppelte Logik).
- **Kommentare:** polymorphe `comments`-Tabelle mit `parent_id` und @mentions für Tasks und Sparrings.
- **Projekte:** `projects`-Tabelle plus `tasks.project_id`. Fortschritt wird aus den Tasks berechnet, nicht gespeichert.
- **Globale Suche und Cmd+K-Palette:** Vanilla JS, ein team-gescopter JSON-Endpoint `/search`, Befehle als statische Liste.
- **Analytics:** einfache Zählwerte und CSS-Balken, vorerst keine Chart-Library.
- **Desktop-Layout:** Sidebar ab `lg`, Mobile behält die Bottom-Nav. Nav-Accessibility und Alert-Verhalten fixen.

## P3

- Skill-Matrix-View und regelbasiertes Matching (`SparringMatcher` über Skills, Level, Verfügbarkeit, bisherige Sparrings).
- AI Assistant mit Tool-Calling nur über policy-gescopte Queries.
- Automation: Domain-Events als Outbound-Webhooks für n8n, Laravel Scheduler (Cron nötig).
- Integrationen: zuerst ein ICS-Feed (günstigster Weg zu Google Calendar).
- Optional: Modernisierung auf Laravel-11+-Struktur (`bootstrap/app.php`). Funktional nicht nötig.

---

## Entscheidungen

- **2026-09-28:** Athletes und Coaches loggen sich nicht selbst ein. Nur der Account-Inhaber (Manager) nutzt die App, Mitglieder sind verwaltete Profile ohne Login.
- **2026-09-28:** Ein Account kann mehrere Teams besitzen.

Folge für die Roadmap: P0-2 wird zu "Multi-Team und Login nur für Account-Inhaber" statt Rollen-Membership. Einladungen, RSVP durch Teilnehmer, Notifications an Mitglieder und @mentions entfallen vorerst. Alle Zugriffe laufen über `currentTeam()` und Ownership-Checks, damit spätere Co-Manager-Logins ohne Umbau nachrüstbar bleiben.

## Offene Entscheidungen

1. Asset-Pipeline: Vite mit Build-Schritt beim Deploy, oder kompiliertes CSS weiter committen?

## Nächster Schritt

P0-2-Migrationsplan im Detail zur Freigabe vorlegen, sobald die offenen Entscheidungen 1 und 2 geklärt sind.
