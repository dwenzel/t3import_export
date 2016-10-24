Overview
========

## Configuration
Import and export tasks are configured via TypoScript. Please referr to the [Configuration](./CONFIGURATION.MD)


## Execution
Pre-configured Tasks can be performed via Backend-Module, Scheduler or command line.

## Flow

Each program call performs the following steps:

* build queue
* for each task
    * perform Initializers
    * for each record in queue perform
        * PreProcessors
        * Converters
        * PostProcessors
        * persist
    * persist all
    * perform Finishers
