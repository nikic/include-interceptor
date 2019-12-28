# Include Interceptor

[![Build Status](https://travis-ci.org/nikic/include-interceptor.svg?branch=master)](https://travis-ci.org/nikic/include-interceptor)

Library to intercept PHP includes. A fork of [icewind1991/interceptor](https://github.com/icewind1991/interceptor).

```
composer require nikic/include-interceptor
```

## Usage

```php
use Nikic\IncludeInterceptor\Interceptor;

$interceptor = new Interceptor();
$interceptor->addWhiteList(__DIR__ . '/src');
$interceptor->addHook(function($code) {
    return str_replace('foo', 'bar', $code);
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
 - the registered callback will be called for every include which is intercepted
 - The code being included will be passed as the first argument
 - The path being included will be passed as second argument
 - If the hook returns a string the loaded code will be replaced by the return value
- `setUp()` start intercepting includes
- `tearDown()` stop intercepting includes
