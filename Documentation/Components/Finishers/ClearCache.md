Finisher ClearCache
===================
Clears caches.
Currently only _all_, and _pages_ cache are supported. Arbitrary caches or cache groups are not supported yet.
**Note**: Nothing will be done, if the result is empty.

### Examples
**all**
```yaml
import:
  tasks:
    example:
      # [...]
      finishers:
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            all: 1
```
Clear all caches (if result not empty).

**pages**
```yaml
import:
  tasks:
    example:
      # [...]
      finishers:
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            pages: '1,3,5'
```
Clear pages 1,3 and 5 (if result not empty).

**Classes**
```yaml
import:
  tasks:
    example:
      # [...]
      finishers:
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            classes:
              NameSpaced\ClassName\OfResult:
                pages: '4'
```
Clear cache for page 4 (The first entry in result must be an object of the given class)

### Options
| option              | type   | required | description         |
| --------------------| ------ | ---------|----------- |
| config.all          | string | no       | when set to _any_ value, all caches will be cleared|
| config.pages        | string | no       | Comma separated list of page ids. Clears the selected pages.|
| config.classes      | array  | no       | The key of each entry in the array must be a class name, the value an array with page identifieres |
