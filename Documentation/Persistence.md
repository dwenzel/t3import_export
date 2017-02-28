Persistence
===========

Import and export tasks use data sources and targets. 

A set of standard sources and targets is included. You may write your own and include them by configuration in TypoScript. 

There exist interfaces for sources and targets. Your custom class **must** implement one of them.

Please see [Overview](./OVERVIEW.md) for the general application flow and detailed description referenced below (non-linked components are not yet documented)
* Sources
    * [DataSourceXML](./Persistence/DataSourceXML.md)
    * DataSourceDB
    * DataSourceDynamicRepository
* Targets
    * DataTargetDB
    * DataTargetFileStream
    * DataTargetRepository
    * DataTargetXMLStream
