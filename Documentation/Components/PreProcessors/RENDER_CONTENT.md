RenderContent
=============
Renders fields of the record with TypoScript content objects (`TEXT`, `COA`,
...). The content object renderer receives the current record.

**Deprecated:** content rendering with TypoScript will be removed in version 4.0
([#75](https://github.com/dwenzel/t3import_export/issues/75)). Use
[EvaluateExpressions](./EVALUATE_EXPRESSIONS.md) instead; it contains a
migration table.

## Configuration

* **class** *string*
  *CPSIT\T3importExport\Component\PreProcessor\RenderContent*

* **config.fields** *array*
  field name => content object configuration

### Example
```typo3_typoscript
10 {
  class = CPSIT\T3importExport\Component\PreProcessor\RenderContent
  config.fields.teaser = TEXT
  config.fields.teaser {
    field = teaser // abstract
  }
}
```

Data that needs a frontend page (`getData` with `TSFE:` or `page:`, references
like `< lib.foo`) is not available.
