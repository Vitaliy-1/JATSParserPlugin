# JATSParserPlugin
OJS3 Plugin for parsing JATS XML and displaying it on article detail page.
## Features 
* JATS XML to HTML automatic conversion.
* JATS XML to PDF automatic conversion.
* Article's metadata now is taken directly from OJS (no need to populate JATS XML meta nodes).
* Article's reference list can be taken from JATS XML or OJS (option in the plugin settings).
* Support for figures attached to the XML through OJS dashboard.
## How to use?
1. Download the [latest release](https://github.com/Vitaliy-1/JATSParserPlugin/releases) 
2. Upload the plugin from the admin dashboard `Website Settings -> Plugins -> Upload a New Plugin` (make sure php.ini variables `upload_max_filesize` and `post_max_size` a set to equal or more than 16M) or unpack the archive into the `plugins/generic/` folder.
3. Activate the plugin from the dashboard.
4. To change image logo in resulted PDF just replace `JATSParser/logo/logo.jpg` file by yours. 
## Requirements
* PHP 7.1 or higher
* OJS theme with Bootstrap 4. If the theme doesn't utilize this library, it must be added alongside with jQuery from the plugin main class (it is [quite straightforward](https://github.com/Vitaliy-1/JATSParserPlugin/blob/master/JatsParserPlugin.inc.php#L145-L148)). JATS Parser Plugin is tested with [HealthSciences](https://github.com/pkp/healthSciences), [Classic](https://github.com/Vitaliy-1/classic), and [oldGregg](https://github.com/Vitaliy-1/oldGregg) themes
* OJS 3.1+
* Lens Galley Plugin must be turned off
## Examples
* Example of the JATS XML: https://github.com/Vitaliy-1/JATSParserPlugin/blob/master/example.xml
* HTML example: http://ojsdemo.e-medjournal.com/index.php/jatsparser/article/view/8/8
* PDF example: http://ojsdemo.e-medjournal.com/index.php/jatsparser/article/view/8/8?download=pdf

