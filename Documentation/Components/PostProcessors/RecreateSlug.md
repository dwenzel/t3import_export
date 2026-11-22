PostProcessor RecreateSlug
===================================

Generates and updates slugs.

### Configuration Example

```yaml
import:
  tasks:
    example:
      postProcessors:
        - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\RecreateSlug"
          config:
            tableName: foo_table  # default: pages
            slugField: bar        # default: slug
            siteId: 2             # default: 1
```

Recreates the slug for table `tableName` for field `slug` with `siteId`.

### Options

| option           | type   | required | default | description                |
|------------------|--------|----------|---------|----------------------------|
| config.tableName | string | no       | `pages` | table name                 |
| config.slugField | string | no       | `slug`  | target field name for slug |
| config.siteId    | int    | no       | `1`     | site id                    |

### Example records

**Source**

```php
$record = [
   'slug' => 'foo-bar',
]
```

**Result**

```php
$record = [
   'slug' => '/path/to/page/foo-bar'
]

```
