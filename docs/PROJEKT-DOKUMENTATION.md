# Vereinssoftware – Projektdokumentation

## 1. Zweck

Die Vereinssoftware ist eine mandantenfähige Verwaltungsanwendung für Vereine.
Jeder Verein besitzt einen eigenen Daten- und Berechtigungskontext. Benutzer
können mehreren Vereinen angehören und innerhalb des Admin-Panels zwischen
diesen Vereinen wechseln.

Die Dokumentation richtet sich an Entwickler, Betreiber und Personen, die das
Projekt lokal einrichten oder produktiv betreiben.

## 2. Funktionsumfang

- Mitglieder mit persönlichen, Kontakt- und Adressdaten verwalten
- Mitgliedsarten, Abteilungen und Vereinsfunktionen pflegen
- zeitlich begrenzte Funktionen und Abteilungszuordnungen verwalten
- Mitgliedschaften aussetzen, reaktivieren oder beenden
- Beitragsarten, Beitragssätze und individuelle Beitragsregeln verwalten
- Dokumente zu Mitgliedern speichern und geschützt herunterladen
- Benutzer zu Vereinen einladen und Vereinskontexte wechseln
- E-Mail-Verifikation, Passwort-Reset und Zwei-Faktor-Authentifizierung
- Rollen, Berechtigungen und Audit-Protokoll

## 3. Technischer Überblick

| Bereich | Technologie |
| --- | --- |
| Backend | PHP 8.4/8.5, Laravel 13 |
| Admin-Panel | Filament 5 und Livewire |
| Lokale Datenbank | SQLite oder PostgreSQL |
| Produktion | PostgreSQL 17 |
| Authentifizierung | Laravel Auth, Sanctum und Filament Auth |
| Berechtigungen | Spatie Laravel Permission mit Teams |
| Domänenereignisse | Spatie Laravel Event Sourcing |
| Frontend | Vite und Tailwind CSS 4 |
| E-Mail | Resend, lokal optional Mailpit |
| Queue | Datenbank-Queue |
| Produktion | FrankenPHP, Docker Compose und Traefik |

## 4. Architektur

```text
HTTP/API oder Filament
        │
        ▼
Application: Commands, Handler, Manager
        │
        ▼
Domain: Aggregate, Events, Models, Policies
        │
        ├── Event Store / Projektionen
        └── Audit-Reactor
```

### 4.1 Projektstruktur

| Pfad | Zweck |
| --- | --- |
| `app/Domain` | Fachliche Modelle, Aggregate, Events, Value Objects und Policies |
| `app/Application` | Anwendungsfälle, Commands, Handler und fachliche Manager |
| `app/Filament` | Administrationsoberfläche, Ressourcen, Formulare und Tabellen |
| `app/Http` | API-Controller, Form Requests und Middleware |
| `app/Infrastructure` | Technische Infrastruktur und gespeicherte Events |
| `database/migrations` | Datenbankschema |
| `database/seeders` | Berechtigungen und Rollen |
| `tests/Feature` | Integrations-, API- und Oberflächentests |
| `tests/Unit` | Isolierte Unit-Tests |
| `resources` | Blade-, CSS- und JavaScript-Ressourcen |

### 4.2 Mitgliederverwaltung

Die Mitgliederverwaltung verwendet Event Sourcing:

1. API oder Filament authentifiziert und autorisiert die Anfrage.
2. Ein Command beschreibt die gewünschte Änderung.
3. Ein Handler lädt das `MemberAggregate` und führt die Geschäftsregel aus.
4. Das Aggregate schreibt ein Domain Event.
5. `MemberProjector` aktualisiert die lesbaren Tabellen.
6. `MemberAuditReactor` protokolliert die Änderung.

Wichtige Stellen sind:

- `app/Domain/Membership/Aggregates/MemberAggregate.php`
- `app/Domain/Membership/Events/`
- `app/Domain/Membership/Projectors/MemberProjector.php`
- `app/Domain/Membership/Reactors/MemberAuditReactor.php`
- `app/Application/Membership/Commands/`
- `app/Application/Membership/Handlers/`

### 4.3 Mandantenfähigkeit

`Club` ist der Tenant des Filament-Panels. `CurrentClub` hält den aktuellen
Vereinskontext; die Middleware `current.club` setzt ihn für API-Anfragen.

Bei jeder fachlichen Abfrage und Schreiboperation muss der Vereinskontext
berücksichtigt werden. Besonders wichtig ist das bei Mitgliedern, Beiträgen,
Dokumenten und Berechtigungen.

Die Filament-Konfiguration befindet sich in
`app/Providers/Filament/AdminPanelProvider.php`.

### 4.4 UUIDs und Fremdschlüssel

Die `members`-Tabelle verwendet nach der Migration
`2026_09_10_181647_rename_id_to_uuid_on_members_table.php` die Spalte `uuid` als
Primärschlüssel. Fremdschlüssel auf Mitglieder müssen daher auf
`members.uuid` verweisen.

## 5. Administrationsoberfläche

Das Admin-Panel ist unter `/admin` erreichbar. Ressourcen werden automatisch aus
`app/Filament/Resources` entdeckt.

Verwaltungsbereiche:

- Mitglieder
- Mitgliedsarten, Abteilungen und Vereinsfunktionen
- Vereinsbenutzer und Vereinseinladungen
- Beitragsarten und Beitragssätze
- Mitgliederdokumente

Profil, Sicherheit, Authentifizierung und Vereinsregistrierung liegen in
`app/Filament/Pages`.

## 6. Berechtigungen

Die Berechtigungen sind in `app/Domain/Identity/Enums/Permission.php` definiert.
Wichtige Gruppen sind:

- `members.*` für Mitgliederdaten und Mitgliedsbeziehungen
- `membership-types.manage`, `departments.manage` und `club-functions.manage`
- `club.users.view` und `club.users.manage`
- `members.documents.view` und `members.documents.manage`
- `contribution-types.manage` und `contribution-rates.manage`
- `contributions.view` und `members.contributions.manage`
- `audit.view` und `security.audit.view`

Initialisierung:

```bash
php artisan db:seed --class=Database\\Seeders\\PermissionSeeder
php artisan db:seed --class=Database\\Seeders\\RoleSeeder
```

## 7. Lokale Einrichtung

### Voraussetzungen

- PHP 8.4 oder 8.5 mit Laravel-Erweiterungen
- Composer 2
- Node.js 22 und npm
- SQLite oder PostgreSQL

### Installation mit SQLite

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
npm ci
npm run build
```

Entwicklung:

```bash
php artisan serve
npm run dev
```

Die Backend- und Vite-Prozesse können alternativ über `composer run dev`
gestartet werden, wenn die lokale Umgebung dies unterstützt.

### Wichtige Umgebungsvariablen

| Variable | Zweck |
| --- | --- |
| `APP_NAME`, `APP_URL`, `APP_KEY` | Anwendungsidentität und Verschlüsselung |
| `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Datenbank |
| `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION` | Sitzungen, Cache und Jobs |
| `MAIL_MAILER`, `MAIL_FROM_ADDRESS` | E-Mail-Versand |
| `RESEND_API_KEY` | Resend in Produktion |
| `REDIS_HOST`, `REDIS_PASSWORD` | Valkey/Redis in Produktion |

Produktionswerte gehören nicht in das Repository. Die Tests verwenden über
`phpunit.xml` standardmäßig SQLite mit einer In-Memory-Datenbank.

## 8. API

Die Routen befinden sich in `routes/api.php` und verwenden `auth:sanctum` sowie
`current.club`.

| Methode | Pfad | Zweck |
| --- | --- | --- |
| `POST` | `/api/members` | Mitglied registrieren |
| `POST` | `/api/club-context` | Vereinskontext wechseln |
| `PATCH` | `/api/members/{member}/address` | Adresse ändern |
| `PATCH` | `/api/members/{member}/contact-data` | Kontaktdaten ändern |
| `PATCH` | `/api/members/{member}/personal-data` | Persönliche Daten ändern |
| `PATCH` | `/api/members/{member}/membership-type` | Mitgliedsart ändern |
| `POST` | `/api/members/{member}/departments` | Abteilung zuordnen |
| `DELETE` | `/api/members/{member}/departments/{department}` | Abteilungszuordnung beenden |
| `POST` | `/api/members/{member}/functions` | Vereinsfunktion zuordnen |
| `PATCH` | `/api/members/{member}/functions/{clubFunction}/end` | Vereinsfunktion beenden |

Der geschützte Dokument-Download ist als Webroute definiert:

```text
GET /member-documents/{document}/download
```

## 9. Tests und Qualitätssicherung

```bash
# Gesamte Testsuite
php artisan test --compact

# Einzelne Testdatei
php artisan test --compact tests/Feature/Filament/MemberResourceTest.php

# PHP-Formatierung
vendor/bin/pint --dirty --format agent

# Statische Analyse
composer analyse

# Frontend
npm run format:check
npm run lint
```

Nach Änderungen an Filament-Ressourcen oder Routen sollte ein alter Route-Cache
entfernt werden:

```bash
php artisan route:clear
```

## 10. Datenbank

Wichtige Tabellen sind:

- `clubs`, `club_user` und `club_invitations`
- `members`, `membership_types`, `departments` und `club_functions`
- `member_departments`, `member_functions` und `member_documents`
- `contribution_types`, `contribution_rates` und `member_contribution_overrides`
- `stored_events`, `snapshots` und `activity_log`

Migrationen werden in zeitlicher Reihenfolge ausgeführt. Neue
Schemaänderungen werden als neue Migration angelegt. Bestehende Migrationen
sollten nach einer Auslieferung nicht rückwirkend geändert werden.

Lokale Datenbank zurücksetzen:

```bash
php artisan migrate:fresh --seed
```

Der Befehl ist destruktiv und darf nicht gegen eine produktive Datenbank
ausgeführt werden.

## 11. Docker und Produktion

`docker-compose.yaml` stellt lokal App, PostgreSQL, Valkey und Mailpit bereit:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
```

Mailpit ist unter Port `8025` erreichbar und nimmt SMTP auf Port `1025` an.

Die Produktionsumgebung in `docker-compose.deploy.yml` besteht aus:

- `app`: Laravel mit FrankenPHP und Traefik-Anbindung
- `worker`: Datenbank-Queue-Worker
- `postgres`: PostgreSQL 17
- `valkey`: persistenter Redis-kompatibler Dienst

Der Entrypoint `.deploy/entrypoint.sh` führt beim Start Migrationen, Permission-
und Role-Seeder sowie Konfigurations-, Routen- und View-Caches aus.

Manueller Rollout:

```bash
docker compose -f docker-compose.deploy.yml pull
docker compose -f docker-compose.deploy.yml up -d --remove-orphans
```

Das externe Docker-Netzwerk `traefik_public` und ein Traefik-Zertifikatsresolver
`le` müssen auf dem Zielsystem vorhanden sein.

## 12. Queue und E-Mail

In Produktion verwendet die Anwendung den Datenbank-Queue-Treiber. Der Worker
wird separat als `queue:work database` gestartet. Das betrifft insbesondere
Einladungen und E-Mail-Verifikation.

Bei ausbleibender Verarbeitung sollten zuerst Worker-Status, Queue-Tabelle,
Mailvariablen sowie die Logs von `app` und `worker` geprüft werden.

## 13. Wartung und Entwicklungskonventionen

```bash
php artisan route:list --except-vendor
php artisan config:show database.default
php artisan optimize:clear
php artisan pail
php artisan queue:work --tries=3
```

Bei neuen Änderungen gelten folgende Grundsätze:

- Tenant- und Berechtigungsprüfungen bei jeder Schreiboperation beibehalten.
- Bestehende Commands, Handler und Services wiederverwenden.
- UUID-Spalten und Fremdschlüsselziele beachten.
- Für Verhaltensänderungen Feature-Tests ergänzen oder aktualisieren.
- Vor dem Abschluss Pint und die betroffenen Tests ausführen.
- Produktions-Caches erst nach Migration und Seedern erzeugen.

