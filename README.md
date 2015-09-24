## PHP Client for BudgetMailer API

This Repository contains PHP BudgetMailer API Client. To use this client, you need to have BudgetMailer Account. To run this code, you will need at least PHP >= 5.3 compiled with JSON and socket support.
```
Composer Package Name: professio/budgetmailer-php-api
```

## Code Example

For complete code usage example, please see files in example directory.

## Motivation

This Project was created as generic PHP implementation of BudgetMailer REST-JSON API, and will be used in all PHP based BudgetMailer API implementations.

## Installation

You will need configuration file (see examples), and optionally writeable cache directory (recommended it may safe up to 50% API calls). You may want to use this project as single file for simple inclure/require into your project or as multiple PSR-4 autoloading standard compatible files (in that case you will need to setup autoloading, or use composer/packagist autoloader).

## API Reference

This project doesn't come with API reference, however it's fully documented with PHPDOC.

## Tests

You can find tests in tests/ and src/BudgetMailer/Api/Tests/ directories. You can run them with PHPUNIT (go to tests/ directory, rename phpunit.xml.dist to phpunit.xml and run phpunit.phar).

## Contributors

Professio BudgetMailer info@budgetmailer.nl

## License

BudgetMailer API PHP Client is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

BudgetMailer API PHP Client is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with BudgetMailer API PHP Client. If not, see http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt.
