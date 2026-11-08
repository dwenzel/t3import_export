EvaluateExpressions
===================
Sets fields of the record from expressions. The expressions are evaluated with
the [Symfony ExpressionLanguage](https://symfony.com/doc/current/reference/formats/expression_language.html).

The fields are evaluated in the configured order. Each expression gets the
variable `record` with the current record, including fields set by earlier
expressions of the same pre-processor.

## Configuration

### Required
* **class** *string*
  fully qualified class name
  *CPSIT\T3importExport\Component\PreProcessor\EvaluateExpressions*

* **config.fields** *array*
  field name => expression. The result of the expression is written to the
  field.

An invalid expression throws an `InvalidConfigurationException`.

### Functions
Besides the [built-in syntax](https://symfony.com/doc/current/reference/formats/expression_language.html)
(`~` concatenates strings, `?:`, `??`, `? :`, `in`, `matches`), the following
functions are available:

| Function | PHP function |
|---|---|
| `upper(string)` | `mb_strtoupper()` |
| `lower(string)` | `mb_strtolower()` |
| `trim(string)` | `trim()` |
| `date(format, timestamp)` | `date()` |
| `strtotime(string)` | `strtotime()` |

### Example
```yaml
preProcessors:
  10:
    class: 'CPSIT\T3importExport\Component\PreProcessor\EvaluateExpressions'
    config:
      fields:
        teaser: "record['teaser'] ?: record['abstract']"
        title: "upper(trim(record['title']))"
        bodytext: "'<p>' ~ record['bodytext'] ~ '</p>'"
        starttime: "strtotime(record['date'])"
        hidden: "(record['status'] ?? '') == 'archived' ? 1 : 0"
```

## Migration from RenderContent
[RenderContent](./RENDER_CONTENT.md) renders TypoScript content objects. It is
deprecated and will be removed in version 4.0
([#75](https://github.com/dwenzel/t3import_export/issues/75)).

| TypoScript (`TEXT`) | Expression |
|---|---|
| `field = teaser // abstract` | `record['teaser'] ?: record['abstract']` |
| `value = foo` | `'foo'` |
| `field = title` + `case = upper` | `upper(record['title'])` |
| `field = title` + `trim = 1` | `trim(record['title'])` |
| `field = bodytext` + `wrap = <p>\|</p>` | `'<p>' ~ record['bodytext'] ~ '</p>'` |
| `field = tstamp` + `date = Y-m-d` | `date('Y-m-d', record['tstamp'])` |
| `COA` with `10`, `20` | concatenation with `~` |
| `if.isTrue.field = x` | `record['x'] ? ... : ...` |

Data that needs a frontend page (`getData` with `TSFE:` or `page:`, references
like `< lib.foo`) has no equivalent.
