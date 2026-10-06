# Vereinssoftware – Benutzerhandbuch

**Stand: 6. Oktober 2026**

Dieses Handbuch richtet sich an Personen, die Mitglieder, Beiträge und
Benutzerzugänge eines Vereins verwalten. Es beschreibt die vorhandene
Browseroberfläche einschließlich des SEPA-XML-Exports.

Welche Menüpunkte und Aktionen du siehst, hängt von deinen Berechtigungen im
ausgewählten Verein ab. Einige automatisch erzeugte Bezeichnungen können
englisch erscheinen. Bei den folgenden Anleitungen sind die ausdrücklich
genannten Aktionsnamen maßgeblich.

## Inhalt

1. [Die wichtigsten Begriffe](#1-die-wichtigsten-begriffe)
2. [Anmelden und den richtigen Verein auswählen](#2-anmelden-und-den-richtigen-verein-auswählen)
3. [Die Oberfläche bedienen](#3-die-oberfläche-bedienen)
4. [Einen Verein einrichten](#4-einen-verein-einrichten)
5. [Mitglieder verwalten](#5-mitglieder-verwalten)
6. [Dokumente verwalten](#6-dokumente-verwalten)
7. [Beiträge und Forderungen verwalten](#7-beiträge-und-forderungen-verwalten)
8. [SEPA einrichten und Mandate erfassen](#8-sepa-einrichten-und-mandate-erfassen)
9. [Einen Lastschriftlauf vorbereiten und exportieren](#9-einen-lastschriftlauf-vorbereiten-und-exportieren)
10. [Mit Testdaten üben](#10-mit-testdaten-üben)
11. [Benutzer und Einladungen verwalten](#11-benutzer-und-einladungen-verwalten)
12. [Profil und Kontosicherheit](#12-profil-und-kontosicherheit)
13. [Änderungen und Audit-Protokoll](#13-änderungen-und-audit-protokoll)
14. [Fehlermeldungen und Problemlösung](#14-fehlermeldungen-und-problemlösung)
15. [Derzeitige Grenzen der Oberfläche](#15-derzeitige-grenzen-der-oberfläche)
16. [Checklisten für den Alltag](#16-checklisten-für-den-alltag)

## 1. Die wichtigsten Begriffe

| Begriff | Bedeutung |
| --- | --- |
| Verein | Der ausgewählte Arbeitsbereich mit eigenen Mitgliedern und Berechtigungen. |
| Benutzerkonto | Dein persönlicher Zugang zur Software. Ein Konto kann mehreren Vereinen zugeordnet sein. |
| Mitglied | Eine im Verein verwaltete Person. Ein Mitglied erhält durch die Anlage nicht automatisch ein Benutzerkonto. |
| Mitgliedsart | Eine Kategorie wie „Ordentliches Mitglied“ oder „Jugendmitglied“. |
| Abteilung | Eine organisatorische Gruppe innerhalb des Vereins. |
| Vereinsfunktion | Eine Aufgabe eines Mitglieds, beispielsweise Vorsitz oder Kassenführung. Sie ist von einer Benutzerrolle zu unterscheiden. |
| Beitragsart | Die Art eines Beitrags, beispielsweise Jahresbeitrag oder Aufnahmegebühr. |
| Beitragssatz | Der gültige Betrag einer Beitragsart, gegebenenfalls für eine bestimmte Mitgliedsart. |
| Beitragsregel | Eine individuelle Betragsregel oder Beitragsbefreiung für ein Mitglied. |
| Forderung | Ein konkreter offener, bezahlter oder stornierter Beitrag für ein Mitglied und einen Zeitraum. |
| Beitragslauf | Ein Vorgang, der für mehrere Mitglieder Forderungen erzeugt. |
| SEPA-Mandat | Die erfassten Angaben zur Lastschriftermächtigung eines Mitglieds. |
| Lastschriftlauf | Die Zusammenstellung fälliger offener Forderungen für einen SEPA-Export. |
| Exportiert | Eine XML-Datei wurde erzeugt und gespeichert. Das bedeutet noch keinen Zahlungseingang. |

Der übliche Beitragsablauf ist:

**Beitragsart und Sätze einrichten → Forderungen erzeugen → Lastschriftlauf
vorbereiten → Positionen prüfen → XML exportieren und herunterladen →
Zahlungseingänge abgleichen.**

## 2. Anmelden und den richtigen Verein auswählen

### 2.1 Erste Anmeldung

1. Öffne die Verwaltungsoberfläche deiner Installation. Die Adresse erhältst
   du von der Person, die die Software betreibt.
2. Melde dich mit deiner E-Mail-Adresse und deinem Passwort an.
3. Bestätige deine E-Mail-Adresse, wenn die Anwendung dich dazu auffordert.
4. Wenn du Zwei-Faktor-Authentifizierung eingerichtet hast, gib zusätzlich
   den Code deiner Authenticator-App ein.
5. Wähle den Verein aus, in dem du arbeiten möchtest.

Wenn du noch kein Benutzerkonto besitzt, nutze die Registrierung oder eine
Einladung deines Vereins.

### 2.2 Eine Einladung annehmen

1. Öffne den Einladungslink aus der E-Mail.
2. Falls bereits ein Konto für die eingeladene E-Mail-Adresse existiert,
   melde dich mit diesem Konto an.
3. Andernfalls registriere dich mit der eingeladenen E-Mail-Adresse. Diese
   Adresse ist bei einer Registrierung über den Einladungslink vorgegeben.
4. Folge den weiteren Anmelde- und Bestätigungsschritten.
5. Prüfe nach der Anmeldung, ob der eingeladene Verein zur Auswahl steht.

Eine Einladung kann ablaufen oder widerrufen werden. Bitte in diesem Fall
die Vereinsadministration um einen neuen Link. Eine Einladung für eine
andere E-Mail-Adresse lässt sich nicht einfach mit deinem Konto übernehmen.

### 2.3 Zwischen Vereinen wechseln

Nutze die Vereinsauswahl der Oberfläche. Prüfe vor Änderungen, Beitragsläufen
und Exporten immer den aktuell ausgewählten Verein. Deine Berechtigungen
können in jedem Verein unterschiedlich sein.

### 2.4 Passwort vergessen

Nutze die Funktion zum Zurücksetzen des Passworts auf der Anmeldeseite. Gib
die E-Mail-Adresse deines Benutzerkontos ein und folge dem Link aus der
E-Mail. Wenn die Nachricht nicht ankommt, prüfe den Spamordner und wende dich
an die Betreuung der Installation.

## 3. Die Oberfläche bedienen

### 3.1 Orientierung

| Bereich oder Menüpunkt | Wofür du ihn verwendest |
| --- | --- |
| Mitglieder | Mitglieder suchen, aufnehmen und ihre Detailseiten öffnen. |
| Stammdaten → Mitgliedsarten | Kategorien für Mitgliedschaften pflegen. |
| Stammdaten → Abteilungen | Vereinsabteilungen pflegen. |
| Stammdaten → Vereinsfunktionen | Funktionen für die Zuordnung zu Mitgliedern pflegen. |
| Stammdaten → Beitragsarten | Beitragsarten und ihren Rhythmus pflegen. |
| Stammdaten → Beitragssätze | Beträge und Gültigkeitszeiträume hinterlegen. |
| Beitragsläufe | Forderungen für mehrere Mitglieder erzeugen und Ergebnisse prüfen. |
| SEPA-Läufe, gegebenenfalls „Sepa Debit Runs“ | Die Liste mit der Aktion „Lastschriftlauf vorbereiten“. |
| Einstellungen → SEPA | Das Gläubigerkonto und die SEPA-Einstellungen pflegen. |
| Administration → Benutzer | Vereinszugänge und Rollen verwalten. |
| Administration → Einladungen | Einladungen prüfen, erneut senden oder widerrufen. |
| Konto → Sicherheit | Eigene aktive Sitzungen prüfen und beenden. |
| Benutzermenü → Profil | Name, E-Mail-Adresse, Passwort und Zwei-Faktor-Authentifizierung verwalten. |

### 3.2 Suchen und Listen verwenden

- Verwende das Suchfeld einer Liste für die dort durchsuchbaren Angaben.
  In der Mitgliederliste sind unter anderem Mitgliedsnummer, Vorname und
  Nachname durchsuchbar.
- Bei sortierbaren Spalten kannst du über die Spaltenüberschrift die
  Reihenfolge ändern.
- Einige Listen bieten eine Spaltenauswahl. Damit blendest du zusätzliche
  Angaben wie Telefonnummern oder Austrittsdaten ein.
- Öffne einen Datensatz über seine Ansicht-Aktion. Änderungen an Mitgliedern
  erfolgen überwiegend über die Aktionen in der Detailansicht.
- Nicht jede Liste hat eigene Filter. Nutze die Suche, wenn kein passender
  Filter angeboten wird.

### 3.3 Speichern und Rückmeldungen

Formulare zeigen fehlende oder ungültige Angaben an. Ergänze diese und
speichere erneut. Kontrolliere danach die Erfolgsmeldung und die gespeicherten
Werte. Eine fehlende Schaltfläche kann durch Berechtigungen oder den aktuellen
Status des Datensatzes bedingt sein.

## 4. Einen Verein einrichten

### 4.1 Verein anlegen

Wenn du einen neuen Verein betreuen möchtest, nutze „Verein anlegen“ in der
Vereinsauswahl beziehungsweise im Einrichtungsablauf.

1. Gib den Vereinsnamen ein.
2. Ergänze bei Bedarf einen Kurznamen.
3. Schließe die Anlage ab.
4. Wähle den neuen Verein aus.

Die Person, die den Verein anlegt, wird diesem Verein mit der Rolle
„Administrator“ zugeordnet. Der Betreiber muss die benötigten Rollen und
Berechtigungen eingerichtet haben.

Im aktuellen Vereinsanlageformular werden auch Kontaktfelder angezeigt.
Der Anlagevorgang speichert derzeit jedoch nur Vereinsname und Kurzname.
Verlasse dich für die Vereinskontaktdaten noch nicht auf dieses Formular.

### 4.2 Stammdaten zuerst vorbereiten

Lege vor der Aufnahme vieler Mitglieder die benötigten Mitgliedsarten,
Abteilungen und Vereinsfunktionen an. So kannst du sie direkt bei der
Mitgliederanlage auswählen.

Die Formulare enthalten jeweils:

| Feld | Verwendung |
| --- | --- |
| Bezeichnung | Der Name, der in Auswahllisten erscheint. |
| Beschreibung | Eine zusätzliche Erläuterung. |
| Sortierung | Die gewünschte Reihenfolge; kleinere Werte werden in entsprechend sortierten Auswahllisten früher berücksichtigt. |
| Aktiv | Kennzeichnet, ob der Eintrag für neue Zuordnungen angeboten werden soll. |

Öffne den passenden Menüpunkt, lege einen Eintrag an und speichere ihn.
Vorhandene Einträge kannst du über die Bearbeiten-Aktion pflegen. Wenn ein
Eintrag künftig nicht mehr verwendet werden soll, kannst du ihn deaktivieren.

## 5. Mitglieder verwalten

### 5.1 Ein Mitglied aufnehmen

1. Öffne **Mitglieder**.
2. Klicke auf **Mitglied anlegen**.
3. Durchlaufe den Assistenten:

| Schritt | Angaben |
| --- | --- |
| Mitgliedschaft | Mitgliedsnummer, Eintrittsdatum und optional Mitgliedsart. |
| Persönliche Daten | Vorname, Nachname und optional Geburtsdatum. |
| Adresse | Straße, Hausnummer, PLZ, Ort und zweistelliger Ländercode. |
| Kontakt | E-Mail-Adresse, Telefon und Mobiltelefon. |
| Abteilungen | Eine oder mehrere vorhandene aktive Abteilungen. |
| Vereinsfunktionen | Vorhandene aktive Funktionen mit Beginn der Gültigkeit. |

4. Prüfe die Angaben und schließe die Aufnahme ab.
5. Suche das neue Mitglied in der Liste und öffne seine Detailansicht.

Mitgliedsnummern müssen innerhalb eines Vereins eindeutig sein. Die
Pflichtangaben umfassen Mitgliedsnummer, Eintrittsdatum, Vorname und Nachname.
Ein Geburtsdatum darf nicht in der Zukunft liegen.

### 5.2 Persönliche Daten, Kontakt und Anschrift ändern

Öffne das Mitglied und nutze **Bearbeiten** im jeweiligen Abschnitt
„Persönliche Daten“, „Kontakt“ oder „Anschrift“. Speichere die Änderungen
und kontrolliere die angezeigten Werte.

Die Bearbeitungsmöglichkeit hängt von deiner Berechtigung ab. Auch der
Zugriff auf persönliche Daten kann gesondert eingeschränkt sein.

### 5.3 Mitgliedsart ändern

1. Öffne im Abschnitt **Mitgliedschaft** das Menü **Aktionen**.
2. Wähle die Aktion zum Ändern der Mitgliedsart.
3. Wähle die passende Mitgliedsart und bestätige die Änderung.

Die Mitgliedsart kann beeinflussen, welcher Beitragssatz bei einer späteren
Beitragsberechnung verwendet wird. Bereits erzeugte Forderungen werden
durch einen Wechsel nicht automatisch neu berechnet.

### 5.4 Mitglied sperren, reaktivieren oder Austritt erfassen

Diese Aktionen stehen im Abschnitt **Mitgliedschaft → Aktionen** zur
Verfügung, soweit Status und Berechtigungen sie zulassen.

| Aktion | Ergebnis |
| --- | --- |
| Mitglied sperren | Das Mitglied erhält den Status `suspended`, also ausgesetzt. |
| Mitglied reaktivieren | Das Mitglied erhält wieder den Status `active`. |
| Austritt erfassen | Das Mitglied erhält den Status `left`; Austrittsdatum und Begründung werden erfasst. |

Fülle die angezeigten Datums- und Begründungsfelder aus und bestätige die
Aktion. Der bisherige Datensatz bleibt erhalten.

**Eine Sperre bewirkt keine automatische Beitragsbefreiung.** Beitragsläufe
berücksichtigen im aktuellen Stand aktive und ausgesetzte Mitglieder.
Wenn Beiträge entfallen sollen, hinterlege eine passende Beitragsregel.
Bereits offene Forderungen werden durch Sperre oder Austritt nicht automatisch
bezahlt oder storniert.

### 5.5 Abteilungen zuordnen

1. Öffne den Abschnitt **Abteilungen** in der Mitgliedsdetailseite.
2. Nutze das Aktionsmenü, um ein Mitglied einer Abteilung zuzuordnen.
3. Wähle die Abteilung und das angezeigte Zuordnungsdatum.
4. Um eine Zuordnung zu beenden, nutze **Abteilung verlassen** und erfasse
   das Austrittsdatum.

### 5.6 Vereinsfunktionen verwalten

Im Abschnitt **Vereinsfunktionen** kannst du eine Funktion mit einem
Gültigkeitsbeginn zuweisen oder eine bestehende Funktion beenden. Die
beendeten Zuordnungen bleiben in der **Funktionshistorie** nachvollziehbar.

Eine Vereinsfunktion wie „Kassenwart“ erteilt dem Mitglied nicht automatisch
einen Softwarezugang oder eine entsprechende Benutzerrolle.

## 6. Dokumente verwalten

### 6.1 Dokument hochladen

1. Öffne die Detailseite des Mitglieds.
2. Klicke im Abschnitt **Dokumente** auf **Dokument hochladen**.
3. Wähle den Dokumenttyp:
   - Mitgliedsantrag
   - SEPA-Mandat
   - Einwilligung
   - Bescheinigung
   - Sonstiges
4. Wähle die Datei und speichere den Upload.
5. Kontrolliere Dateiname, Dokumenttyp und Uploadzeitpunkt.

Die Oberfläche akzeptiert **PDF, JPEG und PNG** mit maximal **10 MiB pro
Datei**. Die Dokumente werden privat gespeichert und über einen geschützten
Download bereitgestellt.

### 6.2 Dokument herunterladen

Klicke in der Dokumentübersicht des Mitglieds auf den Dateinamen. Der
Download erfordert eine passende Dokumentberechtigung.

Ein als Dokument hochgeladenes SEPA-Mandat ersetzt nicht die Erfassung der
Mandatsdaten im Abschnitt **SEPA-Mandat**. Für den Lastschriftlauf werden die
dort hinterlegten strukturierten Angaben benötigt.

## 7. Beiträge und Forderungen verwalten

### 7.1 Beitragsart anlegen

1. Öffne **Stammdaten → Beitragsarten**.
2. Lege eine Beitragsart an.
3. Trage Bezeichnung, optionalen Code und Beschreibung ein.
4. Wähle den Rhythmus: einmalig, monatlich, vierteljährlich, halbjährlich oder
   jährlich.
5. Prüfe Sortierung und Aktiv-Schalter und speichere.

Der Rhythmus beschreibt die Beitragsart. Er löst in der beschriebenen
Oberfläche nicht selbstständig einen wiederkehrenden Beitragslauf aus.
Berechnungsdatum und Beitragszeitraum werden beim Erzeugen von Forderungen
festgelegt.

### 7.2 Beitragssätze hinterlegen

1. Öffne **Stammdaten → Beitragssätze**.
2. Lege einen Satz für die gewünschte aktive Beitragsart an.
3. Wähle eine Mitgliedsart oder lasse die Auswahl für einen allgemeinen Satz
   leer.
4. Trage den Betrag in Euro ein.
5. Gib **Gültig ab** und bei Bedarf **Gültig bis** an.
6. Aktiviere den Satz und speichere.

Das Enddatum darf nicht vor dem Startdatum liegen. Ein allgemeiner Satz
gilt als Rückfall für Mitglieder ohne passenden spezifischen Satz.

Bei der Berechnung hat eine passende individuelle Beitragsregel Vorrang.
Ohne solche Regel verwendet die Anwendung einen gültigen aktiven Satz für
die Mitgliedsart und andernfalls einen gültigen allgemeinen Satz. Vermeide
mehrdeutige überschneidende Sätze für denselben Anwendungsfall.

### 7.3 Individuellen Betrag oder Beitragsbefreiung hinterlegen

1. Öffne das Mitglied.
2. Wähle oben **Beiträge → Beitragsregel hinzufügen**.
3. Wähle die Beitragsart.
4. Wähle **Individueller Betrag** oder **Beitragsbefreit**.
5. Gib bei einem individuellen Betrag die Höhe an.
6. Erfasse den Gültigkeitszeitraum und bei Bedarf eine Begründung.
7. Speichere die Regel.

Für dieselbe Beitragsart eines Mitglieds dürfen sich aktive individuelle
Regeln nicht überschneiden. Die Regel wird bei späteren Berechnungen
berücksichtigt; sie ändert keine bereits erzeugte Forderung.

### 7.4 Eine einzelne Forderung erzeugen

1. Öffne das Mitglied.
2. Wähle im Menü **Beiträge** die Aktion zum Erstellen einer Beitragsforderung.
3. Wähle Beitragsart und Berechnungsdatum.
4. Gib den Zeitraum, das Fälligkeitsdatum und die Beschreibung an.
5. Bestätige die Erstellung.
6. Prüfe den Abschnitt **Beitragsforderungen**.

Der Betrag wird aus den gültigen Sätzen und individuellen Regeln ermittelt.
Das Berechnungsdatum bestimmt, welche davon gelten. Der Zeitraum bezeichnet
die abgerechnete Leistung; das Fälligkeitsdatum ist der Zahlungstermin.

### 7.5 Forderungen für mehrere Mitglieder erzeugen

1. Öffne **Beitragsläufe**.
2. Wähle **Beitragslauf erstellen**.
3. Fülle das Formular aus:

| Feld | Bedeutung |
| --- | --- |
| Beitragsart | Welche Beiträge berechnet werden sollen. |
| Berechnungsdatum | Stichtag für Mitgliedsauswahl, gültige Beitragssätze und individuelle Regeln. |
| Zeitraum von / Zeitraum bis | Der abzurechnende Zeitraum. |
| Fällig am | Zahlungstermin der erzeugten Forderungen. |
| Beschreibung | Text der Forderung und Grundlage für den späteren Lastschrift-Verwendungszweck. |

4. Prüfe die Angaben und bestätige den Lauf.
5. Lies die Ergebnisnachricht und öffne die Laufdetails.

Die Anwendung berücksichtigt aktive und ausgesetzte Mitglieder, deren
Eintrittsdatum spätestens am Berechnungsdatum liegt. Ausgetretene Mitglieder
werden in diesem Sammellauf nicht berücksichtigt.

| Ergebnisangabe | Bedeutung |
| --- | --- |
| Forderungen | Neu erstellte Beitragsforderungen. |
| Beitragsfrei | Mitglieder, für die keine positive Forderung erzeugt wurde. |
| Übersprungen | Bereits vorhandene, nicht stornierte Forderungen für dieselbe Beitragsart und denselben Zeitraum. |
| Fehler | Mitglieder, deren Beitrag nicht verarbeitet werden konnte. |
| Summe | Gesamtsumme der neu erzeugten Forderungen. |

Prüfe die Fehlerliste auch dann, wenn die Meldung „Beitragslauf abgeschlossen“
erscheint. Nach einer fachlichen Korrektur kann ein erneuter Lauf bereits
vorhandene Forderungen überspringen und fehlende ergänzen. Verwende dafür
dieselbe Beitragsart und denselben Zeitraum und prüfe das Ergebnis.

### 7.6 Eine Forderung als bezahlt markieren

1. Gleiche den tatsächlichen Zahlungseingang ab.
2. Öffne das Mitglied und wähle **Beiträge → Forderung bezahlen**.
3. Wähle die offene Forderung und trage **Bezahlt am** ein.
4. Bestätige **Als bezahlt markieren**.

Diese Aktion erfasst einen Zahlungseingang in der Software. Sie löst keine
Bankzahlung aus. Ein SEPA-Export allein ist kein Grund, eine Forderung als
bezahlt zu markieren.

### 7.7 Eine offene Forderung stornieren

Öffne **Beiträge → Forderung stornieren**, wähle die offene Forderung und
erfasse die erforderliche Begründung. Die stornierte Forderung bleibt mit
Stornodatum und Begründung sichtbar.

Nutze eine Stornierung für eine fachlich nicht mehr bestehende oder falsch
angelegte Forderung. Eine gültige Forderung ohne SEPA-Mandat bleibt eine
offene Forderung und muss auf einem anderen vorgesehenen Weg beglichen
werden.

## 8. SEPA einrichten und Mandate erfassen

### 8.1 Gläubigerdaten des Vereins hinterlegen

1. Öffne **Einstellungen → SEPA**.
2. Fülle die SEPA-Konfiguration aus:

| Feld | Bedeutung |
| --- | --- |
| Gläubiger-ID | Kennung des Vereins als Lastschriftgläubiger. |
| Kontoinhaber | Name des Inhabers des Gläubigerkontos. |
| IBAN | Konto, auf dem die eingezogenen Beiträge eingehen sollen. |
| BIC | Bankkennung; für den aktuellen XML-Export erforderlich. |
| Mandatspräfix | Präfix für automatisch erzeugte Mandatsreferenzen, beispielsweise `SV-`. |
| Standard-Vorlauftage | Mindestabstand zwischen Vorbereitung und gewünschtem Einzugsdatum; Standardwert fünf Tage. |
| Standard-Verwendungszweck | Hinterlegter Standardtext. Der aktuelle Lastschriftlauf übernimmt seinen konkreten Verwendungszweck aus der jeweiligen Forderungsbeschreibung. |
| SEPA-Lastschriften aktiv | Aktiviert die Konfiguration für die Vorbereitung. |

3. Speichere die Konfiguration.

Die Vorlauftage dürfen zwischen 0 und 30 liegen. Sie sind eine Einstellung
der Software; die tatsächlichen Termine und Anforderungen deiner Bank sind
gesondert zu berücksichtigen.

Obwohl die BIC im Formular kein Pflichtfeld ist, blockiert der aktuelle
Exportvalidator den Export ohne gültige BIC. Das gilt für das Gläubigerkonto
und für jede Lastschriftposition.

### 8.2 Mandatsdaten beim Mitglied erfassen

1. Öffne die Mitgliedsdetailseite.
2. Nutze im Abschnitt **SEPA-Mandat** die Aktion zum Anlegen eines Mandats.
3. Trage Mandatsreferenz, Kontoinhaber, IBAN und BIC ein.
4. Gib **Mandat erteilt am** und bei Bedarf **Gültig ab** an.
5. Speichere das Mandat.
6. Lade bei Bedarf zusätzlich das zugehörige Mandatsdokument unter
   **Dokumente** hoch.

Die Mandatsreferenz wird im aktuellen Formular ausdrücklich eingegeben.
Sie muss innerhalb des Vereins eindeutig sein. Für das aktuelle XML-Format
sollte sie höchstens 35 Zeichen umfassen. Ohne ein angegebenes Gültigkeitsdatum
verwendet die Anwendung das Unterschriftsdatum.

Pro Mitglied kann nur ein aktives Mandat über diese Verwaltung angelegt
werden. Bei einem notwendigen Wechsel oder Widerruf wende dich an eine
berechtigte Person beziehungsweise die technische Betreuung; dafür ist
derzeit keine eigene Schaltfläche in der beschriebenen Mitgliedsseite
vorhanden.

### 8.3 Anzeige der Bankdaten

Die Mitgliedsdetailseite und die Positionsdetails eines Lastschriftlaufs
zeigen die IBAN derzeit maskiert mit ihren letzten vier Zeichen. Das ist
keine beschädigte IBAN. Bankdaten und die Verwaltung von Mandaten können
gesonderte Berechtigungen verlangen.

## 9. Einen Lastschriftlauf vorbereiten und exportieren

### 9.1 Voraussetzungen prüfen

Für den Lauf benötigst du:

- Den richtigen ausgewählten Verein und die notwendigen Berechtigungen.
- Eine aktive SEPA-Konfiguration mit gültiger IBAN und BIC.
- Fällige offene Forderungen mit positiven Beträgen.
- Aktive, am Einzugstag gültige Mandate für die zu belastenden Mitglieder.
- Vollständige Mandats- und Bankdaten einschließlich BIC.
- Einen Einzugstermin, der den eingestellten Mindestvorlauf erfüllt.

Ein Lastschriftlauf erzeugt keine neuen Beitragsforderungen. Er verwendet
bereits vorhandene Forderungen.

### 9.2 Lauf vorbereiten

1. Öffne die Liste der SEPA-Lastschriftläufe.
2. Wähle **Lastschriftlauf vorbereiten**.
3. Gib eine aussagekräftige **Bezeichnung** ein, zum Beispiel
   „Jahresbeiträge 2026 – Oktober“.
4. Wähle das **Gewünschte Einzugsdatum**.
5. Bestätige die Vorbereitung.
6. Prüfe die Ergebnisnachricht und öffne den neuen Lauf.

Die Vorbereitung verarbeitet die bis zum Einzugsdatum fälligen offenen
Forderungen mit positivem Betrag im aktuellen Verein. Bereits in einer
Position mit Status `prepared` verwendete Forderungen werden übersprungen.
Eine manuelle Auswahl einzelner Mitglieder wird in diesem Dialog nicht
angeboten.

### 9.3 Positionen und Fehler kontrollieren

Die Übersicht zeigt **Bezeichnung**, **Einzug**, **Status**, **Positionen**,
**Fehler**, **Summe** und den Erstellungszeitpunkt.

In der Detailansicht:

1. Prüfe Anzahl und Gesamtsumme.
2. Öffne die Liste **Positionen**.
3. Kontrolliere Mitglied, Betrag, Mandatsreferenz und Verwendungszweck.
4. Nutze **Details**, um eine Position genauer anzusehen.
5. Prüfe die Liste **Fehler** und die betroffenen Mitgliedsnummern.

**Ein vorbereiteter Lauf kann gültige Positionen und trotzdem Fehler
enthalten.** Ein fehlendes Mandat eines anderen Mitglieds führt zu einem
Vorbereitungsfehler. Solange ein solcher Fehler am Lauf hinterlegt ist,
blockiert der Exportvalidator den gesamten Lauf.

### 9.4 Fehlerhaften Lauf bereinigen

1. Kläre jede Fehlermeldung fachlich, zum Beispiel ein fehlendes Mandat oder
   fehlerhafte Bankdaten.
2. Beachte, dass die Vorbereitung Angaben als Positionen übernimmt. Spätere
   Änderungen am Mitglied oder Mandat aktualisieren diese Positionen nicht
   automatisch.
3. Lass nötigenfalls den fehlerhaften Lauf durch eine berechtigte Person
   über den vorgesehenen Anwendungsvorgang stornieren.
4. Bereite anschließend einen neuen Lauf mit den korrigierten Daten vor.
5. Prüfe den neuen Lauf erneut auf Positionen, Gesamtsumme und null Fehler.

Für das Stornieren eines Lastschriftlaufs gibt es derzeit keine eigene
Aktion in der beschriebenen Laufdetailseite. Die technische Betreuung kann
den vorhandenen Stornierungsvorgang ausführen. Einfach einen weiteren Lauf
anzulegen reicht bei bereits reservierten Forderungen nicht aus.

Fehlerzahlen und Summen dürfen nicht durch direkte technische Eingriffe
„passend gemacht“ werden. Es müssen die zugrunde liegenden Daten stimmen.

### 9.5 XML-Datei erstellen

1. Öffne den geprüften vorbereiteten Lauf.
2. Wähle **SEPA-XML erstellen**.
3. Bestätige den Exportdialog.
4. Warte auf die Meldung **SEPA-XML erfolgreich erstellt**.

Beim Export prüft die Anwendung unter anderem, ob Forderungen weiterhin
offen und Mandate weiterhin gültig sind. Sie prüft außerdem Positionsanzahl,
Gesamtsumme und das XML-Schema.

Nach erfolgreichem Export erhält der Lauf den Status **Exportiert**. Das
Format ist **`pain.008.001.08`**. Die Exportdatei wird verschlüsselt gespeichert
und mit einer Prüfsumme versehen.

Ein bereits exportierter Lauf wird bei einem erneuten Exportaufruf nicht
neu erzeugt. Der bestehende Dateiinhalt bleibt erhalten. Wenn sich Daten
nach dem Export ändern müssen, ist eine fachliche Klärung und gegebenenfalls
ein neuer Lauf erforderlich.

### 9.6 XML herunterladen

1. Öffne den exportierten Lauf.
2. Klicke auf **XML herunterladen**.
3. Speichere die Datei an einem dafür vorgesehenen Ort.

Die Datei heißt nach dem Muster `sepa-YYYY-MM-DD-LAUF-ID.xml`. Der Download
erfordert eine Exportberechtigung und prüft die Integrität der gespeicherten
Datei. Ein erneuter Download stellt denselben vorhandenen Export bereit.

Die heruntergeladene XML-Datei enthält lesbare Bankdaten. Die Verschlüsselung
der serverseitigen Speicherung schützt nicht automatisch die Datei in
deinem Downloadordner.

### 9.7 Export und Zahlungseingang unterscheiden

Die Software stellt die XML-Datei bereit. Sie übermittelt sie in diesem
Arbeitsablauf nicht automatisch an eine Bank und bestätigt keinen
Zahlungseingang. Kläre den vorgesehenen Einreichungsweg und die spezifischen
Anforderungen mit deiner zuständigen Stelle.

Gleiche später die tatsächlichen Zahlungseingänge ab und markiere die
zutreffenden Forderungen als bezahlt. Reiche denselben Export nicht allein
deshalb erneut ein, weil eine Forderung in der Software noch offen ist.

### 9.8 Status eines Lastschriftlaufs

| Status | Bedeutung |
| --- | --- |
| Entwurf / `draft` | Der Lauf ist noch nicht exportfähig vorbereitet. |
| Vorbereitet / `prepared` | Positionen sind zusammengestellt; Fehler und Daten müssen geprüft werden. |
| Exportiert / `exported` | Eine geprüfte Exportdatei wurde erzeugt. Das bestätigt keine Bankverarbeitung. |
| Storniert / `cancelled` | Der Lauf wurde über den Stornierungsvorgang beendet. Seine stornierten Positionen bleiben als Historie erhalten. |

Ein bereits exportierter Lauf kann über den normalen Vorbereitungs-
Stornierungsvorgang nicht rückgängig gemacht werden. Eine notwendige
Korrektur muss fachlich geklärt werden, insbesondere wenn die Datei bereits
außerhalb der Software verwendet wurde.

## 10. Mit Testdaten üben

Nutze vor dem ersten echten Lastschriftlauf einen eigenen Testverein und
ausschließlich ausdrücklich gekennzeichnete Testdaten. Fiktive Bankdaten
müssen trotzdem die technischen Format- und Prüfsummenprüfungen bestehen.
Lass entsprechende Testdaten bei Bedarf durch die technische Betreuung
bereitstellen.

### 10.1 Beispiel

| Mitglied | Testforderung | Mandat |
| --- | --- | --- |
| Max Mustermann | 120,00 € | Aktiv |
| Anna Beispiel | 60,00 € | Aktiv |
| Peter Beispiel | 80,00 € | Kein Mandat |

Wenn alle drei Forderungen zum Einzugstermin fällig sind, lautet das
erwartete Vorbereitungsergebnis:

- Zwei gültige Positionen.
- Gesamtsumme 180,00 €.
- Ein Fehler wegen Peters fehlendem Mandat.
- Der Export wird abgelehnt.

Für einen exportfähigen Testlauf müssen nur die beiden gültigen Forderungen
für den gewählten Einzugstermin relevant sein. Im lokal geprüften Beispiel
wurde der erste Lauf storniert und Peters offene Testforderung auf einen
späteren Fälligkeitstermin verschoben. Der neu vorbereitete Lauf enthielt
zwei Positionen, 180,00 € und null Fehler.

Die Verschiebung wurde ausschließlich für diese Testdaten vorgenommen.
Ändere reale Fälligkeiten oder storniere reale Forderungen nicht nur, um
einen Exportfehler zu umgehen.

### 10.2 Testdatei prüfen

Vergleiche Positionen, Gesamtsumme und Einzugsdatum. Für eine zusätzliche
lokale technische Prüfung kann eine Person mit installiertem `xmllint`
folgende Befehle verwenden:

```bash
xmllint --nonet --noout sepa-test.xml
```

Schema-Prüfung aus dem Projektverzeichnis:

```bash
xmllint --nonet --noout \
  --schema resources/sepa/xsd/EPC130-08_2025_V1.0_pain.008.001.08.xsd \
  sepa-test.xml
```

Passe Dateinamen und Pfade an die tatsächlichen Dateien an. Eine erfolgreiche
Prüfung bestätigt XML-Syntax und Schema, nicht sämtliche bankfachlichen
Vorgaben. **Reiche eine Datei mit fiktiven Zahlungsdaten niemals bei einer
echten Bank ein.**

## 11. Benutzer und Einladungen verwalten

### 11.1 Benutzer einladen

1. Öffne **Administration → Benutzer**.
2. Wähle die Aktion zum Einladen eines Benutzers.
3. Gib die E-Mail-Adresse ein und wähle die vorgesehene Rolle.
4. Bestätige die Einladung.

Die eingeladene Person erhält eine E-Mail. Bestehende Vereinsmitglieder in
der Mitgliederverwaltung erhalten dadurch nicht automatisch einen Zugang;
die Einladung betrifft das Benutzerkonto.

### 11.2 Vorhandenes Konto hinzufügen

Wähle **Benutzer hinzufügen**, gib die E-Mail-Adresse eines bereits
registrierten Kontos ein und wähle eine Rolle. Wenn das Konto noch nicht
existiert, erscheint **Benutzer nicht gefunden**. Nutze dann eine Einladung.

### 11.3 Rolle ändern und Vereinszugang entfernen

In der Benutzerliste kannst du mit entsprechender Berechtigung:

- **Rolle ändern** auswählen und die neue Rolle speichern.
- **Benutzer aus Verein entfernen** auswählen und bestätigen.

Beim Entfernen verliert die Person den Zugang zum ausgewählten Verein.
Das persönliche Benutzerkonto und Zugänge zu anderen Vereinen bleiben
bestehen. Die Software verhindert, dass der letzte Administrator eines
Vereins auf diesem Weg entfernt oder seine Rolle geändert wird. Die
Entfernen-Aktion ist für das eigene Konto in der Liste deaktiviert.

Die vorgesehenen Rollenbezeichnungen sind **Administrator**, **Vorstand**,
**Kassenwart**, **Schießwart** und **Mitglied**. Welche davon nutzbar sind und
welche Rechte sie besitzen, hängt von der Einrichtung der Installation ab.
Aus dem Rollennamen allein lässt sich beispielsweise keine SEPA-
Exportberechtigung ableiten.

### 11.4 Einladungen nachverfolgen

Öffne **Administration → Einladungen**. Die Übersicht unterscheidet offene,
akzeptierte, widerrufene und abgelaufene Einladungen.

| Aktion | Wirkung |
| --- | --- |
| Erneut senden | Erzeugt einen neuen Link mit neuer Gültigkeitsdauer; der bisherige Link wird ungültig. |
| Widerrufen | Macht einen noch nicht angenommenen Einladungslink ungültig. |

Einladungen lassen sich nach Status filtern. Eine bereits angenommene
Einladung wird nicht durch erneutes Senden rückgängig gemacht; einen
bestehenden Vereinszugang verwaltest du in der Benutzerliste.

## 12. Profil und Kontosicherheit

### 12.1 Name und E-Mail ändern

Öffne **Profil** im Benutzermenü, passe Name oder E-Mail-Adresse an und
wähle **Profil speichern**. Nach einer Änderung der E-Mail-Adresse muss
diese erneut bestätigt werden. Bei Bedarf kannst du die Aktion zum erneuten
Versenden der Bestätigungsnachricht verwenden.

### 12.2 Passwort ändern

1. Öffne im Profil **Passwort ändern**.
2. Gib das aktuelle Passwort ein.
3. Gib das neue Passwort zweimal ein.
4. Bestätige die Änderung.

Das neue Passwort muss mindestens zwölf Zeichen umfassen und Buchstaben,
Zahlen sowie Sonderzeichen enthalten. Die Anwendung prüft außerdem, ob es
als kompromittiert bekannt ist. Andere Geräte werden im Änderungsablauf
abgemeldet.

### 12.3 Zwei-Faktor-Authentifizierung

Im Profil kannst du die Authentifizierung über eine Authenticator-App
einrichten. Folge dem angezeigten Ablauf und bestätige die Einrichtung mit
dem aktuellen Code deiner App. Bewahre die angebotenen Wiederherstellungscodes
an einem geschützten Ort auf.

Bei späteren Anmeldungen verwendest du zusätzlich zum Passwort den App-Code
oder bei Bedarf einen Wiederherstellungscode. Wenn du auf beides keinen
Zugriff mehr hast, wende dich an die technische Betreuung.

### 12.4 Sitzungen und Geräte prüfen

1. Öffne **Konto → Sicherheit**.
2. Prüfe die **Aktiven Sitzungen** mit Browser- beziehungsweise Geräteangabe,
   IP-Adresse und letzter Aktivität.
3. Die aktuelle Sitzung ist als **Aktuelles Gerät** markiert.
4. Beende bei Bedarf eine andere Sitzung über **Abmelden**.

Mit **Andere Geräte abmelden** kannst du nach Eingabe des aktuellen
Passworts die anderen Sitzungen beenden. Das aktuell verwendete Gerät bleibt
angemeldet. Die aktuelle Sitzung lässt sich nicht über die einzelne
Sitzungs-Abmelden-Aktion beenden; nutze dafür die normale Abmeldung im
Benutzermenü.

## 13. Änderungen und Audit-Protokoll

Die Software protokolliert wichtige Mitglieds-, Beitrags-, Benutzer- und
Sicherheitsvorgänge. Dazu gehören beispielsweise Änderungen am Mitglied,
Dokumentdownloads, Benutzerzuordnungen und SEPA-Exporte.

Ein erfolgreicher SEPA-Export erzeugt einen Audit-Eintrag. Jeder erfolgreich
geprüfte XML-Download erzeugt einen eigenen Eintrag. Für diese beiden
Vorgänge werden Laufkennung, Verein, Format, Positionsanzahl, Gesamtsumme
und Prüfsumme gespeichert, aber keine XML-Inhalte, IBANs oder BICs.

Im aktuellen Stand ist keine allgemeine Audit-Ansichtsseite im beschriebenen
Menü vorhanden. Wenn du eine Änderung nachvollziehen musst, nenne der
berechtigten Administration oder technischen Betreuung Verein, betroffenen
Datensatz, Aktion und ungefähren Zeitpunkt.

## 14. Fehlermeldungen und Problemlösung

| Meldung oder Beobachtung | Ursache und nächster Schritt |
| --- | --- |
| Ein Menüpunkt oder eine Aktion fehlt | Prüfe den ausgewählten Verein. Die benötigte Berechtigung oder der passende Datensatzstatus kann fehlen. Bitte die Vereinsadministration um Prüfung. |
| Zugriff verweigert / 403 | Dein Konto darf diese Aktion im aktuellen Verein nicht ausführen. |
| Datensatz nicht gefunden / 404 | Der Datensatz oder die Datei fehlt, oder er gehört nicht zum aktuellen Verein. Wähle den richtigen Verein und öffne den Datensatz erneut aus seiner Liste. |
| Einladung nicht mehr gültig | Die Einladung wurde widerrufen oder ist abgelaufen. Bitte um einen neuen Link. |
| Mitgliedsnummer bereits vergeben | Nutze innerhalb des Vereins eine andere Mitgliedsnummer oder suche nach dem vorhandenen Datensatz. |
| Kein gültiger Beitragssatz | Prüfe Beitragsart, Mitgliedsart, Berechnungsdatum, Aktiv-Schalter und Gültigkeitszeitraum der Sätze. |
| Beitragsregel überschneidet sich | Prüfe andere aktive individuelle Regeln desselben Mitglieds und derselben Beitragsart. |
| Für diesen Verein ist keine aktive SEPA-Konfiguration vorhanden | Öffne Einstellungen → SEPA und prüfe Konfiguration und Aktiv-Schalter. |
| Einzugsdatum liegt vor dem Mindestvorlauf | Wähle einen späteren Einzugstermin. |
| Kein aktives SEPA-Mandat | Erfasse ein gültiges Mandat, sofern es tatsächlich vorliegt. Kläre andernfalls die Zahlungsweise und den fehlerhaften Lauf. |
| Der SEPA-Lauf enthält keine Positionen | Prüfe offene positive Forderungen, ihre Fälligkeit und eine mögliche Verwendung in einem anderen vorbereiteten Lauf. |
| Ungeklärte Vorbereitungsfehler | Prüfe die Fehlerliste und bereinige den Lauf fachlich; gegebenenfalls ist nach Stornierung eine neue Vorbereitung erforderlich. |
| Forderung ist nicht mehr offen | Die Forderung wurde nach der Vorbereitung bezahlt oder storniert. Lass den Lauf fachlich prüfen. |
| SEPA-Mandat ist nicht mehr gültig | Prüfe Mandatsstatus, Gültigkeitsbeginn und Einzugstermin. Bereite erforderlichenfalls einen neuen Lauf vor. |
| Für den Export fehlt eine gültige BIC | Prüfe die Gläubiger-BIC und die BICs der Lastschriftpositionen. Eine nachträgliche Änderung des Mandats korrigiert keine bereits übernommene Position. |
| Anzahl oder Gesamtsumme stimmt nicht | Bitte die technische Betreuung um Prüfung des Laufs. Ändere nicht nur die angezeigte Summe. |
| XML entspricht nicht dem XSD-Schema | Prüfe insbesondere vollständige und ausreichend kurze Angaben. Lass die konkrete Schemaursache durch die technische Betreuung prüfen. |
| Die Exportdatei ist beschädigt | Die Datei stimmt nicht mit ihrer Prüfsumme überein. Verwende sie nicht weiter und melde den Fehler mit der Laufkennung. |
| Eine offene Forderung bleibt nach Export offen | Das ist erwartetes Verhalten. Ein Export bestätigt keinen Zahlungseingang. |
| Ein ausgesetztes Mitglied erhält eine Forderung | Ausgesetzte Mitglieder werden im Beitragslauf berücksichtigt. Eine gewünschte Befreiung muss als Beitragsregel hinterlegt werden. |

Wenn du Unterstützung benötigst, übermittle die Fehlermeldung, den Verein,
die betroffene Mitgliedsnummer oder Laufbezeichnung sowie die ungefähre
Uhrzeit. Zugangspasswörter oder vollständige Bankdaten sind für eine erste
Fehlerbeschreibung nicht erforderlich.

## 15. Derzeitige Grenzen der Oberfläche

Dieses Handbuch beschreibt den implementierten Stand und setzt keine
zusätzlichen Schaltflächen voraus:

- Der Widerruf eines SEPA-Mandats und die Stornierung eines Lastschriftlaufs
  sind als Anwendungsvorgänge vorhanden, aber nicht als eigene Aktionen in
  den beschriebenen Detailseiten angeboten.
- Ein Lastschriftlauf bietet keine manuelle Auswahl einzelner Forderungen
  im Vorbereitungsdialog.
- Die XML wird als Datei bereitgestellt; Bankübermittlung, Bankantworten und
  automatische Zahlungseingangsverarbeitung gehören nicht zu diesem
  Oberflächenablauf.
- Die allgemeine Audit-Auswertung erfordert derzeit Unterstützung durch die
  berechtigte Administration oder technische Betreuung.
- Nicht jeder vorhandene Berechtigungsname bedeutet, dass eine entsprechende
  Funktion bereits als Menüpunkt verfügbar ist.

## 16. Checklisten für den Alltag

### Neues Mitglied

- Richtigen Verein ausgewählt.
- Eindeutige Mitgliedsnummer und Eintrittsdatum eingetragen.
- Persönliche Daten, Kontakt und Anschrift geprüft.
- Mitgliedsart, Abteilungen und Funktionen passend zugeordnet.
- Beitragssatz oder individuelle Regel vorhanden.
- Benötigte Dokumente hochgeladen.
- Für Lastschriftzahlung: Mandatsdaten einschließlich BIC erfasst.

### Beitragslauf

- Beitragsart und Berechnungsdatum geprüft.
- Zeitraum und Fälligkeitsdatum geprüft.
- Gültige Sätze und individuelle Regeln kontrolliert.
- Lauf bewusst gestartet.
- Ergebniszahlen und Fehlerliste kontrolliert.
- Neu erzeugte Forderungen stichprobenartig geprüft.

### SEPA-Export

- Richtigen Verein und Gläubigerkonto geprüft.
- Einzugstermin und Vorlauf geprüft.
- Positionen, Beträge, Mandate und Verwendungszwecke kontrolliert.
- Keine ungeklärten Vorbereitungsfehler.
- Export erfolgreich erstellt und Datei heruntergeladen.
- Testdateien eindeutig von echten Zahlungsdateien getrennt.
- Eventuelle externe Einreichung nach dem vorgesehenen Verfahren dokumentiert.
- Tatsächliche Zahlungseingänge anschließend abgeglichen.

### Benutzerzugang

- Persönliches Benutzerkonto verwendet.
- E-Mail-Adresse bestätigt.
- Passende Rolle und Berechtigungen im jeweiligen Verein geprüft.
- Zwei-Faktor-Authentifizierung und Wiederherstellungscodes eingerichtet.
- Nicht mehr benötigte Vereinszugänge und Sitzungen beendet.

---

Für Installation und Betrieb siehe die
[Projektdokumentation](PROJEKT-DOKUMENTATION.md). Die Änderungen am
SEPA-Export sind im [Changelog](../CHANGELOG.md) dokumentiert.
