Finisher WriteFile
==================

Writes a result to a file.
The result is assumed to be a file. Its location must be found in the info of the current task result.

### Examples

**Minimal Configuration**
```TypoScript
module.tx_t3importexport.settings.import.tasks.example {
  [...]
  finishers.30 {
    class = CPSIT\T3importExport\Component\Finisher\WriteFile
    config {
      target.name = bar.xml
    }
  }
  [...]
}
```
Write the file from ´result.info´ into the file ´bar.xml´ in the root directory of the storage with ID 1. It is assumed that the _info_ property of the result contains a ´FileInfo´ object. 
This object must describe the source file object.

### Options
| option                 | type    | required | description         |
| -----------------------| ------- | ---------|----------- |
| config.target.name     | string  | yes      | target file name |
| config.target.storage  | integer | no       | ID of the storage in which the file should be written. If not set default storage is used. |
| config.target.directory| string  | no       | target directory name. The directory _must_ exist in the selected storage |
