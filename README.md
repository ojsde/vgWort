[DEPRECATED]

Attention: This repo is not maintained anymore. Further development of the OJS vgWort plugin is continued at the University Libraray Heidelberg at [https://github.com/UB-Heidelberg/vgWort](https://github.com/UB-Heidelberg/vgWort)

```
================================
=== OJS VG Wort Plugin
=== Version: 2.0
=== Author: Božana Bokan, Ronald Steffen
=== Last update: June 23, 2020
================================
```

About
-----
The VG Wort plugin enables integration and management of the VG Wort pixel tags in OJS. Here you can assign the pixel tags to the articles, and register them automatically (using a cron job or AcronPlugin) or manually.

The plugin is a part of the project OJS-de.net (http://www.ojs-de.net)

License
-------
This plugin is licensed under the GNU General Public License v2. See the file LICENSE for the complete terms of this license.

System Requirements
-------------------
This plugin is compatible with...
- OJS 3.1.1 and OJS 3.1.2

Additionally PHP Soap, and PHP OpenSSL extension modules.

Installation
------------
Installalion via OJS GUI:
 - download vgWort-[version].tar.gz from https://github.com/ojsde/vgWort/releases
 - install plugin in OJS (Settings -> Website -> plugins -> „Upload a New Plugin“ -> upload vgWort-[version].tar.gz)

Installation via command line without Git:
 - download archive from https://github.com/ojsde/vgWort
 - unzip the archive to the folder plugins/generic/
 - rename the main plugin folder to "vgWort" if necessary
 - from your OJS application's installation directory, run the upgrade script (it is recommended to back up your database first):
   php tools/upgrade.php upgrade or php lib/pkp/tools/installPluginVersion.php (see https://github.com/pkp/pkp-lib/issues/2503)

Installation with Git:
 - cd [my_ojs_installation]/plugins/generic
 - git clone https://github.com/ojsde/vgWort
 - cd vgWort
 - git checkout [branch]
 - cd [my_ojs_installation]
 - php tools/upgrade.php upgrade
     or
   php tools/installPluginVersion.php
     and
   php tools/dbXMLtoSQL.php -schema execute plugins/generic/vgWort/xml/schema.xml
   (s. https://github.com/pkp/pkp-lib/issues/2503, it is recommended to back up your database first)

Contact/Support
---------------
Documentation, bug listings, and updates can be found on this plugin's homepage
at <http://github.com/ojsde/vgWort>.

Version History
---------------
1.0	- Initial Release
1.1	- Updated to support OJS 2.4.1
1.2 - Updated to support OJS 2.4.2
1.3 - Insert already registered pixel tags
1.4 - Very important fix - use VG Wort live instead of test server
1.5 - VG Wort test system in plugin settings, 
1.6 - Fix a major error: all authors and translators has to be registered at VG Wort. Also: add the possibility to enter translators and to remove a registration.
1.7 - Plugin version for OJS 3.1.1-4
2.0 - Plugin version for OJS 3.1.2
