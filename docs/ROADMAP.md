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

### P1-1 Asset-Pipeline aufräumen ✅ erledigt 2026-09-28

Abschluss auf Branch `chore/bootstrap-5`: Bootstrap 5.3.3 im Lockfile, `app.css` wird mit Bootstrap 5 gebaut, das CDN-Stylesheet entfällt (vorher ca. 185 KB `app.css` plus 227 KB CDN, jetzt 264 KB in einer Datei). BS4-Klassen ersetzt (`float-right/left` → `float-end/start`, `font-weight-bold` → `fw-bold`); `.close`, `.form-group`, `.card-body`/`.toast-*` ohne Wrapper und Label-Abstände als explizite App-Regeln; Variablen für 30px Gutter, feste Überschriftengrößen, `.small` 80 %, Links nur beim Hover unterstrichen, `$dark` wie zuvor. Geprüft per automatischem Vergleich alt gegen neu (Größen und berechnete Styles aller Elemente) auf 19 Seiten plus Login/Signup, Handy und Desktop, Light und Dark: deckungsgleich bis auf bewusst übernommene Verbesserungen (kontrastreicheres `text-muted`, Dark-Mode-Linkfarbe, `.close` ohne 50 % Deckkraft, Orts-Icon-Feld mit Tokens). Nebenbei gefixt: Namensfilter der Sparring-Liste lief wegen eines fehlenden Semikolons nie; Datei-Feld im Ankündigungsformular erzeugte seitliches Scrollen. Regressionstests für CDN-freies Layout und BS4-freie Views.

Ursprünglicher Befund: 
Befund: Kein reines Duplikat. `package-lock.json` pinnt Bootstrap 4.4.1 (obwohl `package.json` `^5.3.0` sagt), `app.css` enthält also BS4, das CDN liefert BS5. Die Views nutzen beide Versionen (BS4: `float-right/left` 45×, `form-group` 53×, `.close` 17×, `font-italic`; BS5: `form-select`, `visually-hidden`, `data-bs-*`, Dark Mode). Erledigt: das ungenutzte Mix-Bundle `js/app.js` (728 KB, axios, lodash, BS4-JS) wird nicht mehr geladen.


- CDN-Bootstrap raus, eine Pipeline. Empfehlung: Vite (schon installiert) mit `@vite`.
- Mix-Reste und tote Views löschen, `database/seeds` entfernen.
- **Dateien:** `frontend/layouts/app.blade.php`, `backend/layout.blade.php`, `vite.config.js`, `public/`.
- **Risiko:** Deploy braucht dann `npm run build`. Alternative: kompiliertes CSS weiter committen.

### P1-2 Tasks und Kanban ✅ erledigt 2026-09-28

Umgesetzt auf Branch `feat/p1-2-tasks-kanban`: Status backlog/todo/in_progress/review/done und Priorität low/medium/high/urgent (Migration mappt 0→todo/medium, 1→done/high, Rollback getestet), optionale Zuständige aus dem aktiven Team (`assignee_id`, `tasks.user_id` bleibt der Ersteller). Ansichten List, Board und My Tasks. Kanban mit nativem Drag-and-Drop plus Status-Select pro Karte für Touch und Tastatur, gespeichert per `PATCH /tasks/{id}/status`, bei Fehler springt die Karte zurück. Planner nutzt die neue Logik. Nebenbei gefixt: Auf der Edit-Seite war das Delete-Formular im Update-Formular verschachtelt, dadurch ließen sich Aufgaben nicht speichern. Tests: 61 grün.


- **Aktuell:** Status 0/1, Priorität 0/1, `user_id` ist der Ersteller.
- **Ziel:** `status` als String (backlog/todo/in_progress/review/done), `priority` low/medium/high/urgent, neue Spalten `assignee_id` und `created_by`. Tabs "Meine Aufgaben" und Liste. Kanban mit nativem HTML5 Drag-and-Drop und `PATCH /tasks/{id}/status`, keine neue Dependency.
- **Migration:** Status 0 wird `todo`, 1 wird `done`. Priorität 0 wird `medium`, 1 wird `high`. `toggle` bleibt kompatibel.
- **Dateien:** Migration, `Task.php`, `TaskController`, `frontend/tasks/*`, `_calendar_tasks.scss`, `TaskTest`.
- **Abhängigkeit:** Assignee braucht P0-2.

### P1-3 Sparring ausbauen ✅ erledigt 2026-09-28

Umgesetzt auf Branch `feat/p1-3-sparring`, angepasst an die Entscheidung ohne Mitglieder-Logins (kein RSVP, der Manager setzt den Status): Status planned/confirmed/in_progress/completed/cancelled (Migration: vergangene Termine werden `completed`, übrige `planned`, Rollback getestet), Felder `goal` und `result`, Status-Wechsel direkt im Aktionsmenü der Liste (`PATCH /schedules/{id}/status`). Liste zeigt Status, Ziel und Ergebnis; abgesagte Sparrings bleiben im Kalender sichtbar (durchgestrichen, blass), fallen aber aus Dashboard-Kennzahl und "Upcoming". Nach dem Speichern landet man auf dem Tag des Sparrings. Nebenbei gefixt: Monats- und Wochenansicht zeigten unter SQLite keine Sparrings am Monatsersten bzw. am Montag. Tests: 83 grün.


- **Aktuell:** fest 2 Teilnehmer, `status` immer 1, kein RSVP.
- **Ziel:** Status geplant/bestätigt/läuft/abgeschlossen/abgesagt. Felder `goal` und `result`. Im Pivot `schedule_participant` zusätzlich `rsvp_status` und `responded_at`. Bestätigen/Ablehnen für Teilnehmer. Bei Termin- oder Zeitänderung wird das RSVP zurückgesetzt (später mit Notification).
- **Dateien:** Migration, `Schedule.php`, `ScheduleController`, `frontend/schedules/*`.
- **Abhängigkeiten:** P0-2, P0-3.

### P1-4 Teamverwaltung ✅ erledigt 2026-09-28

Umgesetzt auf Branch `feat/p1-4-team-management` (ohne Einladungen, da Mitglieder sich nicht einloggen): Suche über Vor-, Nach- und Spitzname (auch "Max Mus"), Filter nach Rolle, Skill und Status, Sortierung A–Z, Z–A und zuletzt hinzugefügt, Filter per URL teilbar. Aktiv/Inaktiv pro Team (`active` in `team_athlete`/`team_coach`): Inaktive bleiben mit Historie im Team, sind aber für neue Sparrings und Aufgaben nicht wählbar; bestehende Zuordnungen bleiben erhalten. Coaches sind jetzt bearbeitbar, deaktivierbar und entfernbar, der Owner ist davor geschützt. "Assign" setzt den Athlete im Sparring-Formular vor. Coaches und Athletes teilen sich ein Zeilen-Partial. Nebenbei gefixt: "Delete" auf der Mitglieds-Edit-Seite speicherte nur, statt zu entfernen; fehlende optionale E-Mail führte beim Bearbeiten zu einem Fehler. Tests: 95 grün.


- Suche über Voll- und Nickname, Filter nach Rolle, Skill und Status, Sortierung.
- Coaches bearbeitbar. Aktive/inaktive Mitglieder über Membership-Status statt Löschen.
- Einladung per Signed URL und Mail, statt dass der Coach Passwörter vergibt.
- "Assign" setzt den Athlete im Sparring-Formular vor.
- **Dateien:** `TeamController`, `frontend/athletes/*`, neu `InvitationController` und Mailable.
- **Abhängigkeiten:** P0-2, Mail-Config.

### P1-5 Dashboard ✅ erledigt 2026-09-28

Umgesetzt auf Branch `feat/p1-5-dashboard`: Kennzahlen des aktiven Teams (offene und überfällige Tasks, mir zugewiesen, Sparrings diese Woche, jeweils verlinkt), Liste "Due today & overdue" mit Abhaken direkt im Dashboard, die nächsten 3 Sparrings (heutige vergangene werden ausgeblendet), "Your teams" mit offenen und überfälligen Tasks pro Team und Ein-Klick-Wechsel (nur bei mehreren Teams), darunter die Ankündigungen. Kennzahlen stehen jetzt oben, der Begrüßungs-Banner erscheint nur noch ohne Team, der Versicherungshinweis bleibt. Tests: 66 grün.


- **Aktuell:** nur eigene Posts.
- **Ziel:** kompakte Abschnitte für heute fällige Tasks, überfällige Tasks, die nächsten 3 Sparrings und team-weite Ankündigungen. Nicht mehr.
- **Dateien:** `HomeController`, `frontend/home.blade.php`.
- **Abhängigkeit:** P0-2.

### P1-6 Kalender vereinheitlichen ✅ erledigt 2026-09-28

Umgesetzt auf Branch `feat/p1-6-calendar`, ohne eigenen `CalendarFeed`-Service (ein Scope `Task::dueBetween()` reicht): offene Task-Deadlines erscheinen in Monat (max. 3 Einträge pro Tag, Rest über "+N more"), Woche (eigene Zeile "Due") und Tag ("Due this day" mit Abhaken). Neuer Tab "Agenda": 14 Tage mit Sparrings und Deadlines chronologisch, Überfälliges oben, Navigation in 14-Tage-Schritten. "List" bleibt als Tagesansicht mit Suche. Monats-, Wochen- und Tagesraster, Task-Liste und View-Tabs nutzen jetzt Design-Tokens: Vorher waren die Raster im Dark Mode weiß und offene Task-Titel kaum lesbar. Tests: 73 grün.


- **Aktuell:** Kalender zeigt nur Schedules, Deadlines nur im Planner.
- **Ziel:** `CalendarFeed`-Service bildet Schedules und Task-Deadlines auf ein gemeinsames Item-Format ab. Bestehende Views bleiben, "List" wird zu "Agenda". Eine eigene `events`-Tabelle (Meetings, persönliche Termine) erst, wenn sie gebraucht wird.
- **Dateien:** neu `app/Services/CalendarFeed.php`, `ScheduleController`, Kalender-Views.

### P1-7 Profile ✅ erledigt 2026-09-28

Umgesetzt auf Branch `feat/p1-7-profiles`. Befund: Die Tabelle `skills` enthielt keine Skills, sondern Erfahrungsstufen (Basic/Intermediate/Advance/Expert, Mehrfachauswahl). Entscheidung (2026-09-28): Skills pro Team selbst anlegen, Altdaten ins Erfahrungslevel übernehmen. Umsetzung: `user_detail.experience_level` (höchste bisherige Stufe übernommen, Pseudo-Skills entfernt), Skill-Katalog pro Team (`skills.team_id`, gepflegt auf "Edit team"), Level pro Mitglied und Skill (`user_skill.level`), Verfügbarkeit als Wochentag plus Zeitfenster (`availabilities`, gleichzeitig bevorzugte Sparring-Zeiten). Speichern und Validierung für Mitglieds- und eigenes Profil zentral in `App\Http\Libraries\MemberProfile`; Skills anderer Teams bleiben beim Speichern unberührt. Mitglieds-Detail und eigenes Profil zeigen Level, Skills mit Level, Verfügbarkeit, offene und erledigte Aufgaben, kommende und absolvierte Sparrings (nur aus den eigenen Teams). Social Links und Benutzername bewusst weggelassen (optional in der Anforderung, Spitzname existiert). Nebenbei gefixt: Das native Datei-Feld der Profilbild-Auswahl war sichtbar und erzeugte auf dem Handy seitliches Scrollen (Regel lag in einer nie importierten Sass-Datei). Tests: 104 grün.


- `level` im Pivot `user_skill`, `availability` (Wochentag plus Zeitfenster, eigene kleine Tabelle), Social-Links, Username.
- Profilseite mit Task-Statistik und kommenden Sparrings.
- **Dateien:** Migration, `User.php`, `UserController`, `frontend/users/*`, `frontend/athletes/detail.blade.php`.

## P2

- ✅ **Erinnerungen statt Notification-Tabelle (2026-09-28):** Ohne Mitglieder-Logins braucht es keine gespeicherten Benachrichtigungen. `App\Services\Reminders` berechnet live: überfällige Aufgaben, heute fällige Aufgaben, Sparrings in den nächsten 24 Stunden; Glocke mit Zähler im Header; pro Typ abschaltbar (`users.notification_preferences`). Die Umbenennung der Posts-Tabelle ist damit nicht nötig. Offen für später: E-Mail-Digest per Scheduler oder n8n.
- ✅ **Activity Feed (2026-09-28, Branch `feat/p2-reminders-activity`):** `activities`-Tabelle, geschrieben ausschließlich von Observern: Aufgaben (angelegt, Status, Zuständige, Deadline, gelöscht; reine Text-Edits bewusst nicht), Sparrings (geplant, Status, verschoben, gelöscht), Ankündigungen, Team angelegt, Mitglieder hinzugefügt/aktiv-inaktiv/entfernt über das eigene Pivot-Model `TeamMembership`. Seite `/activity` (nach Tagen gruppiert, paginiert) und Abschnitt "Recent activity" im Dashboard; nur aktives Team.
- **Kommentare:** polymorphe `comments`-Tabelle mit `parent_id` und @mentions für Tasks und Sparrings.
- **Projekte:** `projects`-Tabelle plus `tasks.project_id`. Fortschritt wird aus den Tasks berechnet, nicht gespeichert.
- ✅ **Globale Suche und Cmd+K-Palette (2026-09-28, Branch `feat/p2-search-command-palette`):** `GET /search` (JSON, gedrosselt, nur aktives Team) über Mitglieder (Name, Spitzname), Aufgaben, Sparrings (inkl. Teilnehmernamen) und eigene Ankündigungen; Palette als natives `<dialog>` mit Combobox/Listbox, Pfeiltasten, Enter, Esc; Befehle (New task, Plan sparring, Add member, Calendar, Agenda, Board, My tasks, Team, Switch to …) aus `CommandPaletteComposer`; Ergebnisse werden per `textContent` gerendert. Namenssuche als `User::scopeMatchingName()` für Team-Seite und Suche. Tests: 113 grün.
- **Analytics:** einfache Zählwerte und CSS-Balken, vorerst keine Chart-Library.
- ✅ **Desktop-Layout und Accessibility (2026-09-28):** Navigation zentral in `config/navigation.php`; Sidebar ab 992px, darunter Bottom-Nav mit Beschriftung im Link und `aria-current`; Inhalt auf dem Desktop bis 860px breit, Kanban zeigt mehrere Spalten; `<main>`-Landmark und Skip-Link; nur Erfolgsmeldungen blenden sich aus, Fehler bleiben stehen.

## P3

- ✅ **Sparring-Matching und Skill-Matrix (2026-09-28, Branch `feat/p3-sparring-matching`):** `App\Services\SparringMatcher`, regelbasiert und erklärbar, max. 11 Punkte: Level (gleich 3, eine Stufe 2, sonst 0, unbekannt 1), gemeinsame Skills mit höchstens einer Stufe Abstand (+1 je Skill, max. 3), gemeinsames Zeitfenster ≥ 60 Min. (3, inkl. nächstem Termin), Gewichtsdifferenz (≤ 5 kg 2, ≤ 10 kg 1, darüber −1), Abwechslung (−1 bei ≥ 2 gemeinsamen Sparrings in 30 Tagen). Nur aktive Athletes des aktiven Teams. Athlete-Detailseite zeigt die Top 3 mit Begründung und "Plan sparring" (füllt Partner, Datum, Zeit vor); das Sparring-Formular schlägt Partner vor, sobald der erste Athlete gewählt ist (`GET /schedules/partners`). Skill-Matrix unter `/user/athletes/matrix` (Tabelle ab Tablet, Karten auf dem Handy), verlinkt auf der Team-Seite und in der Palette. Nebenbei: Athleten-Auswahl im Sparring-Formular bleibt nach Validierungsfehlern erhalten. Tests: 122 grün.
- AI Assistant mit Tool-Calling nur über policy-gescopte Queries.
- Automation: Domain-Events als Outbound-Webhooks für n8n, Laravel Scheduler (Cron nötig).
- Integrationen: zuerst ein ICS-Feed (günstigster Weg zu Google Calendar).
- Optional: Modernisierung auf Laravel-11+-Struktur (`bootstrap/app.php`). Funktional nicht nötig.

---

## Entscheidungen

- **2026-09-28:** Athletes und Coaches loggen sich nicht selbst ein. Nur der Account-Inhaber (Manager) nutzt die App, Mitglieder sind verwaltete Profile ohne Login.
- **2026-09-28:** Ein Account kann mehrere Teams besitzen.
- **2026-09-28:** Skills legt jeder Manager pro Team selbst an, jedes Mitglied bekommt pro Skill ein Level. Die bisherigen Pseudo-Skills Basic bis Expert werden zum Erfahrungslevel.

Folge für die Roadmap: P0-2 wird zu "Multi-Team und Login nur für Account-Inhaber" statt Rollen-Membership. Einladungen, RSVP durch Teilnehmer, Notifications an Mitglieder und @mentions entfallen vorerst. Alle Zugriffe laufen über `currentTeam()` und Ownership-Checks, damit spätere Co-Manager-Logins ohne Umbau nachrüstbar bleiben.

## Offene Entscheidungen

1. Asset-Pipeline: Vite mit Build-Schritt beim Deploy, oder kompiliertes CSS weiter committen?

## Nächster Schritt

P0-2-Migrationsplan im Detail zur Freigabe vorlegen, sobald die offenen Entscheidungen 1 und 2 geklärt sind.
