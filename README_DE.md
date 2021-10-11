    ==========================================================
    === OJS VG Wort Plugin
    === Version: 2.1
    === Authors: Božana Bokan, Ronald Steffen, Christoph Otte
    === Last updated: 8. Oktober 2021
    ==========================================================

# OJS VG Wort Plugin

Das VG-Wort-Plugin ermöglicht die Integration und Verwaltung von [VG-Wort](https://www.vgwort.de/startseite.html)-Zählpixeln in [OJS](https://pkp.sfu.ca/ojs/). Sie können hier Artikeln Zählpixel zuweisen und die Zählpixel automatisch (durch Verwendung eines Cron-Jobs oder des Acron-Plugins) oder manuell melden.

Das Plugin ist Teil des Projekts [OJS-de.net](http://www.ojs-de.net)


## Getting Started

#### Installation über die OJS-Benutzeroberfläche:

1. Download des `vgWort-[version]-.tar.gz` von [GitHub](https://github.com/ojsde/vgWort/)
2. Install the plugin in OJS
2. Installation des Plugins im Managementbereich von OJS (`Einstellungen > Website > Plugins > "Ein neues Plugin hochladen" -> vgWort-[Version].tar.gz` hochladen)

#### Installation über die Kommandozeile ohne Git:

1. Download des Archivs `.tar.gz` in der gewünschten Version von [GitHub](https://github.com/ojsde/vgWort)
2. Entpacken des Plugins in das Verzeichnis `plugins/generic/` und ggf. Umbennen des Hauptverzeichnisses in "vgWort"
3. Aktualisierung der Datenbank (es empfiehlt sich, zuerst ein Backup der Datenbank zu erstellen),
   führen Sie dazu aus Ihrem OJS-Verzeichnis aus: `php tools/upgrade.php` upgrade oder
   `php tools/installPluginVersion.php` (siehe [GitHub](https://github.com/pkp/pkp-lib/issues/2503))


#### Installation über die Kommandozeile mit Git:

1. Wechseln Sie in das Verzeichnis Ihrer OJS-Installation und clonen sie das Repository

```console
$ cd [path/to/your/ojs]/plugins/generic
$ git clone https://github.com/ojsde/vgWort
```

2. Wechseln Sie in das vgWort-Verzeichnis und checken Sie den entsprechenden Branch aus

```console
$ cd vgWort
$ git checkout [branch]
```

3. Führen Sie das Skript `upgrade.php` aus

```console
$ cd [path/to/your/ojs]
$ php tools/upgrade.php upgrade
```


## Usage

* **Pixel Tags zuordnen** &mdash; Vergewissern Sie sich, dass der Artikel noch nicht veröffentlicht wurde. Dann gehen Sie nach `Publikation > VG Wort`.

1. Wählen Sie den **Texttyp**; Der Artikel gilt als "Gedicht", wenn er nicht mehr als 1800 Zeichen enthält.
2. Zu Beginn ist der **Status** ist auf "Es wurde diesem Artikel noch kein VG-Wort-Zählpixel zugewiesen." gesetzt und die Checkbox "Das VG-Wort-Zählpixel von diesem Artikel entfernen." deaktiviert. Bitte wählen Sie die Checkbox "Ein VG-Wort-Zählpixel diesem Artikel zuweisen." Nach Speichern des Formulars ändert sich der Status zu "Unregistriert, aktiv" und ein neuer Zählpixel wurde zugeordnet.

* **Auflistung der Zählpixel** &mdash; Alle aktivierten Zählpixel werden unter `Einstellungen > Vertrieb > Zählpixel` aufgelistet. Klicken Sie auf blaue Dreieck zu Beginn jeder Zeile um den Zählpixel zu registrieren. **Beachten Sie, dass nur registrierte Zählpixel gezählt werden**.

* **Fahnen von der Zählung ausschließene** &mdash; Bitte aktivieren Sie das **DOI-Plugin**. Dann erscheint ein beim Bearbeiten einer Fahne unter `Veröffentlichung > Fahnen > Bearbeiten` ein weiterer Tab ("Identifier") mit einer entsprechenden Checkbox.


## Lizenz

Das Plugin ist unter GNU General Public License v2 lizenziert. Sehen Sie die Datei [LICENSE](LICENSE) für mehr Informationen über die Lizenz.


## Systemanforderungen

Dieses Plug-In Version ist kompatibel mit OJS 3.2.1. Zusätzlich werden PHP Soap und PHP OpenSSL extension modules benötigt.


## Version History

* 1.0 &ndash; Initial Release
* 1.1 &ndash; Updated to support OJS 2.4.1
* 1.2 &ndash; Updated to support OJS 2.4.2
* 1.3 &ndash; Insert already registered pixel tags
* 1.4 &ndash; Very important fix - use VG Wort live instead of test server
* 1.5 &ndash; VG Wort test system in plugin settings,
* 1.6 &ndash; Fix a major error: all authors and translators has to be registered at VG Wort. Also: add the possibility to enter translators and to remove a registration.
* 1.7 &ndash; Plugin version for OJS 3.1.1-4
* 2.0 &ndash; Plugin version for OJS 3.1.2
* 2.1 &ndash; Plugin version for OJS 3.2.1


## Kontakt/Support

Dokumentation, Fehlerauflistung und Updates können auf dieser Plugins-Startseite gefunden werden (GitHub)[http://github.com/ojsde/vgWort]
