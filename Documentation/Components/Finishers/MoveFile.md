Finisher MoveFile
==================

Moves an existing file.

### Examples

**Minimal Configuration**
```TypoScript
module.tx_t3importexport.settings.import.tasks.example {
  [...]
  finishers.30 {
    class = CPSIT\T3importExport\Component\Finisher\MoveFile
    config {
      source.name = foo.xml
      target.name = bar.xml
    }
  }
  [...]
}
```
Renames the file `foo.xml` into `bar.xml`. It is assumed that the source file exists in the root directory of the storage with ID 1 (default storage).  
The file must be readable and the directory writable for the current user. Note: The rights for scripts run by scheduler task or at the command line might differ.

**Extended Configuration**
```TypoScript
module.tx_t3importexport.settings.import.tasks.example {
  [...]
  finishers.30 {
    class = CPSIT\T3importExport\Component\Finisher\MoveFile
    config {
      source {
        name = foo.xml
        storage = 2
        directory = foo/bar
      }
      target {
        name = bar.xml
        storage = 7
        conflictMode = replace
        directory = boom
      }
    }
  }
  [...]
}
```
Renames (moves) the file `foo.xml` from directory `foo/bar` of the storage with ID 
`2` to the file `bar.xml` in the directory `boom` of the storage with ID `7`. 

### Options
| option                    | type    | required | default    |description         |
| --------------------------| ------- | ---------|------------|----------- |
| config.source.name        | string  | yes      |            | source file name |
| config.source.storage     | integer | no       | 1          | ID of the storage in which the file should be written. If not set default storage is used. |
| config.source.directory   | string  | no       |            | target directory name. If the directory does not exist, it will be created in the selected storage |
| config.target.name        | string  | yes      |            | target file name |
| config.target.storage     | integer | no       | 1          | ID of the storage in which the file should be written. If not set default storage is used. |
| config.target.directory   | string  | no       |            | target directory name. If the directory does not exist, it will be created in the selected storage |
| config.target.conflictMode| string  | no       | changeName | which strategy to use when a file already exists. Allowed: cancel, replace, changeName |
