# JATSParserPlugin
OJS3 Plugin for parsing JATS XML and displaying it on article detail page.
## Features 
* JATS XML to HTML conversion.
* JATS XML to PDF conversion.
* Article's metadata now is taken directly from OJS (no need to populate JATS XML meta nodes).
* Article's reference list can be taken from JATS XML or OJS (option in the plugin settings).
* Support for figures attached to the XML through OJS dashboard.
## How to use?
### Installation
1. Download the [latest release](https://github.com/Vitaliy-1/JATSParserPlugin/releases) 
2. Upload the plugin from the admin dashboard `Website Settings -> Plugins -> Upload a New Plugin` (make sure php.ini variables `upload_max_filesize` and `post_max_size` a set to equal or more than 16M) or unpack the archive into the `plugins/generic/` folder.
3. Activate the plugin from the dashboard.
4. To change image logo in resulted PDF just replace `JATSParser/logo/logo.jpg` file by yours.
### Usage
After activation the plugin adds new item to the publication form:
![Screenshot from the publication page when plugin is activated](https://github.com/Vitaliy-1/JATSParserPlugin/blob/master/images/jatsParser_scr_1.png?raw=true)
The first select option contains the list of XML files uploaded to the production ready stage where is possible to pick the file that will be converted and saved as article's full-text. Before saving, a result of a conversion can be previewed. 
Under the hood the conversion process transforms JATS XML into HTML and saves the results in the database.

After publication full-text will be shown on the article landing page under the abstract.

### Installation for development
1. Navigate to `plugins/generic` folder starting from OJS webroot.
2. `git clone --recursive https://github.com/Vitaliy-1/JATSParserPlugin.git jatsParser`.
3. To install support for JATS to PDF conversion: `cd jatsParser/JATSParser` and `composer install`.  
## Requirements
* PHP 7.3 or higher
* OJS 3.1+
* Lens Galley Plugin must be turned off
## Examples
* Example of the JATS XML: https://github.com/Vitaliy-1/JATSParser/blob/main/examples/example.xml
* HTML example: https://e-medjournal.com/index.php/psp/article/view/213
* PDF example: https://uk.e-medjournal.com/index.php/psp/article/view/296/486
## Release notes
### 2.2.0
Starting from this version JATS Parser Plugin starts to serve HTML statically and will no longer support rendering of the full-text on the fly on the galley page. Instead full-text is generated on demand from production ready JATS XML files and saved as a part of the publication. 

