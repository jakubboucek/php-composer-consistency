# Vendor checker for PHP Composer
Package for your app – it allows your App to check himself has consistent vendor dir (direct API, no CLI).

`composer.json` <== (synchronized) ==> `/vendor`

## About package
For small teams can be difficult to cooperate and keep `/vendor` directory synchronized with requirements
in `composer.json`. Your colleagues can be a junior or can be not accustomed to right use the Composer.

You can force refresh Composer via git-hooks, but it requires careful preparation on each developer station.  
In other way you can push all `/vendor` into your repo, but it's very very dirty way. Don't do it!

Or… just add this package to you project. It checks if you `/vendor` is consistent with project and can
notify you and your colleagues to forgotten refresh.

## Usage
Add this package to project as dev-dependency:
```shell
composer require --dev jakubboucek/composer-consistency
```

In your app just call `validate()` method:
```php
ComposerConsistency::rootDir(__DIR__)->validate();
```

When `/vendor` is not consistent with `composer.json`, checker throws an Exception.

![Exception from Checker](https://cdn.jakub-boucek.cz/screenshot/190703-jptvw.png)

## Usage

```php
use JakubBoucek\ComposerConsistency\ComposerConsistency;

ComposerConsistency::rootDir(__DIR__)
    ->validate();
```

### Directories
- **Root dir** - directory which contains `composer.json`, rspt. `composer.lock` file,
usually root directory od project.
- **Vendor dir** - directory which contains Composer's `autoload.php` file.

By default checker is assumes Verdor dir at `vendor/` from root dir, you can change by method `vendorDir()`.

```php
ComposerConsistency::rootDir(__DIR__)
    ->vendorDir(__DIR__ . '/../vendor')
    ->validate();
```

### Error severity
When checker detects incosistence, it throws `ComposerInconsitencyException`. 

You can change exception throwing to emit user error by method `errorMode($severity)` where `$severity` is Severity of
emitted error, default is `E_USER_ERROR`, you can change it to any of `E_USER` family severity
([read more](https://www.php.net/manual/en/function.trigger-error.php#refsect1-function.trigger-error-parameters)).

```php
ComposerConsistency::rootDir(__DIR__)
    ->errorMode(E_USER_WARNING)
    ->validate();
```

Also you can disable checking by `errorMode(false)` – that's cause to completelly disable checking.

```php
ComposerConsistency::rootDir(__DIR__)
    ->errorMode(false)
    ->validate();
```

### Strict mode
In strict mode Checker throws Exception when is unable to read Composer's files used to
compute vendor consistency. Default value: `off`. 

Turn on Strict mode:
```php
ComposerConsistency::rootDir(__DIR__)
    ->strict()
    ->validate();
```

Scrict mode is by default disabled, because: 
- it can break production if you ignore some files (like `composer.lock`) during deploy - 
that's false positive,
- is not important to guard these files, for example when is missing whole `vendor/` directory, 
is unable to load this package too,
- main purpose of package is watching to subtle nuances in packages consistency, not fatals
in Composer's file system.

### Cache
Checking vendor consistency on every request consume unnecessarily huge CPU power. Ceche is store last success check
only check is composer files stay without change. It does not check consincy of packages until these files keep same
content. Default value: `off`.

```php
ComposerConsistency::rootDir(__DIR__)
    ->cache(__DIR__ . '/temp')
    ->validate();
```

### Froze mode
Checking vendor consistency on every request consume unnecessarily huge CPU power. Froze mode is usable when you
guarantee the deploy process to production is always purge the temp directory. It requires a path to temporary directory
for saving necessary files. Default value: `off`.

```php
ComposerConsistency::rootDir(__DIR__)
    ->froze(__DIR__ . '/temp')
    ->validate();
```

In Froze mode is vendor consistenty checked only once, then is state saved to temp directory and
no more checks are performed until is temp directory purged. It requires a path to temporary directory
for saving necessary files. Default value: `off`.
