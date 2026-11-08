ConcatenateFields
=================

Concatenates fields of a given record and adds the result to a new or existing
field of this record. All fields **must** contain strings. If targetField is
not empty the result will be added to its value.

## Configuration

### required
* **class** *string*
  fully qualified class name
  *CPSIT\T3importExport\Component\PreProcessor\ConcatenateFields*
* **config.targetField** *string*

  field name of record to which the content of the fields are added. Field
  **must** contain a string value.
* **config.fields** *array*
  An array of field names as keys. Each key must hold an array!
### optional
* **config.fields.\<field name\>.wrap** *string*
  `before|after`, as TypoScript `wrap`. Both parts are trimmed and wrapped
  around the field value. Empty field values are not wrapped.
* **config.fields.\<field name\>.noTrimWrap** *string*
  `|before|after|`, as TypoScript `noTrimWrap`. Whitespace is kept.

### Example

```
module.tx_t3importexport.settings.import.tasks.exampleTask.preProcessors {
  # concatenate some fields, wrap them and add them to another field
  5 {
    class = CPSIT\T3importExport\Component\PreProcessor\ConcatenateFields
    config {
      # name of the target field
      targetField = fullName
      # fields must be array with field names as keys
      fields {
        titel_1 {
          # bogus - just to get an array
          foo = bar
        }
        firstName {
          # wrap around field firstName, whitespace is trimmed
          wrap = <span class="foo">|</span>
        }
        lastName {
          # wrap around lastName, whitespace is kept
          noTrimWrap = | ||
        }
      }
    }
  }
}
```
