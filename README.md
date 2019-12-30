# Include Interceptor

Library to intercept PHP includes. A fork of [icewind1991/interceptor](https://github.com/icewind1991/interceptor).

```
composer require nikic/include-interceptor
```

## Usage

```php
use Nikic\IncludeInterceptor\Interceptor;

$interceptor = new Interceptor(function(string $path) {
    if (!wantToIntercept($path)) {
        return null;
    }
    return transformCode(file_get_contents($path));
});
$interceptor->setUp(); // Start intercepting includes

require 'src/foo.php';

$interceptor->tearDown(); // Stop intercepting includes
```

The interception hook follows the following contract:

 * It is only called if the included file exists.
 * The passed `$path` is the realpath.
 * If the hook returns `null`, no interception is performed.
 * If the hook returns a string, these are taken as the transformed content of the file.

For convenience, a `FileFilter` is provided that implements white- and black-listing
of files and directories. Paths passed to `addBlackList()` and `addWhiteList()` should
always be realpaths.

```php
use Nikic\IncludeInterceptor\Interceptor;
use Nikic\IncludeInterceptor\FileFilter;

$fileFilter = FileFilter::createAllWhitelisted();    // Start with everything whitelisted
$fileFilter->addBlackList(__DIR__ . '/src/');        // Blacklist the src/ directory
$fileFilter->addWhiteList(__DIR__ . '/src/foo.php'); // But whitelist the src/foo.php file
$interceptor = new Interceptor(function(string $path) use ($fileFilter) {
    if (!$fileFilter->test($path)) {
        return null;
    }
    return transformCode(file_get_contents($path));
});
$interceptor->setUp();

require 'src/foo.php';
```
