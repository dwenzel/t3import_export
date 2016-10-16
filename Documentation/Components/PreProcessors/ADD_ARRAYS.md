AddArrays
=========

Adds the content of each configured field to a target field. 

Only the target field will be changed. If the content of a field is not an array it will be ignored.

## Configuration
## required
* class fully qualified class name, i.e. *CPSIT\T3importExport\Component\PreProcessor\AddArrays*
* config.targetField *string* field name of record to which the content of the fields are added. (Should be an array too)
* config.fields *string* comma separated list of field names in record

## optional
* **config.disable** *boolean*

    If the value is 1, the initializer will not be called. 
    If the value is an array and can be interpreted as content object it will be rendered. The result of the rendering
    will be interpreted as boolean. 

### Example
```
module.tx_t3importexport.settings.import.tasks.exampleTask.preProcessors {
  10 {
    class = CPSIT\T3importExport\Component\PreProcessor\AddArrays
    config {
      targetField = speakers
      fields = speakers_responsible,speakers_employee
    }
  }
}
```