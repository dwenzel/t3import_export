LookUpDB
========
The LookUpDB PreProcessor gets a value from the source record, creates a sql
lookup query and sets a new value based on the lookup result to the processed
record.


### Example
```yaml
preProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\LookUpDB"
    config:
      # the field of the processed record that gets the result
      targetField: target_fieldname
      select:
        # the database table where we want to look for an entry
        table: lookup_tablename
        # the columns we like to use as result of the lookup
        fields: uid
        where:
          AND:
            # compare the column title of the lookup table with a value
            condition: "title="
            # the field of the source record whose value is compared
            value: source_fieldname
        singleRow: true
      fields:
        # map the column uid of the lookup row to a field of the record
        uid:
          mapTo: target_fieldname
```

### Options

| option | description |
|---|---|
| `targetField` | Field of the record for the result (required). Without `singleRow` it gets a list of the mapped rows. |
| `select.table` | Table to look in (required) |
| `select.fields` | Columns to read, comma separated, default `*` |
| `select.where` | Constraints `AND`, `OR` and `IN`, see below |
| `select.groupBy`, `select.orderBy`, `select.limit` | SQL parts of the query |
| `select.singleRow` | Use only the first row; its mapped columns are set in the record itself |
| `fields.<column>.mapTo` | Field that gets the value of the column |
| `childRecords` | Field with a list of child records; the lookup is done for each child record instead of the record |

`condition` of `AND` and `OR` is SQL; the value of the record field `value`
(with an optional `prefix`) is quoted and appended to it. The query uses the
connection that `select.table` is mapped to and does not apply the
restrictions of TYPO3 (`deleted`, `hidden`). The configuration must come from
trusted sources.

### Constraints

`select.where` accepts the keys `AND`, `OR` and `IN`, each once. The
constraints are joined in the order of the configuration, without
parentheses; `IN` is joined with `AND`. SQL evaluates `AND` before `OR`: the
keys `IN`, `AND`, `OR` result in `field IN (...) AND a OR b`, which matches
`(field IN (...) AND a) OR b`.

### IN constraint

`IN` looks for rows whose column `field` has one of the values of the record
field `values.field` (an array). An optional `values.prefix` is put in front
of each value.

```yaml
preProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\LookUpDB"
    config:
      targetField: categories
      select:
        table: sys_category
        fields: uid
        where:
          IN:
            field: title
            keepOrder: 1
            values:
              field: categoryTitles
      fields:
        uid:
          mapTo: uid
```

* An empty array of values matches nothing. Without an `OR` constraint no
  query is run and the record gets no result.
* With `keepOrder` the rows are returned in the order of the values. The rows
  are sorted in PHP after the query, so this works on all databases. Values
  are compared case-insensitively and without trailing spaces. Rows whose
  value is not in the list come last. `limit` and `singleRow` are applied
  after sorting.
* `keepOrder` has no effect if `groupBy` is set.

### Empty result

If the query returns no rows, the target field is removed from the record.
A target field that was not in the record is not set.
