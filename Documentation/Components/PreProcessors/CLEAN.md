Clean
============

Cleans or transforms string fields by default PHP functions.

The source field has to be defined by name. Each field needs a process instruction.

All function are just routed to the original PHP functions without any parameters.

The function **strip_empty_tags** is an exception and used a RegEx to remove empty HTML tags.


### Example

```
preProcessors {
	40 {
		class = CPSIT\T3importExport\Component\PreProcessor\Clean
		config {
			fields {
				abstract {
					stripslashes = true
					strip_empty_tags = true
					strip_tags = true
					htmlspecialchars = true
					trim = true
					ltrim = true
					rtrim = true
					strtolower = true
					strtoupper = true
				}
			}
		}
	}
}
```
