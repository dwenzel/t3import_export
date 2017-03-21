# sets.ts
# import configuration for sets
module.tx_t3importexport.settings.import.sets {
	testSet {
		label = Example Set
		description (
          An example import set containing the tasks <i>foo, bar, baz</i>.<br />
		  Note: This set will fail since none of its task exist!
		)
		tasks = foo,bar,baz
	}
}
