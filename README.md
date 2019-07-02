# Vendor checker for PHP Composer
Package for your app – it allows your app to check himself has consistent vendor dir (direct API, no CLI).

`composer.json` <== (synchronized) ==> `/vendor`

## About package
For small teams can be difficult to cooperate and keep `/vendor` directory synchronized with requirements
in `composer.json`. Your colleagues can be a junior or can be not accustomed to right use the Composer.

You can force refresh Composer via git-hooks, or push all `/vendor` into your repo, but it's very dirty way.
Don't do it!

Or… just add this package to you project. It checks if you `/vendor` is consistent with project and can
notify you and your colleagues to forgotten refresh.

## Usage
Add this package to project as dev dependency:
```bash
composer require --dev jakubboucek/composer-vendor-checker
```

In your app just call `validateReqs()` method:
```php
\JakubBoucek\ComposerVendorChecker\Checker::validateReqs(__DIR__);
```

When `/vendor` is not consistent with `composer.json`, checker throws an Exception.

![Exception from Checker](https://cdn.jakub-boucek.cz/screenshot/190703-jptvw.png)