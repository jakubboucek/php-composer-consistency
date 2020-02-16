# Vendor checker for PHP Composer
Package for your app – it allows your App to check himself has consistent vendor dir (direct API, no CLI).

`composer.json` <== (synchronized) ==> `/vendor`

## About package
For small teams can be difficult to cooperate and keep `/vendor` directory synchronized with requirements
in `composer.json`. Your colleagues can be a junior or can be not accustomed to right use the Composer.

You can force refresh Composer via git-hooks, or push all `/vendor` into your repo, but it's very dirty way.
Don't do it!

Or… just add this package to you project. It checks if you `/vendor` is consistent with project and can
notify you and your colleagues to forgotten refresh.

## Usage
Add this package to project as dev-dependency:
```bash
composer require --dev jakubboucek/composer-vendor-checker
```

In your app just call `validateReqs()` method:
```php
<?php
use JakubBoucek\ComposerConsistency\Checker;

Checker::validateReqs(__DIR__);
```

When `/vendor` is not consistent with `composer.json`, checker throws an Exception.

![Exception from Checker](https://cdn.jakub-boucek.cz/screenshot/190703-jptvw.png)

## Modes

### Strict/Lax mode
Nehlásí chybu, pokud nenajde soubory

POPZOR? výchozéí stan není striktní - vysvětlit v readme

### Silent mode
Vyhodí pouze chybu E_USER_ERROR, ne vyjímku

```php
Composer::validateConsistency($rootDir, $vendorDir, true, false);
(new Composer($rootDir))->strict(false)->validate($debugMode, E_USER_ERROR);
```

### Warning
Because you install Checker as dev-dependency, you should be very careful to hard-linking dependency
insinde your App. Is highly recommended to call validation only on development stage, not on production:

```php
if(Debugger::$productionMode === false) {
   Checker::validateReqs(__DIR__);
}
```

Or check if dependency exists:
```php
if(class_exists(Checker::class)) {
   Checker::validateReqs(__DIR__);
}
```

## Reference

### Class `Checker`

#### `__constructor($rootDir, ?string $vendorDir = null)`
Contructor creates Checker instance.\
Only required argument is absolute path to root directory (directory where checker search the `composer.lock` file).\
Optional argument is absolute path do `vendor` directory. Default is `{$rootDir}/vendor`.

#### `validate(): void`
Check if `/vendor` is consitent with app requirements and throw an Exception if is not.

#### `isReqsValid(): bool`
Check if `/vendor` is consitent with app requirements and return boolean of consistency status.

#### `validateReqs(string $rootDir, ?string $vendorDir = null): void`
Shortcut to call `validate()` without creating Checker instance before.