LookUpDB
========
The LookUpDB PreProcessor gets a value from the source record, creates a sql
lookup query and sets a new value based on the lookup result to the processed
record.


### Example
```
class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB
config {
  select {

    # the database table where we want to look for an entry
    table = lookup_tablename

    # the column we like to use as result of the lookup proprocessor
    fields = uid

    where {
      AND {

        # the sql where clause, in this case we like to compare the title
        # column of the lookup table with a value
        condition = title=

        # the attribute of our source record we want to copmpare with the title
        # column
        value = source_fieldname

      }
    }
    singleRow = true
  }

  # the attribute containing the lookup value in our processed record after the
  # pre processing
  targetField = target_fieldname

  fields {

    # map the uid from the lookup record to the attribute in our pre processor
    # record
    uid {
      mapTo = target_fieldname
    }
  }
}
```

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
