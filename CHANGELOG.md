# Changelog

Alle wesentlichen Änderungen an der Vereinssoftware werden in dieser Datei
dokumentiert. Die Struktur orientiert sich an Keep a Changelog; für zukünftige
Releases ist Semantic Versioning vorgesehen.

## [Unreleased]

### Projektstatus

Die Vereinssoftware befindet sich vor dem ersten offiziellen Release. Im
lokalen Repository sind derzeit keine Versions-Tags vorhanden. Die folgenden
Abschnitte bündeln die Entwicklung nach Funktionsbereichen; die Teilnummern
sind Arbeitsschritte und keine veröffentlichten Versionsnummern.

Der Schwerpunkt liegt auf mandantenfähiger Mitgliederverwaltung für
Schützenvereine, Beitragsverwaltung und SEPA-Lastschriften. Eine getrennt
betriebene öffentliche Vereinswebsite ist weiterhin vorgesehen.

**Statuskennzeichnung**

- **Umgesetzt:** Im aktuellen Repository implementiert; keine pauschale Zusage
  über Produktionsbetrieb oder sämtliche denkbaren Fehlerfälle.
- **Lokal geprüft:** Zusätzlich durch die dokumentierten Tests oder manuellen
  Entwicklungsprüfungen bestätigt.
- **Offen / nicht verifiziert:** Noch nicht implementiert oder im Rahmen dieses
  Abgleichs nicht bestätigt. Vorhandene Deployment-Konfiguration ist kein
  Nachweis für erfolgreichen produktiven Betrieb.

Stand des Repository-Abgleichs: 06.10.2026. Frühere technische Einzelkorrekturen
werden zusammengefasst, ohne ihnen nachträglich Release-Daten zuzuweisen.

### 1. Grundarchitektur und Infrastruktur – Teil 1

#### Hinzugefügt und geändert

- Laravel-Backend mit Filament und getrennten Domain-, Application- und
  Infrastructure-Schichten.
- PostgreSQL, Valkey, Docker Compose und FrankenPHP/Caddy.
- Mehrstufiger Docker-Build mit Composer-Installation, Frontend-Build und
  Runtime-Image sowie erforderlichen PHP-Erweiterungen.
- Lokale Entwicklungsdienste einschließlich Mailpit und Queue-Worker.
- Konfiguration für private Dateien, Storage und Cache.
- HTTPS-Verarbeitung und Asset-Auslieferung mit Regressionstest für die
  Erkennung des HTTPS-Schemas.

**Status:** Umgesetzt. Der aktuelle Laravel-Stand ist 13, nicht 12.

### 2. Continuous Integration und Deployment – Teil 2

#### Hinzugefügt und geändert

- GitHub-Actions-Workflow für `develop`, `main`, `master` und `workos`.
- PHP-Formatierung, PHPStan, Frontend-Formatierung und ESLint als CI-Schritte.
- Pest-Tests mit PostgreSQL und einer PHP-Matrix für 8.4 und 8.5.
- Frontend-Build, Docker-Image-Build und Veröffentlichung in der GitHub
  Container Registry.
- Produktionsdeployment bei Push auf `main` nach dem Test-Job.
- WireGuard-/SSH-Zugriff auf den Docker-Host, Deployment-Secrets, Migrationen
  und Laravel-Optimierung im Workflow.

**Status:** Workflow und Deployment-Konfiguration umgesetzt. Tatsächlicher
Produktionszustand, aktuelle CI-Ergebnisse und vollständige Betriebshärtung
wurden für diesen Abgleich nicht überprüft.

### 3. Vereinsverwaltung und Multi-Tenancy – Teil 3

#### Hinzugefügt

- Vereinsmodell `Club`, UUID-Identifikation und Benutzerzuordnungen.
- `CurrentClub`, Middleware und Filament-Tenancy zur Vereinsauswahl.
- Vereinsbezogene Abfragen, Policies und serverseitige Zugriffsprüfungen.
- Trennung von Benutzeridentität und Berechtigungen im jeweiligen Verein.
- Tests für Vereinsisolation in Mitglieds-, Beitrags- und SEPA-Vorgängen.

**Status:** Umgesetzt und für die vorhandenen Testfälle geprüft. Ein
vollständiges Cross-Tenant-Sicherheitsaudit bleibt offen.

### 4. Rollen und Berechtigungen – Teil 4

#### Hinzugefügt und geändert

- Spatie Laravel Permission mit vereinsbezogenen Teams und Rollenzuweisungen.
- Zentrale Permission-Definitionen, Standardrollen und Modell-Policies.
- Berechtigungsabhängige Filament-Actions und serverseitige Prüfung
  zustandsändernder Vorgänge.
- Rollenänderung und Entzug des Vereinszugangs für Benutzerkonten.
- Schutz des letzten Administrators vor Entfernung oder Herabstufung.

**Status:** Umgesetzt. Ein vollständiges Berechtigungsaudit bleibt offen.

### 5. Mitgliederverwaltung – Teil 5

#### Hinzugefügt und geändert

- `Member`, UUID-Identifikation und vereinsbezogene Mitgliedsnummern.
- Registrierung mit persönlichen Daten, Anschrift, Kontakt, Eintrittsdatum,
  Mitgliedsart und organisatorischen Zuordnungen.
- Filament-Mitgliederliste, Erfassungsassistent und gegliederte Detailansicht.
- Eigenständige Vorgänge für Änderungen persönlicher Daten, Adresse und Kontakt.
- Beziehungen zu Dokumenten, Beitragsforderungen und SEPA-Mandaten.

**Status:** Umgesetzt.

### 6. Event Sourcing und CQRS – Teil 6

#### Hinzugefügt und geändert

- Spatie Laravel Event Sourcing für wesentliche Mitgliedsvorgänge.
- Aggregate, Commands, gespeicherte Events und Projektoren für Lesemodelle.
- Ereignisbasierte Registrierung sowie Änderungen von Stammdaten,
  Mitgliedschaftsstatus, Abteilungen und Funktionen.
- Audit-Reactor sowie Aggregate-, Projektions- und Vorgangstests.
- Fachliche Schreiboperationen in Application- und Domain-Schichten statt
  direkter Änderungen an Mitglieder-Projections.

**Status:** Umgesetzt. Nicht sämtliche Modelle der Anwendung sind eventgesourct.

### 7. Mitgliedschaftsstatus und Lebenszyklus – Teil 7

#### Hinzugefügt

- Aktive, suspendierte und beendete Mitgliedschaften.
- Commands und Events für Suspendierung, Reaktivierung und Austritt.
- Prüfung von Zustandsübergängen und Nachvollziehbarkeit über gespeicherte Events.
- Geschützte Aktionen in der Mitgliederdetailansicht.

**Status:** Umgesetzt. Ein gesonderter Wiedereintritt ehemaliger Mitglieder
einschließlich seiner Historienregeln bleibt offen.

### 8. Vereinsorganisation – Teil 8.1–8.9

#### Hinzugefügt

- Mitgliedsarten, Abteilungen und Vereinsfunktionen.
- Wechsel der Mitgliedsart sowie Abteilungsbeitritt und -austritt.
- Zeitabhängige Zuweisung und Beendigung von Vereinsfunktionen.
- Filament-Formulare und Actions sowie laufende und historische Zuordnungen.
- Vereinsbezogene Filterung und Berechtigungsprüfung.

**Status:** Umgesetzt. Vereinsfunktionen sind von Benutzer-Zugriffsrollen getrennt.

### 9. Benutzerkonten, Authentifizierung und Einladungen – Teil 8.10–8.11

#### Hinzugefügt und geändert

- Registrierung, Anmeldung, Passwort-Reset und E-Mail-Verifikation.
- Zwei-Faktor-Authentifizierung per Authenticator-App mit Wiederherstellungscodes.
- Profilverwaltung, Passwortänderung und Verwaltung anderer Anmeldesitzungen.
- Vereinseinladungen mit Ablauf, Annahme, Widerruf und erneutem Versand.
- Gehashte Einladungstokens; erneuter Versand ersetzt den Token.
- Prüfung der eingeladenen E-Mail-Adresse und vereinsbezogene Rollenzuweisung.
- Benutzerverwaltung mit Schutz des letzten Administrators.
- Tests für Authentifizierung, Verifikation, Profil, Sicherheit und Einladungen.

**Status:** Umgesetzt. Einladungsverwaltung und Schutz des letzten Administrators
sind nicht mehr lediglich geplant. Ein umfassendes Account-Sicherheitsaudit
bleibt offen.

### 10. E-Mail-Versand – Teil 8.11

#### Hinzugefügt

- Resend-Integration und Konfiguration für System-E-Mails.
- Verifikations- und Einladungskommunikation.
- Lokales Mailpit sowie Mail-Konfiguration und Secrets im Deployment-Konzept.

**Status:** Implementiert. Produktionsversand, DNS-/Cloudflare-Zustand und
Zustellbarkeit wurden für diesen Abgleich nicht extern verifiziert.
Vereinsverteiler, Versandhistorie und fachlicher Massenversand bleiben offen.

### 11. Mitgliedsdokumente – Teil 8.12

#### Hinzugefügt

- `MemberDocument` und Dokumenttypen für Anträge, Mandate, Einwilligungen,
  Bescheinigungen und sonstige Unterlagen.
- Filament-Upload mit Dateiname, MIME-Type, Größe, Prüfsumme und Benutzerzuordnung.
- Private Speicherung, geschützte Downloads und Prüfung der Vereinszuordnung.
- Einschränkung akzeptierter Dateitypen und Dateigrößen.
- Dokumentenliste sowie Modell-, Upload- und Downloadtests.

**Status:** Umgesetzt. Malware-Scanning und ein abschließendes Upload-Audit
bleiben offen. Ein Mandatsdokument ersetzt nicht die strukturierte Erfassung
eines SEPA-Mandats.

### 12. Beitragsverwaltung – Teil 8.13a–8.13d

#### Hinzugefügt

- `ContributionType` und `ContributionRate` mit einmaligen, monatlichen,
  vierteljährlichen, halbjährlichen und jährlichen Beitragsintervallen.
- Allgemeine und mitgliedsartspezifische Beitragssätze mit Gültigkeitszeitraum.
- `MemberContributionOverride` für individuelle Beträge und Befreiungen mit
  Begründung und zeitlicher Gültigkeit.
- `ContributionResolver` mit Vorrang individueller Regeln und allgemeinen
  Beitragssätzen als Fallback.
- `ContributionCharge` mit Betragssnapshot, Zeitraum, Fälligkeit und den
  Zuständen offen, bezahlt und storniert.
- Einzelforderungen, manuelle Zahlungsmarkierung, Stornierung und Duplikatschutz.
- `ContributionRun` mit Chunk-Verarbeitung, Ergebnissen, Befreiungen,
  übersprungenen Duplikaten und Fehlererfassung.
- Filament-Oberflächen, Auditierung sowie Resolver-, Manager-, Forderungs-,
  Lauf-, Policy- und Oberflächentests.

#### Geändert

- Beitragssatzänderungen verändern bereits erzeugte Forderungen nicht.
- Beitragsläufe berücksichtigen aktive und suspendierte Mitglieder;
  Suspendierung ist keine automatische Beitragsbefreiung.
- Sammelläufe werden über die Oberfläche angestoßen; ein Beitragsintervall
  allein richtet keine zeitgesteuerte Abrechnung ein.

**Status:** Umgesetzt und durch vorhandene Tests abgedeckt, nicht nur als
Implementierung beschrieben. Automatisch geplante beziehungsweise
Queue-basierte Beitragsläufe und vollständiger Zahlungsabgleich bleiben offen.

### 13. SEPA-Mandate, Vereinskonfiguration und Lastschriftläufe – Teil 8.14a–8.14c

#### Hinzugefügt

- `SepaMandate` mit Referenz, Unterschriftsdatum, Gültigkeitsbeginn,
  aktivem, widerrufenem oder abgelaufenem Status und Widerruf mit Begründung.
- IBAN-Format- und Prüfsummenvalidierung, verschlüsselte IBAN-/BIC-Speicherung
  und maskierte Anzeige.
- Separate Berechtigungen für Mandatsverwaltung und Bankdaten sowie
  Mandats- und Autorisierungstests.
- `ClubSepaConfiguration` mit Gläubiger-ID, Vereinskonto, Referenzpräfix,
  Vorlauftagen, Standard-Verwendungszweck und Aktivierung.
- Manager, automatische Mandatsreferenzen und Filament-SEPA-Einstellungen.
- `SepaDebitRun` und `SepaDebitItem` zur Vorbereitung offener positiver
  Forderungen bis zum gewählten Einzugsdatum.
- Prüfung der Konfiguration, des internen Mindestvorlaufs und aktiver Mandate.
- Verschlüsselte Positionssnapshots, Anzahl, Gesamtsumme und Vorbereitungsfehler.
- Schutz vor mehrfach aktiven Positionen und Stornierung nicht exportierter
  Läufe über den Application-Dienst.
- Filament-Laufübersicht sowie Konfigurations-, Vorbereitungs- und Stornierungstests.

#### Geändert

- Forderungen bleiben nach Vorbereitung und Export offen, bis eine tatsächliche
  Zahlung gesondert bestätigt wird.
- Positionssnapshots bleiben erhalten; vor dem Export wird zusätzlich der
  aktuelle Forderungs- und Mandatsstatus geprüft.

**Status:** Umgesetzt. Mandatswiderruf und Laufstornierung sind als Dienste
vorhanden, derzeit aber nicht als eigene Aktionen in der beschriebenen
Filament-Oberfläche. Bankspezifische Vorgaben sind separat zu prüfen.

### 14. SEPA-XML-Export und Download – Teil 8.14d

**Status:** Umgesetzt und lokal geprüft. Bankeinreichung und tatsächliche
Bankannahme sind nicht Bestandteil dieser Fertigstellung.

#### 2026-10-06 – Technische Änderungen und verifizierte Prüfungen

Dieser Eintrag dokumentiert die SEPA-Export-Erweiterung aus Commit
`cdc712f39bdfa62b6c8db6d0bf3285aa5162231e`, einschließlich Audit,
Regressionstests und der lokalen Prüfung mit Testdaten aus den Arbeitsschritten
8.14d.23 bis 8.14d.26. Die lokale Prüfung und die angelegten Testdaten sind
Entwicklungsartefakte und nicht Bestandteil des Commits.

#### Hinzugefügt

##### Export von vorbereiteten Lastschriftläufen

- `ExportSepaDebitRun` erstellt eine SEPA-XML-Datei für einen vorbereiteten Lauf.
- Das Exportformat ist `pain.008.001.08` mit dem Namespace
  `urn:iso:std:iso:20022:tech:xsd:pain.008.001.08`.
- Vor dem Export werden der aktuelle Verein, der Laufstatus und die
  SEPA-Konfiguration geprüft.
- Der Lauf wird innerhalb einer Datenbanktransaktion mit `lockForUpdate()`
  gesperrt, damit parallele Exportaufrufe denselben Lauf nicht gleichzeitig
  bearbeiten.
- Bereits exportierte Läufe mit hinterlegtem Speicherpfad werden unverändert
  zurückgegeben. Bestehende Exportdateien und Exportmetadaten bleiben erhalten.
- Nach erfolgreichem Export wird der Status auf `exported` gesetzt und der
  Exportzeitpunkt gespeichert.
- Ein Export markiert Forderungen nicht als bezahlt. Die Lastschriftpositionen
  bleiben im Status `prepared`.

##### Fachliche Exportvalidierung

`ValidateSepaDebitRunForExport` prüft vor der XML-Erzeugung:

- Der Lauf ist vorbereitet und enthält mindestens eine Position.
- Die SEPA-Konfiguration gehört zum Verein des Laufs und ist aktiv.
- Es bestehen keine ungeklärten Vorbereitungsfehler.
- Jede Position gehört zum Verein des Laufs und hat den Status `prepared`.
- Die zugehörige Forderung existiert, ist offen und ist dem richtigen Verein
  und Mitglied zugeordnet.
- Das zugehörige Mandat existiert, ist aktiv, ist richtig zugeordnet und ist
  spätestens am Einzugstag gültig.
- Die IBANs bestehen die Format- und Prüfsummenprüfung.
- Für Gläubiger und Lastschriftpositionen liegen gültig formatierte BICs vor.
- Beträge sind positiv; Kontoinhaber, Mandatsreferenz und Verwendungszweck sind
  ausgefüllt.
- Das Unterschriftsdatum des Mandats liegt nicht nach dem Einzugstag.
- Positionsanzahl und Gesamtsumme stimmen mit den gespeicherten Laufwerten
  überein. Beträge werden mit BCMath und zwei Nachkommastellen verarbeitet.

##### XML-Erzeugung und Schema-Prüfung

- `Pain008XmlBuilder` erzeugt das XML mit `DOMDocument` und XML-Textknoten.
  Sonderzeichen in Namen und Verwendungszwecken werden korrekt maskiert.
- Die Datei enthält Gruppeninformationen, Zahlungsinformationen und für jede
  Position ein `DrctDbtTxInf`-Element.
- Nachrichten-, Zahlungsinformations- und End-to-End-Kennungen werden aus den
  UUIDs des Laufs beziehungsweise der Positionen abgeleitet.
- Zahlungsinformationen enthalten unter anderem `DD`, `SEPA`, `CORE`, `RCUR`,
  `EUR`, `SLEV`, Einzugsdatum, Gläubiger-ID und Mandatsdaten.
- `ValidatePain008Xml` prüft XML-Syntax und Übereinstimmung mit dem gebündelten
  EPC-Schema. Ungültiges XML wird vor der Speicherung abgelehnt.
- Beim Parsen wird `LIBXML_NONET` verwendet. Der vorherige Zustand der
  libxml-Fehlerbehandlung wird nach der Prüfung wiederhergestellt.
- Folgende Schemas liegen unter `resources/sepa/xsd/`:
  - `EPC130-08_2025_V1.0_pain.008.001.08.xsd` – für die Exportvalidierung verwendet.
  - `EPC130-08_2025_V1.0_pain.007.001.09.xsd` – zusätzlich hinterlegt.
  - `EPC130-08_2025_V1.0_pain.002.001.10.xsd` – zusätzlich hinterlegt.

##### Verschlüsselte Speicherung und Integritätsprüfung

- XML wird mit `Crypt::encryptString()` verschlüsselt auf dem lokalen,
  privaten Storage-Disk gespeichert.
- Das Pfadmuster lautet `sepa/exports/{club_id}/{run_id}.xml.enc`.
- Die Anwendung speichert keine unverschlüsselte XML-Exportdatei auf diesem
  Disk.
- Für den XML-Klartext wird eine SHA-256-Prüfsumme berechnet und am Lauf
  gespeichert.
- Bei Fehlern während Speicherung, Aktualisierung oder Audit-Protokollierung
  wird die Exportdatei im bestehenden Fehlerpfad entfernt und die
  Datenbanktransaktion zurückgerollt.

##### Geschützter XML-Download

- Neuer Controller: `SepaDebitRunDownloadController`.
- Neue GET-Route: `/sepa-debit-runs/{run}/download`.
- Name der Route: `sepa-debit-runs.download`.
- Die Route verwendet Authentifizierung und `SetCurrentClub`.
- Die Policy verlangt die Berechtigung `sepa-debit-runs.export` und die
  Zugehörigkeit des Laufs zum aktuellen Verein.
- Zugriffe auf Läufe anderer Vereine liefern HTTP 404; fehlende
  Exportberechtigungen führen zu HTTP 403.
- Nur exportierte Läufe mit Speicherpfad, Prüfsumme und vorhandener Datei
  können heruntergeladen werden.
- Vor dem Download wird die Datei entschlüsselt und die SHA-256-Prüfsumme mit
  `hash_equals()` geprüft. Eine abweichende Prüfsumme führt zu HTTP 500.
- Der Klartext wird als Stream mit dem Dateinamen
  `sepa-{YYYY-MM-DD}-{run_id}.xml` bereitgestellt.
- Antwortheader:
  - `Content-Type: application/xml; charset=UTF-8`
  - `Cache-Control: private, no-store`
  - `X-Content-Type-Options: nosniff`

##### Aktionen in der Oberfläche

- Die Detailseite eines Lastschriftlaufs bietet „SEPA-XML erstellen“ für
  vorbereitete Läufe mit Exportberechtigung an.
- Vor dem Export wird eine Bestätigung verlangt.
- Nach erfolgreichem Export erscheint eine Erfolgsmeldung; der Datensatz wird
  neu geladen.
- Für exportierte Läufe mit Exportberechtigung erscheint „XML herunterladen“.
  Der Download öffnet in einem neuen Tab.

#### Audit – 8.14d.23

- Der vorhandene `AuditLogger` protokolliert erfolgreiche Exporte mit
  `AuditAction::SepaDebitRunExported` beziehungsweise
  `sepa.debit-run.exported`.
- Ein wiederholter Aufruf für einen bereits exportierten Lauf erzeugt keinen
  weiteren Export-Audit-Eintrag.
- Neues Ereignis: `AuditAction::SepaDebitRunDownloaded` beziehungsweise
  `sepa.debit-run.downloaded`.
- Jeder Download, der die Berechtigungs-, Datei- und Integritätsprüfungen
  erfolgreich durchläuft, erzeugt einen eigenen Download-Eintrag.
- Beide Ereignisse speichern folgende Laufmetadaten:
  - `club_id`
  - `run_id`
  - `xml_format`
  - `items_count`
  - `total_amount`
  - `xml_sha256`
- Akteur und vorhandene Request-Metadaten werden durch den `AuditLogger`
  ergänzt.
- XML-Inhalte, IBANs, BICs, Kontoinhaber und vollständige personenbezogene
  Bankinformationen werden nicht in diesen Audit-Ereignissen gespeichert.
- Abgelehnte oder beschädigte Downloads erzeugen keinen erfolgreichen
  Download-Eintrag. Das Audit bestätigt die serverseitige Bereitstellung,
  nicht den vollständigen Empfang auf dem Client.

#### Datenbank und Laufzeitvoraussetzungen

- Migration `2026_10_06_182932_add_xml_export_to_sepa_debit_runs_table` ergänzt
  die nullable Spalten `xml_format`, `xml_storage_path`, `xml_sha256` und
  `xml_generated_at`.
- `SepaDebitRun` unterstützt die neuen Exportattribute und castet
  `xml_generated_at` als unveränderlichen Zeitstempel.
- Migration `2026_10_06_192348_fix_sepa_debit_item_uniqueness` ersetzt den
  uneingeschränkten Unique-Index auf `contribution_charge_id` durch einen
  partiellen Unique-Index für Positionen mit `status = 'prepared'`.
- Dadurch kann eine Forderung nach einer Stornierung erneut vorbereitet
  werden, während stornierte Positionen als Historie erhalten bleiben.
- PHP-Erweiterungen `ext-bcmath`, `ext-dom` und `ext-libxml` sind in
  `composer.json` als Voraussetzungen eingetragen.
- Verschlüsselung und Entschlüsselung verwenden die bestehende Laravel-
  Schlüsselkonfiguration. Exportdateien benötigen weiterhin den passenden
  Anwendungsschlüssel.

#### Behoben

##### Typinformationen für PHPStan

- Die Beziehung `SepaDebitRun::items()` ist als
  `HasMany<SepaDebitItem, $this>` dokumentiert.
- `SepaDebitItem::charge()` und `SepaDebitItem::mandate()` enthalten konkrete
  generische Rückgabetypen.
- `ViewSepaDebitRun` dokumentiert `SepaDebitRun` als Rückgabetyp von
  `getRecord()`.
- Damit erkennt PHPStan die konkreten Positionen, Beziehungen und
  Filament-Datensätze ohne Fehlerunterdrückung.

##### Veraltete Caches in Tests

- `phpunit.xml` setzt eigene Cache-Pfade für Tests:
  - `APP_CONFIG_CACHE=bootstrap/cache/config-testing.php`
  - `APP_ROUTES_CACHE=bootstrap/cache/routes-testing.php`
- Beide Variablen werden mit `force="true"` gesetzt.
- Tests übernehmen dadurch nicht die vorhandenen Konfigurations- und
  Route-Caches der normalen Anwendung. Das behebt insbesondere den Fehler
  „Route [sepa-debit-runs.download] not defined“ durch einen veralteten Cache.

##### Explizite Mandatsreferenzen

- `SepaMandateManager` trimmt eine übergebene Mandatsreferenz und übernimmt
  sie, wenn sie nicht leer ist.
- Nur bei leerer oder ausschließlich aus Leerzeichen bestehender Eingabe
  wird `MandateReferenceGenerator` verwendet.
- Eine explizite Referenz benötigt deshalb keine SEPA-Konfiguration für die
  Referenzerzeugung. Automatische Referenzerzeugung benötigt weiterhin eine
  Konfiguration.
- Bestehende Tests für IBAN-Validierung, Referenz-Eindeutigkeit, Widerruf und
  Berechtigungen erreichen wieder das jeweils zu prüfende Verhalten.

#### Automatisierte Tests – 8.14d.24

Die Tests verwenden Pest, `RefreshDatabase`, vorhandene Factories und für
Dateizugriffe einen lokalen Storage-Fake.

##### XML-Export und Download

`tests/Feature/Sepa/SepaXmlExportTest.php` enthält 28 Testfälle, einschließlich
der Dataset-Varianten:

- Erfolgreicher Export und Download eines vorbereiteten Laufs.
- Ablehnung eines leeren Laufs und eines Laufs mit Vorbereitungsfehlern.
- Unveränderte Exportdatei und Metadaten bei wiederholtem Export.
- Ablehnung stornierter und noch nicht vorbereiteter Läufe.
- Ablehnung bezahlter und stornierter Forderungen.
- Ablehnung widerrufener, abgelaufener und erst später gültiger Mandate.
- Ablehnung stornierter Positionen.
- Ablehnung einer falschen Gesamtsumme oder Positionsanzahl.
- Ablehnung fehlender BICs, inaktiver Konfiguration und ungültiger IBANs.
- Vereinsisolation bei Export und Download.
- Zwei getrennte `DrctDbtTxInf`-Elemente mit korrekten Beträgen,
  Mandatsreferenzen und unterschiedlichen End-to-End-Kennungen.
- Korrekte SEPA-Zahlungsinformationen und XML-Maskierung von Sonderzeichen.
- Verschlüsselte Speicherung ohne XML-Klartext auf dem Export-Disk.
- Erfolgreiche XSD-Prüfung für einen und zwei Zahlungsposten.
- Ablehnung schemawidriger XML-Dokumente und schemawidriger Exportdaten vor
  der Speicherung.
- Ablehnung von Downloads bei falscher Prüfsumme oder fehlender Berechtigung.

##### Audit und Oberfläche

- `tests/Feature/SepaDebitRunAuditTest.php` enthält zehn Testfälle für
  Export-Audit, wiederholte Downloads, erlaubte Metadaten und fehlende
  Erfolgseinträge bei Validierungs-, Datei-, Integritäts- und Zugriffsfehlern.
- Zwei zusätzliche Testfälle in
  `tests/Feature/Filament/SepaDebitRunResourceTest.php` prüfen die Sichtbarkeit
  der XML-Aktionen für vorbereitete und exportierte Läufe.
- Vier zusätzliche Regressionstestfälle in
  `tests/Feature/SepaMandateManagerTest.php` prüfen explizite Referenzen,
  automatische Referenzen bei leerer oder aus Leerzeichen bestehender
  Eingabe sowie die Ablehnung automatischer Erzeugung ohne Konfiguration.

##### Verifizierter Stand

| Prüfung | Ergebnis |
| --- | --- |
| Vollständige Pest-Suite | 339 Tests bestanden, 1.510 Assertions |
| PHPStan über den Anwendungscode | 0 Fehler |
| `vendor/bin/pint --dirty --format agent` | Erfolgreich |
| `git diff --check` für die Codeänderungen | Erfolgreich |

Diese Ergebnisse stammen aus den ausgeführten Prüfungen vor der Erstellung
dieses Changelog-Eintrags.

#### Lokale Prüfung mit Testdaten – 8.14d.25

In der lokalen Docker-Entwicklungsumgebung wurde ein separater
„SEPA-Testverein – nur Testdaten“ mit einem eigenen Testkonto angelegt.
Zugangsdaten werden nicht im Changelog dokumentiert.

| Mitglied | Testforderung | Mandat |
| --- | --- | --- |
| Max Mustermann | 120,00 € | Aktiv |
| Anna Beispiel | 60,00 € | Aktiv |
| Peter Beispiel | 80,00 € | Kein Mandat |

1. Der erste Lauf berücksichtigte Max und Anna mit zwei Positionen und
   180,00 €, enthielt aber wegen Peters fehlendem Mandat einen
   Vorbereitungsfehler. Der Export wurde erwartungsgemäß blockiert.
2. Der erste Lauf wurde storniert. Peters offene Testforderung wurde auf
   den 17.10.2026 verschoben, sodass sie außerhalb des neuen Einzugstermins
   liegt. Die Forderung wurde weder bezahlt noch storniert.
3. Ein neuer Lauf für den 16.10.2026 wurde vorbereitet und exportiert:
   zwei Positionen, 180,00 €, null Fehler, Format `pain.008.001.08`.
4. Zwei XML-Transaktionen, XSD-Konformität, verschlüsselte Speicherung und
   SHA-256-Prüfsumme wurden geprüft.
5. Der Download wurde durch den lokalen HTTP-Kernel ausgeführt. Er lieferte
   HTTP 200 und denselben XML-Inhalt wie die entschlüsselte Exportdatei.
6. Export- und Download-Audit wurden einschließlich der erlaubten Metadaten
   geprüft. Der lokale Route-Cache wurde geleert, damit die neue Downloadroute
   in der laufenden Anwendung verfügbar ist.

Die Prüfung erfolgte über Anwendungsdienste und HTTP-Kernel. Eine visuelle
Browserprüfung wurde dabei nicht durchgeführt. Die Testdaten wurden direkt
in der lokalen Entwicklungsdatenbank angelegt; ein zusätzlicher Demo-Seeder
wurde nicht erstellt.

#### Lokale XML-Prüfung – 8.14d.26

Der XML-Download des Testlaufs wurde als temporäre lokale Datei gespeichert:

```text
/tmp/sepa-test-01a112cf-6e87-7268-bafd-fb4fdbb143c1.xml
```

Ausgeführte Syntaxprüfung:

```bash
xmllint --nonet --noout \
  /tmp/sepa-test-01a112cf-6e87-7268-bafd-fb4fdbb143c1.xml
```

Ausgeführte Schema-Prüfung aus dem Projektverzeichnis:

```bash
xmllint --nonet --noout \
  --schema resources/sepa/xsd/EPC130-08_2025_V1.0_pain.008.001.08.xsd \
  /tmp/sepa-test-01a112cf-6e87-7268-bafd-fb4fdbb143c1.xml
```

Beide Befehle endeten mit Exit-Code 0; die Schema-Prüfung meldete
`validates`. Die temporäre Datei ist kein Repository-Artefakt und kann nach
einer Bereinigung des temporären Verzeichnisses fehlen.

#### Hinweise zur Inbetriebnahme

- Neue Datenbankmigrationen vor der Nutzung des Exports ausführen.
- Beim Einsatz eines Route-Caches diesen nach der Aktualisierung neu
  erstellen oder mit `php artisan route:clear` leeren.
- PHP-Erweiterungen, Anwendungsschlüssel, privaten Storage und das verwendete
  EPC-XSD-Schema müssen verfügbar sein.
- Ein Lauf mit ungeklärten Vorbereitungsfehlern muss vor dem Export fachlich
  bereinigt oder durch einen neuen fehlerfreien Lauf ersetzt werden.
- Syntax- und XSD-Konformität bestätigen nicht sämtliche bankfachlichen oder
  bankspezifischen Anforderungen und keine tatsächliche Annahme durch eine
  Bank.
- Alle manuellen Prüfungen verwendeten ausschließlich fiktive Zahlungsdaten.
  Keine Testdatei wurde an eine Bank übermittelt. Die erzeugten Testdateien
  dürfen nicht für echte Zahlungsaufträge verwendet werden.

### 15. Technische Qualität, Sicherheit und Dokumentation

#### Hinzugefügt und geändert

- PHPStan/Larastan, Laravel Pint und Pest mit Unit- und Feature-Tests.
- Tests für Domain-Vorgänge, Event Sourcing, Policies, Vereinsisolation,
  Dokumente, Beiträge, Benutzerkonten und SEPA.
- Projektspezifischer `AuditLogger` auf Basis von Spatie Activitylog mit
  Mitglieds-, Benutzer-, Beitrags- und SEPA-Ereignissen.
- Private Dateispeicherung, verschlüsselte Bankdaten, E-Mail-Verifikation und 2FA.
- Technische Projektdokumentation und dieses zusammengeführte Changelog.
- Deutsches [Benutzerhandbuch](docs/BENUTZERHANDBUCH.md) mit Arbeitsabläufen,
  Testdaten, Fehlerhilfe, Checklisten und ausdrücklich beschriebenen UI-Grenzen;
  Verlinkung in der README.
- Filament-Seite **Hilfe → Benutzerhandbuch** mit formatierter Anzeige der
  Markdown-Quelldatei, Tabellen und Inhaltsverzeichnis-Ankern. Der Zugriff ist
  für angemeldete Benutzer des ausgewählten Vereins ohne zusätzliche
  Verwaltungsberechtigung möglich. HTML und unsichere Links werden gefiltert.
- Fünf Feature-Tests für Handbuchanzeige, Navigation, Anmeldung,
  Vereinszugriff und sichere Markdown-Ausgabe: bestanden mit 17 Assertions.
  PHPStan für die neue Seite und Pint wurden erfolgreich ausgeführt.

**Verifizierter Stand:** Die zuletzt ausgeführte vollständige Pest-Suite
bestand mit 339 Tests und 1.510 Assertions; PHPStan meldete keine Fehler und
Pint lief erfolgreich. Details und Prüfgrenzen stehen in Abschnitt 14. Für
das damalige Zusammenführen der Dokumentation wurden diese Codeprüfungen
nicht erneut ausgeführt. Die später ergänzte Handbuchseite wurde gesondert
mit den oben genannten fünf Feature-Tests und PHPStan geprüft.

**Noch offen / nicht vollständig verifiziert:**

- Vollständiges Policy-, Cross-Tenant- und Account-Sicherheitsaudit.
- Verschlüsselungs-, Schlüsselrotations-, Backup- und Wiederherstellungskonzept.
- Zusätzliche Upload-Prüfungen und Malware-Scanning.
- Erweiterte Race-Condition-, Belastungs- und Wiederanlauftests.
- Bankfachliche Gesamtprüfung und produktiver End-to-End-Zahlungsvorgang.

### 16. Bekannte offene Themen

#### Zahlungen

- Bankeinreichung und Bankbestätigung von SEPA-Dateien.
- Rücklastschriften, automatisierte Zahlungszuordnung und Teilzahlungen.
- Salden, Mahnwesen und vollständiges Zahlungsjournal.
- Bankdatei-Import und automatischer Zahlungsabgleich.

Eine manuelle Zahlungsmarkierung einzelner Forderungen ist bereits umgesetzt;
sie ist nicht mit einem vollständigen Zahlungseingangs- oder Bankjournal gleichzusetzen.

#### Mitglieder und Vereinsverwaltung

- Automatische Mitgliedsnummern und gesonderter Wiedereintritt.
- DSGVO-Datenauskunft und vollständige fachliche Mitglieder-Timeline als Oberfläche.
- Erweiterte Such-, Filter- und Exportfunktionen über den vorhandenen Umfang hinaus.
- Vollständige Pflege erweiterter Vereinsstammdaten; die Vereinsregistrierung
  speichert derzeit nicht alle dort angezeigten Kontakt- und Adressfelder.
- Erweitertes Vereinsdashboard, Veranstaltungen, Termine und Trainings.
- Vereinskommunikation und frei konfigurierbare Rollen-/Berechtigungsverwaltung
  über den bisherigen Umfang hinaus.
- Eigene UI-Aktionen für Mandatswiderruf und Laufstornierung sowie eine
  Audit-Auswertungsoberfläche.

#### Website-Integration

- Getrennte öffentliche Website und versionierte Website-API.
- Öffentliche Vereinsdaten, News und Veranstaltungen.
- Website-spezifische Berechtigungen, Authentifizierung und Rate-Limiting-Konzept.

Eine Sanctum-geschützte Verwaltungs-API für Mitgliedsvorgänge und Vereinskontext
ist bereits vorhanden; sie ist nicht die geplante öffentliche Website-API.

#### Betrieb

- Produktionsmonitoring, automatisierte Backups und Wiederherstellungstests.
- Verifizierung des produktiven Queue-Betriebs und dessen Überwachung.
- Vollständiges Security-Audit und Production-Readiness-Checkliste.
- Release-Dokumentation und verifizierte Deployment-Ergebnisse.

Ein Queue-Worker ist bereits in der lokalen Docker-Compose-Konfiguration
enthalten; dessen Existenz bestätigt keinen überwachten produktiven Betrieb.

### 17. Geplantes Release

#### [1.0.0] – Geplant, ohne festgelegtes Veröffentlichungsdatum

Der erste stabile Release soll mindestens folgende Bereiche verlässlich abdecken:

- Mandantenfähige Vereins- und Mitgliederverwaltung mit Rollen und Berechtigungen.
- Ereignisbasierte Mitgliedsvorgänge, Mitgliedsarten, Abteilungen und Funktionen.
- Geschützte Dokumente, E-Mail-Verifikation und Zwei-Faktor-Authentifizierung.
- Beitragssätze, individuelle Regeln, Forderungen und Beitragsläufe.
- SEPA-Mandate und validierter XML-Export mit sicherem Download und Auditierung.
- Nachvollziehbare Zahlungsverwaltung und Prüfung des gesamten Zahlungsvorgangs.
- Verlässliche Backups und nachgewiesene Wiederherstellung.
- Sicherheits- und Integrationstests sowie stabiler CI/CD-Prozess.

Diese Liste beschreibt Release-Ziele, nicht den Nachweis ihrer produktiven
Erfüllung. Ein Einsatz mit echten Finanzdaten setzt insbesondere die Prüfung
der jeweiligen Bankvorgaben und des gesamten Zahlungsverfahrens voraus.

### Technologiestack

Die PHP-Paketversionen wurden mit `composer show --direct` im lokalen
Arbeitsverzeichnis abgeglichen. Sie beschreiben diesen Entwicklungsstand,
nicht notwendigerweise eine produktive Installation.

| Bereich | Technologie / lokaler Stand |
| --- | --- |
| Backend | Laravel 13.30.1 |
| Programmiersprache | PHP; Docker-Laufzeit 8.5, CI-Matrix 8.4 / 8.5 |
| Admin-Oberfläche | Filament 5.8.1 |
| Datenbank | PostgreSQL 17 im Docker-Setup |
| Cache / Queue-Infrastruktur | Valkey 8 im Docker-Setup |
| API-Authentifizierung | Laravel Sanctum 4.3.3 |
| Event Sourcing | Spatie Laravel Event Sourcing 7.15.1 |
| Berechtigungen | Spatie Laravel Permission 8.3.0 |
| Auditing | Spatie Activitylog 5.1.1 / projektspezifischer AuditLogger |
| E-Mail | Resend PHP 1.15.0; lokal Mailpit |
| Webserver | FrankenPHP / Caddy |
| Container | Docker / Docker Compose |
| CI/CD | GitHub Actions / GitHub Container Registry |
| Deployment-Zugriff | WireGuard / SSH |
| SEPA | ISO 20022 / pain.008.001.08; Export implementiert |
| Tests | Pest 5.1.4 / Pest Laravel Plugin 5.0.1 |
| Statische Analyse | Larastan 3.12.0 / PHPStan |

Debian als Produktionshost und Cloudflare als DNS-Anbieter wurden im
Entwicklungsentwurf genannt. Ihr aktueller Betriebszustand wurde nicht
überprüft und ist keine durch das Repository belegte Release-Eigenschaft.

### Hinweis zur Versionierung

Alle bisherigen Änderungen werden vorläufig unter `Unreleased` geführt.
Die historischen Arbeitsschritte sind keine eigenständigen Releases. Erst
nach Festlegung, Prüfung und Tagging eines Release-Stands werden daraus
datierte Versionseinträge, beispielsweise `0.1.0` oder `1.0.0`.

Weiterführende Dokumentation:
[Benutzerhandbuch](docs/BENUTZERHANDBUCH.md) und
[technische Projektdokumentation](docs/PROJEKT-DOKUMENTATION.md).
