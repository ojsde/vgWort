    ==========================================================
    === OJS VG Wort Plugin
    === Version: 2.1
    === Authors: Božana Bokan, Ronald Steffen, Christoph Otte
    === Last updated: October 8, 2021
    ==========================================================

# OJS VG Wort Plugin

The VG Wort plugin enables integration and management of the [VG Wort](https://www.vgwort.de/startseite.html) pixel tags
in [OJS](https://pkp.sfu.ca/ojs/). Here you can assign the pixel tags to the articles, and register them
automatically (using a cron job or AcronPlugin) or manually.

The plugin is a part of the project [OJS-de.net](http://www.ojs-de.net).


## Getting Started

#### Installation via OJS GUI

1. Download `vgWort-[version]-.tar.gz` from [GitHub](https://github.com/ojsde/vgWort/).
2. Install the plugin in OJS.

#### Installation via command line without Git

1. Download the `.tar.gz` archive from [GitHub](https://github.com/ojsde/vgWort).
2. Go to the folder of your OJS instance and unzip the archive.
3. Rename the main plugin folder to "vgWort" if necessary.

#### Installation via command line with Git

1. Go to the folder of your OJS instance and clone the repository.

    ```console
    $ cd [path/to/your/ojs]/plugins/generic
    $ git clone https://github.com/ojsde/vgWort
    ```

2. Switch to the vgWort directory and checkout the branch.

    ```console
    $ cd vgWort
    $ git checkout [branch]
    ```

3. Run the `upgrade.php` script.

    ```console
    $ cd [path/to/your/ojs]
    $ php tools/upgrade.php upgrade
    ```


## Usage

* **Assign Pixel Tags** &mdash; Make sure that your publication has not been published yet. Then, go to `Publication > VG Wort`.

    1. Choose the **text type**; the publication is defined to be "Poem" if it does not contain more than 1800 characters.
    2. Initially, the **status** is set to "No VG Wort pixel tag has been assigned to this article yet." and the checkbox "Assign a VG Wort pixel tag to this article." is active. By simply saving this form, the status will switch to "Unregistered, active" and a new pixel tag is assigned. Note that the pixel tag can only be activated if the status has been changed.

    By publishing this version, the pixel tag will be activated.

* **Pixel Tag Listing** &mdash; All activated pixel tags will be listed under `Settings > Distribution > Pixel Tags`. By clicking the blue triangle at the beginning of each line you will be able to register the pixel tag. **Note that only registered pixel tags will be counted.**

* **Exclude Galleys from Counting** &mdash; Please activate the **DOI plugin**. Then, when edititing the galley under `Publication > Galleys > Edit`, there will appear a second tab ("Identifiers") with a checkbox.


## License

This plugin is licensed under the GNU General Public License v2. See the file [LICENSE](LICENSE) for the complete terms of this license.


## System Requirements

This plugin version is compatible with OJS 3.2.1. Additionally PHP Soap, and PHP OpenSSL extension modules.


## Version History

* 1.0 - Initial Release
* 1.1 - Updated to support OJS 2.4.1
* 1.2 - Updated to support OJS 2.4.2
* 1.3 - Insert already registered pixel tags
* 1.4 - Very important fix - use VG Wort live instead of test server
* 1.5 - VG Wort test system in plugin settings,
* 1.6 - Fix a major error: all authors and translators has to be registered at VG Wort. Also: add the possibility to enter translators and to remove a registration.
* 1.7 - Plugin version for OJS 3.1.1-4
* 2.0 - Plugin version for OJS 3.1.2
* 2.1 - Plugin version for OJS 3.2.1


## Contact

Documentation, bug listings, and updates can be found on this plugin's homepage
at [GitHub](http://github.com/ojsde/vgWort).
