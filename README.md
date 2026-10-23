# JATSParserPlugin
OJS 3 plugin for parsing JATS XML and displaying it on the article landing page.

## Features
* JATS XML to HTML conversion.
* JATS XML to PDF conversion (optional, creates a PDF galley).
* Article's metadata is taken directly from OJS (no need to populate JATS XML meta nodes).
* Article's reference list can be imported from the JATS XML into the publication's References (selectable in the References tab).
* Support for figures attached to the XML through the OJS dashboard as dependent files.

## How to use?
### Installation
1. Download the [latest release](https://github.com/Vitaliy-1/JATSParserPlugin/releases).
2. Upload the plugin from the admin dashboard `Website Settings -> Plugins -> Upload a New Plugin` (make sure php.ini variables `upload_max_filesize` and `post_max_size` are set to 16M or more) or unpack the archive into the `plugins/generic/` folder.
3. Activate the plugin from the dashboard.
4. To change the logo in the generated PDF, replace the `JATSParser/logo/logo.jpg` file with your own.

### Settings
* **Convert to PDF**: when enabled, a PDF galley is created when the JATS XML is processed. Re-processing updates the existing PDF galley instead of creating a new one.
* **Import full-text from galleys**: one-time action. When the box is checked and the form is saved, the plugin imports full-text from existing XML galleys. The box is not kept checked after saving.

### Usage
1. **Upload the JATS XML.** In the Production stage, upload the JATS XML file as a Production Ready file.
2. **Attach figures (optional).** Open the XML file's Dependent Files and upload the images. Use a genre whose category is Artwork. The image name must match the `xlink:href` value used in the XML (e.g. `<graphic xlink:href="figure1.png"/>`).
3. **Process the XML.** In the Publication tab, open **JATS Full Text**, select the XML file and click **Process JATS**. The XML is converted to HTML and saved as the publication's full-text. Use **Re-process JATS** after changing the XML or its figures, and **Remove Full Text** to clear the generated HTML (the XML file is kept).
4. **Import references (optional).** In the Publication tab, open **References**, select the processed JATS XML file and save. The references from the XML `<ref-list>` replace the publication's references as raw text.
5. **Publish.** After publication, the full-text is shown on the article landing page under the abstract.

### Known limitations
* Figures in the full-text are not displayed when previewing a version that has not been published yet. They are displayed once the version is published.

### Installation for development
1. Navigate to the `plugins/generic` folder starting from the OJS webroot.
2. `git clone https://github.com/Vitaliy-1/JATSParserPlugin.git jatsParser`
3. Install the dependencies: `cd jatsParser/JATSParser` and `composer install`.
4. Run the tests from the plugin folder: `JATSParser/vendor/bin/phpunit`.

For a release package, install the dependencies without development packages: `composer install --no-dev`.

## Requirements
* PHP 8.1 or higher
* OJS 3.5
* Lens Galley Plugin must be turned off

## Examples
* Example of the JATS XML: https://github.com/Vitaliy-1/JATSParser/blob/master/example.xml
* HTML example: http://ojsdemo.e-medjournal.com/index.php/jatsparser/article/view/8/8
* PDF example: http://ojsdemo.e-medjournal.com/index.php/jatsparser/article/view/8/8?download=pdf

## Release notes
### Unreleased
OJS 3.5 compatibility. The version number is left to the maintainer.
* Ported the plugin to OJS 3.5 (PHP API, Vue 3 workflow tab, JATS tag extensions).
* Removed the unused CiteProc dependencies, which conflicted with the Citation Style Language plugin and broke its "How to cite" block. The citation style settings have been removed; references are imported from the JATS XML as raw text.
* Updated TCPDF to 6.11.4 (security advisories); figures are now rendered in the PDF galley.
* Re-processing an existing PDF galley now replaces its file instead of adding a new one.

### 2.2.0
Starting from this version JATS Parser Plugin starts to serve HTML statically and will no longer support rendering of the full-text on the fly on the galley page. Instead full-text is generated on demand from production ready JATS XML files and saved as a part of the publication.
