Import & Export
===============
[![code coverage](https://codecov.io/gh/dwenzel/t3import_export/branch/main/graph/badge.svg)](https://codecov.io/gh/dwenzel/t3import_export)

Imports data from different sources into TYPO3 and exports from TYPO3 to different targets.
Possible data sources and targets are databases, and files (XML, CSV...).
Import and export tasks are described via TypoScript and can be grouped to sets.
Tasks can be executed manually via backend module or command line or automatically as a scheduler task.

## Support matrix

| TYPO3 | Branch              | Latest release | Status |
|-------|---------------------|----------------|--------|
| 13.4  | `main`              | none yet (3.0.0 planned) | in development |
| 12.4  | `support/typo3-v12` | 2.0.1          | bugfixes |
| 11.5  | `support/typo3-v11` | none yet       | in development, see [#19](https://github.com/dwenzel/t3import_export/issues/19) |
| 9.5, 10.4 | none            | 1.0.5          | not maintained |
| 6.2 - 8.7 | none            | 0.14.0         | not maintained |

See [CONTRIBUTING.md](CONTRIBUTING.md) for the branching model and the release process.


## Table of contents
* [Installation and requirements](/Documentation/INSTALL.md)
* [Overview](/Documentation/OVERVIEW.md)
* [Configuration](/Documentation/CONFIGURATION.md)
* [Components](/Documentation/COMPONENT.md)
    * [Initializers](./Documentation/Components/Initializer.md)
    * [PreProcessors](./Documentation/Components/PREPROCESSOR.md)
    * [Converters](./Documentation/Components/CONVERTER.md)
    * [PostProcessors](./Documentation/Components/POSTPROCESSOR.md)
    * [Finishers](./Documentation/Components/FINISHER.md)
* [Persistence](./Documentation/Persistence.md)
* Services
    * [DatabaseConnectionService](./Documentation/Service/DatabaseConnectionService.md)
* [Development Guide](./Documentation/DEVELOPMENT.md)

## Credits
Thanks for contribution and feedback

* [Sebastian Kreideweiss](https://github.com/kreidewe)
* [Jan-Henrik Hempel](https://github.com/motordigital)
* [Benjamin Rannow](https://github.com/brannow)
* [Nicole Cordes](https://github.com/IchHabRecht)
* [Vladimir Falcón Piva](https://github.com/vladimirfalconpiva)
* [Benjamin Schütz](https://github.com/schuetzbenjamin)

This extension has been developed at [CPS IT GmbH](https://cps-it.de).
