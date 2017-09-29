ImplodeArray
============

Implodes an array or nested array to a string value.

The source field has to be defined by name and optional by a child array name (useful for XML imports) and a glue.


### Example

For example a source array as XML

```
<tags>
	<tag>something</tag>
	<tag>anything</tag>
</tags>
```

becomes a nested array like this by DataSourceXML

```
array (size=1)
  'tag' => 
    array (size=2)
      0 => string 'something'
      1 => string 'anything'
```

and can be imploded by the folloing configuration

```
preProcessors {
	30 {
		class = CPSIT\T3importExport\Component\PreProcessor\ImplodeArray
		config {
			fields {
				tags {
					child = tag
					wrap = , 
				}
			}
		}
	}
}
```
