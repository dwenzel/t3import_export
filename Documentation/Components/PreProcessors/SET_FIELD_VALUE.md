SetFieldValue
=============
Sets a single field value.

## Configuration

### Required
* **class** *string*
    
    fully qualified class name *CPSIT\T3importExport\Component\PreProcessor\SetFieldValue*

* **config.targetField** *string* 

    field name of record to which the content of the fields are added. Field **must** contain a string value.

* **config.value** *string*

    value for target field. 

### Example

A set of SetFieldValue PreProcessors seeting some fields for tt_content.

```
preProcessors {
    11 {
        class = CPSIT\T3importExport\Component\PreProcessor\SetFieldValue
        config {
            targetField = pid
            value = 56
        }
    }
	12 {
	    class = CPSIT\T3importExport\Component\PreProcessor\SetFieldValue
	    config {
	        targetField = colPos
	        value = 0
	    }
	}
	13 {
	    class = CPSIT\T3importExport\Component\PreProcessor\SetFieldValue
	    config {
	        targetField = cruser_id
	        value = 5
	    }
	}
	14 {
	    class = CPSIT\T3importExport\Component\PreProcessor\SetFieldValue
	    config {
	        targetField = CType
	        value = myext_customctype
	    }
	}
}
```