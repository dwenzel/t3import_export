Components
==========

Import and export tasks are performed by a data transfer processor.

Each task has a data source and target and may request sub-tasks which will be performed by components.

Currently the following types of components are implemented:

* [Initializers](./Components/INITIALIZER.md)
* [PreProcessors](./Components/PREPROCESSOR.md)
* [Converters](./Components/CONVERTER.md)
* [PostProcessors](./Components/POSTPROCESSORR.md)
* [Finishers](./Components/FINISHER.md)

A set of standard components is included. You may write your own components and include them by configuration in TypoScript. 

There exist interfaces for each type of component. Your custom class **must** implement one of them.

Please see [Overview](./OVERVIEW.md) for the general application flow. 
A more in-depth description of components can be found in the correspondend sections linked above. There are references for single components too.
