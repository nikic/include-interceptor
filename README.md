# Interceptor

[![Build Status](https://travis-ci.org/icewind1991/interceptor.svg?branch=master)](https://travis-ci.org/icewind1991/interceptor)
[![Code Coverage](https://scrutinizer-ci.com/g/icewind1991/interceptor/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/icewind1991/interceptor/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/icewind1991/interceptor/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/icewind1991/interceptor/?branch=master)

Intercept php includes

```
composer require cewind/interceptor
```

## Usage

```php
use Icewind\Interceptor\Interceptor;

$interceptor = new Interceptor();
$interceptor->addWhiteList(__DIR__ . '/src');
$interceptor->addHook(function($code) {
    return str_replace('foo', 'bar', $code);
});

$instance->setUp();

require 'src/foo.php'
```

## API

- `addWhiteList(string $path)`: Add a folder to the whitelist, only files located in whitelisted folders will be intercepted
- `addHook(callable $hook)`: Register a hook to the intercepter
 - the registered callback will be called for every include which is intercepted
 - The code being included will be passed as the first argument
 - The path being includded will be passed as seccond argument
 - If the hook returns a string the loaded code will be replaced by the return value
- `setUp()` start intercepting includes
