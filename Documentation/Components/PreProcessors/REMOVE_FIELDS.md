RemoveFields
============

> Deprecated since 3.2, removed in 4.0. See [Deprecations](../../DEPRECATIONS.md#deprecated-in-32) for the replacement in `cpsit/import-export-core`.
Removes attributes from the record. This is useful for cleaning up e.g. import
data or after the usage of [PreProcessor MapFields](MAP_FIELDS.md).

### Example

```
preProcessors {
  61 {
    class = CPSIT\T3importExport\Component\PreProcessor\RemoveFields
    config {
      fields {
        nid = true
        node_type = true
        keywords = true
        title = true
        authors = true
        full_text_source_in_english = true
        leading_country = true
      }
    }
  }
}

```
