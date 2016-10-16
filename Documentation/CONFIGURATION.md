Configuration
=============

All configuration is done via TypoScript. 

You may put your configuration into any Template record. Best practice is to place all configuration into a file and include it.

## Tasks
An import task is described at the TypoScript path:

```
module.tx_t3importexport.settings.import.tasks.<task identifier>
```

An export task at:
```
module.tx_t3importexport.settings.export.tasks.<task identifier>
```
Identifiers **must** be unique and **must not** contain white space or dots (.)

Configuration for import task with identifier *event*:

```
module.tx_t3importexport.settings.import.tasks {
  event {
    label = Incremental import of events
    description = Imports recent events from foo.com.
   ...
  }
}
```

Each configuration of a task **must** contain the keys
 * source 
 * target
 
 and **may** contain the keys
 
 * label
 * description
 * initializers
 * preProcessors
 * converters
 * postProcessors
 * finishers
 
 If *label* and *description* are set they will appear in the Backend module. Otherwise only the key is shown.
 
 For *description* HTML markup is allowed.
 
 The keys *initializers*, *preProcessors*, *postProcessors*, *converters*, *postProcessors*, *finishers* **must** contain at least one
 sub-key. Arbitrary identifiers are allowed numbers recommended.
 
 ```
 ...
     preProcessors {
       10 {
         ... configuration for first pre processor
       }
       20 {
         ... configuration for second pre processor
       }
     }
  ...   
 ```
  
 ## Sets
 An import set is described at the TypoScript path:
 
 ```
 module.tx_t3importexport.settings.import.sets.<set identifier>
 ```
 
 An export task at:
 ```
 module.tx_t3importexport.settings.export.sets.<set identifier>
 ```
Identifiers **must** be unique.

Each configuration of a set **must** contain the key
 * tasks
 
 and **may** contain the keys

 * label
 * description
 
 If label and description are set they will appear in the Backend module. Otherwise only the key is shown.
 
Configuration for import set with identifier *fullImport*:

```
module.tx_t3importexport.settings.import.set {
  fullImport {
   label = Full Import
   description = This is a really huge import set.
   tasks = event,internet,allTheRest
}
```
