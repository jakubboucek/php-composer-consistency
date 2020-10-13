# PHP Composer consistency checker 
Checks if the `/vendor` directory is consistent with the project's `/composer.lock` (direct API, no CLI).

`/composer.json` <== (synchronized) ==> `/vendor`

![Code Analysis](https://github.com/jakubboucek/php-composer-consistency/workflows/Code%20Analysis/badge.svg)

## About
For small teams can be difficult to cooperate and keep `/vendor` directory synchronized with requirements
in `/composer.json`. Your colleagues can be a junior or can be not accustomed to the right of use the Composer and
often forgot to call `composer install` before running the App after update code from remote.

You can force refresh Composer via git-hooks, but it requires careful preparation on each developer station.  
In another way, you can push the whole `/vendor` into your repo, but it's a very very dirty way. Don't do it!

Or… just add this package to you project. It checks if your `/vendor` is consistent with the project and can
notify you and your colleagues to forgotten refresh it.

## Usage
Add this package to project **as dev-dependency**:
```shell
composer require --dev jakubboucek/composer-consistency
```

In your app just call `validate()` method:
```php
ComposerConsistency::rootDir(__DIR__)->validate();
```

When `/vendor` directory is not consistent with `/composer.json`, checker throws an Exception.

![Exception from Checker](https://cdn.jakub-boucek.cz/screenshot/201014-php-composer-consistency-exception.png)

## Usage

```php
use JakubBoucek\ComposerConsistency\ComposerConsistency;

ComposerConsistency::rootDir(__DIR__)
    ->validate();
```

### Directories
- **Root directory** - directory which contains `/composer.json`, rspt. `/composer.lock` file, usually the root directory
of the project.
- **Vendor directory** - directory which contains Composer's `autoload.php` file.

By default, the checker is assuming Vendor dir at `/vendor` from root directory, you can change by method `vendorDir()`.

```php
ComposerConsistency::rootDir(__DIR__)
    ->vendorDir(__DIR__ . '/../vendor')
    ->validate();
```

### Error severity
When the checker detects any inconsistency, it throws `ComposerInconsitencyException`. 

You can change exception throwing to emit user error by method `errorMode($severity)` where `$severity` is Severity of
emitted error, default is `E_USER_ERROR`, you can change it to any of `E_USER` family severity
([read more](https://www.php.net/manual/en/function.trigger-error.php#refsect1-function.trigger-error-parameters)):

```php
ComposerConsistency::rootDir(__DIR__)
    ->errorMode(E_USER_WARNING)
    ->validate();
```

Also, you can disable checking by `errorMode(false)` – that's cause to completelly disable checking:
```php
ComposerConsistency::rootDir(__DIR__)
    ->errorMode(false)
    ->validate();
```

### Strict mode
In strict mode, the checker throws Exception when is unable to read Composer's files used to
compute vendor consistency. Default value: `off`. 

Turn on Strict mode:
```php
ComposerConsistency::rootDir(__DIR__)
    ->strict()
    ->validate();
```

Strict mode is by default disabled, because: 
- it can break production if you ignore some files (like `/composer.lock`) during deploy - 
that's a false positive,
- is not important to guard these files, for example when is missing the whole `/vendor` directory, 
is unable to load this package too,
- the main purpose of the package is watching to subtle nuances in packages consistency, not fatal in the Composer's
file system.

### Cache
Checking `/vendor` consistency on every request consumes unnecessarily huge CPU power. The cache is storing the last
success validation result. It does not more check consistency until these files keep the same content. It requires
a path to a temporary directory for saving necessary files. Default value: `off`.

```php
ComposerConsistency::rootDir(__DIR__)
    ->cache(__DIR__ . '/temp')
    ->validate();
```

### Froze mode
Checking `/vendor` consistency on every request consumes unnecessarily huge CPU power. Froze mode is usable when you
guarantee the deploy process to production is always purge the temp directory. It requires a path to a temporary
directory for saving necessary files. Recommended in the production environment. Default value: `off`.

```php
ComposerConsistency::rootDir(__DIR__)
    ->froze(__DIR__ . '/temp')
    ->validate();
```

In Froze mode is vendor consistency checked only once, then is state saved to the temp directory and no more checks are
performed until is temp directory purged. The difference between `cache` and `froze` behavior is `cache` mode is
checking files if is not modified and `froze` does don't do it.
