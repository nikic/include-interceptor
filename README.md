# Include Interceptor

[![Build Status](https://travis-ci.org/nikic/include-interceptor.svg?branch=master)](https://travis-ci.org/nikic/include-interceptor)

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
$interceptor->setUp();

require 'src/foo.php';
```

For convenience, a `FileFilter` is provided that implements white- and black-listing
of files and directories:

```php
use Nikic\IncludeInterceptor\Interceptor;
use Nikic\IncludeInterceptor\FileFilter;

$fileFilter = FileFilter::createDefault();
$fileFilter->addWhiteList('');                       // Whitelist everything
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

## API

- `addWhiteList(string $path)`: Add a folder to the white list
- `addBlackList(string $path)`: Add a folder to the black list
 - Only white listed files will be intercepted
 - A file is white listed if it has a parent folder that is white listed
 without there being a more direct parent folder that is black listed
- `addHook(callable $hook)`: Register a hook to the intercepter
- `setUp()` start intercepting includes
- `tearDown()` stop intercepting includes
