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
Write the file from `result.info` into the file `bar.xml` in the root directory of the storage with ID 1. It is assumed that the _info_ property of the result contains a `FileInfo` object. 
This object must describe the source file object.

**Extended Configuration**
```TypoScript
module.tx_t3importexport.settings.import.tasks.example {
  [...]
  finishers.30 {
    class = CPSIT\T3importExport\Component\Finisher\WriteFile
    config {
      target {
        name = bar.xml
        storage = 7
        conflictMode = replace
        directory = foo/baz
      }
    }
  }
  [...]
}
```
Write the file from `result.info` into the file `bar.xml` in the directory `foo/baz` of the storage with ID 7. 

### Options
| option                    | type    | required | default    |description         |
| --------------------------| ------- | ---------|------------|----------- |
| config.target.name        | string  | yes      |            | target file name |
| config.target.storage     | integer | no       | 1          | ID of the storage in which the file should be written. If not set default storage is used. |
| config.target.directory   | string  | no       |            | target directory name. If the directory does not exist, it will be created in the selected storage |
| config.target.conflictMode| string  | no       | changeName | which strategy to use when a file already exists. Allowed: cancel, replace, changeName |
