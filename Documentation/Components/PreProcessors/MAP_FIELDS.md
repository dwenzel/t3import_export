MapFields
=========

> Deprecated since 3.2, removed in 4.0. See [Deprecations](../../DEPRECATIONS.md#deprecated-in-32) for the replacement in `cpsit/import-export-core`.
Maps attributes of the data source record to new attributes in the processed
record.

It's good to use in combination with
the [PreProcessor RemoveFields](REMOVE_FIELDS.md), if the originals of the
mapped attributes are not needed under the former name.

### Example

```
preProcessors {
  41 {
    class = CPSIT\T3importExport\Component\PreProcessor\MapFields
    config {
      fields {
        title = header
        authors = tx_myext_authors
        full_text_source_in_english = bodytext
        leading_country = tx_myext_country
      }
    }
  }
}
```
