# Vereinssoftware

Eine mandantenfähige Verwaltungssoftware für Vereine. Jeder Verein arbeitet
in einem eigenen Kontext und verwaltet dort Mitglieder, Mitgliedsarten,
Abteilungen, Vereinsfunktionen und Benutzerzugänge.

Das Administrationsinterface steht nach der Anmeldung unter `/admin` bereit.

## Funktionsumfang

- Mitglieder mit persönlichen Daten, Kontakt- und Adressdaten verwalten
- Mitgliedsarten, Abteilungen und Vereinsfunktionen je Verein pflegen
- Mitglieder Abteilungen zuordnen und Funktionen mit Gültigkeitszeitraum
  vergeben
- Mitgliedschaften aussetzen, reaktivieren oder beenden
- Benutzer per Einladung zu einem Verein hinzufügen
- E-Mail-Verifikation, Passwort-Reset und Zwei-Faktor-Authentifizierung
- Änderungsprotokoll für Mitgliedsaktionen

## Architektur

Die Anwendung basiert auf Laravel 13 und Filament 5. Die fachliche
Mitgliederverwaltung folgt einem ereignisbasierten Ansatz:

1. Commands und Handler in `app/Application/Membership` führen Änderungen aus.
2. `MemberAggregate` prüft die Geschäftsregeln und speichert Domain Events.
3. `MemberProjector` erzeugt daraus die lesbaren Datenmodelle (`members`,
   Abteilungs- und Funktionszuordnungen).
4. `MemberAuditReactor` protokolliert relevante Änderungen im Audit-Log.

Die Mandantentrennung erfolgt über das Filament-Tenant-Modell `Club`. Ein
Benutzer kann mehreren Vereinen angehören; Berechtigungen werden mit
`spatie/laravel-permission` verwaltet.

## Voraussetzungen

- PHP 8.4 oder 8.5 mit den für Laravel erforderlichen Erweiterungen
- Composer 2
- Node.js 22 und npm
- Für die lokale Standardkonfiguration: SQLite
- Für Produktion: Docker Compose v2 sowie ein vorhandenes Docker-Netzwerk
  `traefik_public`

## Lokale Entwicklung

```bash
composer install
cp .env.example .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate
npm ci
npm run dev
```

Der Vite-Entwicklungsserver beobachtet Frontend-Dateien. In einem zweiten
Terminal kann Laravel mit folgendem Befehl gestartet werden:

```bash
php artisan serve
```

Ohne laufenden Vite-Server werden die Assets einmalig gebaut:

```bash
npm run build
```

Die lokale Datenbank ist standardmäßig SQLite. Für PostgreSQL müssen die
`DB_*`-Variablen in `.env` gesetzt werden.

## Wichtige Befehle

```bash
# Tests
php artisan test --compact

# PHP-Formatierung
vendor/bin/pint --format agent

# Statische Analyse
composer analyse

# Frontend-Formatierung und Linting
npm run format:check
npm run lint
```

## Projektstruktur

| Pfad | Zweck |
| --- | --- |
| `app/Domain` | Fachliche Modelle, Aggregate, Events, Value Objects und Policies |
| `app/Application` | Anwendungsfälle, Commands und Handler |
| `app/Filament` | Administrationsoberfläche und Ressourcen |
| `app/Http` | API-Controller, Requests und Middleware |
| `app/Infrastructure` | Technische Infrastruktur, etwa gespeicherte Events |
| `database/migrations` | Datenbankschema |
| `tests/Feature` | Integrations- und Oberflächentests |
| `.deploy/entrypoint.sh` | Produktionsstart: Migrationen, Rollen, Caches, FrankenPHP |

## API

Die API ist durch Laravel Sanctum und den aktuellen Vereinskontext geschützt.
Sie stellt Endpunkte zum Anlegen und Ändern von Mitgliedern sowie für
Mitgliedsarten, Abteilungen und Funktionen bereit. Alle API-Anfragen benötigen
eine authentifizierte Sitzung bzw. ein Sanctum-Token und einen gültigen
Vereinskontext.

Die Routen sind in `routes/api.php` definiert.

## Produktion und Deployment

Ein Push auf `main` startet den GitHub-Actions-Workflow:

1. Formatierung, statische Analyse, Frontend-Linting und Tests laufen.
2. Ein Multi-Stage-Docker-Image wird gebaut und in GitHub Container Registry
   (`ghcr.io`) mit `latest` und dem Commit-SHA veröffentlicht.
3. Der Runner verbindet sich über WireGuard und SSH mit dem Produktionsserver.
4. Docker Compose zieht das neue Image, startet die Dienste und optimiert
   Laravel.

Die Produktionsumgebung wird durch
[`docker-compose.deploy.yml`](docker-compose.deploy.yml) beschrieben. Sie
besteht aus folgenden Diensten:

| Dienst | Aufgabe |
| --- | --- |
| `app` | Laravel auf FrankenPHP; über Traefik unter `https://${APP_DOMAIN}` erreichbar |
| `postgres` | PostgreSQL 17 für Anwendungs-, Cache-, Session- und Queue-Daten |
| `valkey` | Passwortgeschützter, persistenter Redis-kompatibler Dienst |

`postgres_data`, `app_storage` und `valkey_data` sind benannte Docker-Volumes
und bleiben bei einem Container-Neustart erhalten. Nur `app` ist mit dem
externen Traefik-Netzwerk verbunden; Datenbank und Valkey bleiben im internen
Netzwerk.

Beim Containerstart führt `.deploy/entrypoint.sh` automatisch Migrationen aus,
legt Berechtigungen und die Administratorrolle an und baut Konfigurations-,
Routen- und View-Caches auf.

### Produktionsvariablen

Die CI erzeugt die Compose-Umgebung aus GitHub-Variablen und -Secrets. Diese
Werte werden benötigt:

| Variable | Quelle | Zweck |
| --- | --- | --- |
| `APP_NAME`, `APP_DOMAIN`, `LOG_LEVEL` | GitHub Variables | Anwendungsname, öffentliche Domain und Log-Level |
| `APP_KEY` | GitHub Secret | Laravel-Anwendungsschlüssel |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | GitHub Secrets | PostgreSQL-Zugangsdaten |
| `REDIS_PASSWORD` | GitHub Secret | Passwort für Valkey |
| `RESEND_KEY` | GitHub Secret | API-Schlüssel für den Versand über Resend |
| `DEPLOY_HOST`, `DEPLOY_PORT`, `DEPLOY_USER`, `DEPLOY_SSH_PRIVATE_KEY`, `SSH_KNOWN_HOSTS` | GitHub Secrets | SSH-Zugang zum Produktionsserver |
| `WG_PRIVATE_KEY`, `WG_SERVER_PUBLIC_KEY`, `WG_ENDPOINT` | GitHub Secrets | WireGuard-Verbindung zum Produktionsnetz |

`APP_IMAGE` wird durch den Workflow automatisch auf das Image des aktuellen
Commits gesetzt. Das externe Docker-Netzwerk `traefik_public` und ein
funktionierender Traefik-Resolver namens `le` müssen auf dem Zielserver bereits
vorhanden sein.

### Manueller Rollout

Für einen manuellen Rollout wird eine nicht versionierte Umgebungsdatei mit den
oben genannten Werten benötigt. Anschließend:

```bash
set -a
source ./.deploy.env
set +a
docker compose -f docker-compose.deploy.yml pull
docker compose -f docker-compose.deploy.yml up -d --remove-orphans
docker compose -f docker-compose.deploy.yml exec -T app php artisan optimize
```

Die Migrationen laufen bereits im Entrypoint; bei einem manuellen Rollout
können sie zusätzlich explizit ausgeführt werden:

```bash
docker compose -f docker-compose.deploy.yml exec -T app php artisan migrate --force
```
