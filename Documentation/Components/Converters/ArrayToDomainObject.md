Converter ArrayToDomainObject
=================================

Transforms a (nested) array to an domain object using the (Extbase) PropertyMapper. Each key in the array can be mapped to a property of
the target object class. Non scalar values are mapped recursively.

**Notes**
* If a record has a key *__identiy*, the property will try and find an object by this id (usually the field *uid*). 
If found the object is updated. If *__identity* is not set or no object can be found, a new object is created.
* There are some properties which **can not** be set via PropertyMapper e.g. *uid*, *_localized_uid*, *_language_uid*, *_versioned_uid*. They must be handled separatly e.g by a finisher.
* **Important** Currently it is **not possible** to import an object and correctly localize it via PropertyMapper (due to limitations in TYPO3 core). Namely the correct relation between original an translated object can not be established. If necessary you will have to fix it on your own.

### Examples
**Minimal Configuration**
```TypoScript
module.tx_t3importexport.settings.import.tasks.events {
  [...]
  converters.10 {
    class = CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
    config {
      targetClass = DWenzel\T3events\Domain\Model\Event
    }
  }
  [...]
}
```
Convert array to Event. All properties are allowed, no custom configuration for mapping.


**Advanced Configuration**
```TypoScript
module.tx_t3importexport.settings.import.tasks.events {
  [...]
  converters.10 {
    class = CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
    config {
      targetClass = Webfox\T3events\Domain\Model\Event
      allowProperties = headline,subtitle,description,performances,eventType,eventLocation,genre,tags
      properties {
        eventType {
          allowProperties = externalId
        }
        performances {
          allowAllProperties = 1
          # configuration for all children in object storage field
          children {
            maxItems = 5
            allowProperties = date,endDate,priceNotice,eventLocation,event,statusInfo,zewId
            properties {
              date {
                typeConverter {
                  # use a type converter different from default PersistentObjectConverter
                  class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
                  options {
                      dateFormat = U
                  }
                }
              }
              endDate {
                typeConverter {
                  class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
                  options {
                      dateFormat = U
                  }
                }
              }
            }
          }
        }
        genre {
          allowAllProperties = 1
        }
        tags {
          allowAllProperties = 1
        }
      }
      typeConverter {
        # use custom implementation of PersistentObjectConverter
        class = CPSIT\T3importExport\Property\TypeConverter\PersistentObjectConverter
        options {
            # options apply when PropertyMapper retrieves existing object from storage
            IGNORE_ENABLE_FIELDS = 1
            ENABLE_FIELDS_TO_BE_IGNORED = hidden
            SYS_LANGUAGE_UID = 0
            #RESPECT_SYS_LANGUAGE = 1
            #INCLUDE_DELETED = true
            #RESPECT_STORAGE_PAGE = 0
            STORAGE_PAGE_IDS = 3,5,9
        }
      }
    }
  }
  [...]
}
```
This example shows how a custom property mapping can be achieved.
 
### Options

| option                    | type       | required | description                                                                                    |
| --------------------------| ---------- | ---------|----------------------------------------------------------------------------------------------- |
| config.targetClass        | string     | yes      | fully qualified class name. A repository with matching name must exist                         |
| config.allowProperties    | string     | yes      | comma separated list of properties which should be mapped. A matching key must exist in the incoming array. |
| config.allowAllProperties | boolean    | no       | If 1 the property mapper is allowed to map all properties otherwise only those are allowed which are configured in *allowProperties* option |
| config.properties         | array      | no       | Array containing configuration for properties. Each key corresponds to a property name         |
| [...].properties.\<name\> | array      | no       | Array containing configuration for a single property. *Important*: Any other option can be applied recursively to properties and sub-properties. |
| [...].\<name\>.children   | array      | no       | Optional configuration for children in 1:n and n:m relation fields (ObjectStorage)             |
| [...].children.maxItems   | integer    | yes      | Not more than *maxItems* number of children are mapped.                                      |
| config.typeConverter      | array      | no       | optional configuration for a type converter                                                    |
| [...].class               | string     | no       | fully qualified class name of a custom type converter implementation                           |
| [...].options             | array      | no       | Array of options for the type converter. Available options depend on implementation.           |

### Signal
The component emits a signal just after building a property mapping configuration and before passing the record to the property mapper. 
See *ArrayToDomainObject-\>convert()* for details.
