Installation
============

## Requirements
* PHP >=5.4
* TYPO3 CMS 6.2 - 8.x


## Installation

Command line
```bash
composer require typo3/cms 7.6.x
composer require cpsit/t3import_export
```

composer.json
```
{
  ...
  "require": {
    "typo3/cms": "^7.6",
    "cpsit/t3import_export": "^0.6.2"
  },
}
```

Currently the extension is not yet available in the TYPO3 Extension Repository (TER).
After installation via composer you have to activate it in the TYPO3 Extension Manager.

## Sources

* [Packagist](https://packagist.org/packages/cpsit/t3import_export)
* [github](https://github.com/dwenzel/t3import_export)
