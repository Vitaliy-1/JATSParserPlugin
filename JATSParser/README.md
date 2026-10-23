# JATSParser
JATSParser is aimed to be integrated with Open Journal Systems 3.0+ for transforming JATS XML to various formats
## Usage
* Install composer dependencies
* See [example.php](examples/example.php)
* Doesn't deal with JATS XML metadata as it by design it should be transfered from OJS
* Transforms JATS to HTML and PDF, uses TCPDF for the latter conversion
* Has dependency from citeproc-php for support for different citation style formats 
