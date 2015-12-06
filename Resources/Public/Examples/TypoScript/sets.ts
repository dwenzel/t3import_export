# sets.ts
# import configuration for sets
module.tx_t3import.settings.importProcessor.sets {
	testSet {
		description = A description for test set
		tasks = venue,events,seminar
	}
	persons {
		description = all person types for events
		tasks = employee,responsible,speaker
	}
}