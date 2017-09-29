Clean
============

Cleans or transforms string fields by default PHP functions.

The source field has to be defined by name. Each field needs a process instruction.

Most functions are just routed to the original PHP functions without any parameters.

The function **strip_empty_tags** is an exception and used a RegEx to remove empty HTML tags.


### Example

```
preProcessors {
	40 {
		class = CPSIT\T3importExport\Component\PreProcessor\Clean
		config {
			fields {
				abstract {
					str_replace {
						search = http://
						replace = https://
					}
					stripslashes = true
					strip_emptytags = true
					strip_tags = true
					strip_spaces = true
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
