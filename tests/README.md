# JATSParser Plugin — Unit Tests

Unit tests for the JATSParser library used by the JATSParser OJS plugin.

These tests cover the JATS XML parsing and HTML rendering pipeline without requiring OJS or database dependencies.

## Scope

| Test class | What it tests |
|---|---|
| `Body\DocumentTest` | JATS XML parsing: sections, paragraphs, inline tags, block elements (boxed-text, def-list, preformat, disp-formula), inline-graphic XPath fix |
| `HTML\DocumentTest` | HTML rendering: inline tag output (monospace→code, sc→small-caps, email→mailto, etc.), block element output (dl, pre, disp-formula), basic structure |
| `Back\ReferenceTest` | Reference extraction: getRawReference() fix, citation types (journal, book, chapter, conference), mixed vs element citations, DOI/PMID, collaboration authors |

## Setup

PHPUnit must be installed as a dev dependency. From the plugin root:

```bash
cd JATSParser
composer require --dev phpunit/phpunit "^10.0"
cd ..
```

Alternatively, use a global PHPUnit install if available.

## Running

From the **plugin root** directory (`plugins/generic/jatsParser/`):

```bash
./JATSParser/vendor/bin/phpunit -c phpunit.xml
```

Or in ddev:

```bash
ddev exec -d /var/www/html/plugins/generic/jatsParser ./JATSParser/vendor/bin/phpunit -c phpunit.xml
```

## Fixtures

Test fixtures are minimal JATS XML files in `tests/fixtures/`:

| File | Content |
|---|---|
| `basic-article.xml` | 2 sections, bold/italic, 1 reference |
| `inline-tags.xml` | All new inline tags (monospace, sc, strike, email, uri, inline-graphic, etc.) |
| `block-elements.xml` | boxed-text, def-list, preformat, code, disp-formula, disp-quote |
| `references.xml` | 4 references: journal, book, chapter, conference with DOI/PMID |

## Notes

- Tests do **not** depend on OJS — they test the JATSParser library in isolation
- `setReferences()` (CiteProc) is not tested because it requires external CSL data and has known issues with locale handling in OJS 3.5
- These tests are intended for maintenance during the OJS 3.5 LTS lifecycle
