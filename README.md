# Yii 3.0 proposal :: NOT FOR ACTUAL USE

[![Latest Stable Version](https://poser.pugx.org/hiqsol/core/v/stable)](https://packagist.org/packages/hiqsol/core)
[![Total Downloads](https://poser.pugx.org/hiqsol/core/downloads)](https://packagist.org/packages/hiqsol/core)
[![Build Status](https://img.shields.io/travis/hiqsol/core.svg)](https://travis-ci.org/hiqsol/core)
[![Scrutinizer Code Coverage](https://img.shields.io/scrutinizer/coverage/g/hiqsol/core.svg)](https://scrutinizer-ci.com/g/hiqsol/core/)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/hiqsol/core.svg)](https://scrutinizer-ci.com/g/hiqsol/core/)
[![Dependency Status](https://www.versioneye.com/php/hiqsol:core/dev-master/badge.svg)](https://www.versioneye.com/php/hiqsol:core/dev-master)

This package is Yii 3.0 architecture changes proposal.

# NOT INTENDED FOR ACTUAL USE

## Idea

- framework gets split into parts, not radically, but functionally
    - `yiisoft/core` - all former framework but all the following
    - `yiisoft/di` - as is, fixed some bugs
    - `yiisfot/yii-web` - everything from `web` and `filter` folders, can be used without yiisoft/console, but requires core
    - `yiisfot/yii-console` - everything from `console` folder, can be used without yiisoft/web, but requires core
    - `yiisoft/log` - everything from `log` folder, any PSR compatible cache could be used, can be done framework independent
    - `yiisoft/cache` - everything from `caching` folder, any PSR compatible cache could be used, can be done framework independent
    - `yiisoft/db` - not all apps need it, can be done framework independent
    - `yiisoft/rbac` - not all apps need it, can be done framework independent
    - also should be considered:
        - grid
        - widgets
        - split db and ActiveRecord
    - also please see discussion https://github.com/hiqsol/core/issues/1
- don't mention framework version anywhere besides version constraints in `composer.json` files
    - rename yii extensions with `yii-` prefix
    - rule of thumb:
        - if extension requires `yiisoft/core` directly or through dependencies - then prefix with `yii-`
        - if extension can be used without yii (completely or partially) - name without `yii-` prefix
        - if extension provides more functions with yii - suggest yii
- `yiisfot/core` requires only virtual psr implementations instead of concrete yii packages
    - actually not all psr implementations will work right now, but it's a declaration of intentions
      and will be implemented sooner or later
- every part provides it's own configuration in `config` folder, see examples below
    - summary config is assembled with [composer-config-plugin], 
      we can think about other config assembling tool, but this one is already
      tested and there are no others :)
    - I understand it is most arguable question but it can become main framework feature
    - allows to throw away things like coreComponents and other crutches like merging
      config parts available in the framework code
    - the config becomes the config of DI container holding configs for application
      and all the services (previously it was config of application)
    - allows to create onion applications and plugins, please see [my article]
    - also please see discussion https://github.com/hiqsol/core/issues/2
- `yii2-composer` - not needed anymore
    - yii2-extension composer package type is not need, extensions will become `library`
    - also yii2-composer assembles `extensions.php` used for aliases and bootstrap
      composer-config-plugin does all the same but more effectively
- think of completely remove bootstrap feature:
    - it was mostly used by extensions to merge into application config - composer-config-plugin
      must be used for it
    - event triggers should be configured for all other cases
        - which improves performance by running tasks not for every request but for certain only
- DI
    - completely remove `ServiceLocator`, use DI instead
    - completely remove components support from `Application` and `Module`
        - according to my experiense, DI is quite enough
    - `Application` becomes really shorter and simpler
- completely remove `Configurable` and `Yii::configure(),` and `init()` goes with them
    - remove everywhere `$config` (last constructor argument)
    - `Configurable` worked this way:
        - `$config` array was passed to constructor
        - `$config` gets applied with `Yii::configure()`
        - constructor runs `init()`
        - it doesn't work with new DI because of the following:
            - new DI calls constructor and then sets props
            - init cannot be called from constructor
            - in theory `init()` could be called with DI, but
              it's necessary to ensure it to be called after settings all props
    - it is necessary to fix all classes having `init()`
    - it is serious BC-break, but everything gets simpler:
        - no need to make changes to `yiisoft/di`
        - initialization better be substituted with getters which makes
          it deferred so more productive in theory
- redo `Yii` as clean helper, without global static properties:
    - no need to require Yii in entry script
    - move to all other helpers `yii\helpers\Yii`
    - remove "global variables" `Yii::$app`, `Yii::$logger` and so on
    - move aliases to `Application`
    - leave only: `Yii::t()`, `Yii::createObject()` and logging and profiling functions
    - to make them working `Yii::setContainer($container)` must be called in entry script
    - but if no container is defined - make all the functions operating in default way
      like logging with PHP built-in capabilities
    - I think `Yii::createObject()` should be deprecated, starting with removing its use
      in the framework and then remove it completely in next version in favour of `Factory::create()`
    - bunch of defines will be moved to `config/defines.php`, composer-config-plugin
      assembles defines too
- cleanup
    - rename folder `web` to `public`
    - rename alias `@webroot` to `@public`
    - I want to make less files in heavy loaded folders (base, web), making a bit more folders,
      but without growing folder depth
        - move all exceptions to own folder in base and web
        - move web formatters to own folder
        - think of: url, action

[hiqsol/web]:               https://github.com/hiqsol/web
[hiqsol/log]:               https://github.com/hiqsol/log
[composer-config-plugin]:   https://github.com/hiqdev/composer-config-plugin
[my article]:               https://hiqdev.com/pages/articles/app-organization

### Entry script

```php
<?php
use hiqdev\composer\config\Builder;
use yii\di\Container;
use yii\helpers\Yii;

(function () {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once Builder::path('defines');

    $container = new Container(require Builder::path('web'));
    Yii::setContainer($container);

    $container->get('app')->run();
})();
```

### Config examples

Sample from `yiisoft/core`, [src/config/common.php](https://github.com/hiqsol/core/blob/master/src/config/common.php):

```php
<?php

return [
    yii\base\Application::class => Reference::to('app'),
    'app' => [
        'aliases' => [
            '@root'     => dirname(__DIR__, 5),
            '@vendor'   => dirname(__DIR__, 4),
        ],
        'params' => $params,
    ],

    yii\base\Request::class => Reference::to('request'),
    yii\base\View::class => Reference::to('view'),
];
```

Sample from `yiisoft/web`, [src/config/web.php](https://github.com/hiqsol/web/blob/master/src/config/web.php):

```php
<?php

return [
    'app' => [
        '__class' => yii\web\Application::class,
        'id' => 'web',
        'name' => 'web',
    ],

    'request' => [
        '__class' => yii\web\Request::class,
    ],
    'view' => [
        '__class' => yii\web\View::class,
    ],
];
```

Sample from `yiisoft/console`, [src/config/console.php](https://github.com/hiqsol/web/blob/master/src/config/console.php):

```php
<?php

return [
    'app' => [
        '__class' => yii\console\Application::class,
        'id' => 'console',
        'name' => 'console',
    ],

    'request' => [
        '__class' => yii\console\Request::class,
    ],
    'view' => [
        '__class' => yii\console\View::class,
    ],
];
```

Sample from `yiisoft/log`, [src/config/common.php](https://github.com/hiqsol/log/blob/master/src/config/common.php):

```php
<?php

return [
    'logger' => [
        '__class' => yii\log\Logger::class,
    ],

    Psr\Log\LoggerInterface::class => yii\di\Reference::to('logger'),
];
```

### Parts and folders

| Size    | folder        | destination          | comments                                                         |
|--------:|---------------|----------------------|------------------------------------------------------------------|
| 1008K   | db            | yiisoft/db           |                                                                  |
| 828K    | messages      | yiisoft/core         | split to yiisoft/messages-ru yiisoft/messages-uk  ???            |
| 588K    | web           | yiisoft/yii-web      |                                                                  |
| 468K    | helpers       | yiisoft/core         |                                                                  |
| 412K    | base          | yiisoft/core         |                                                                  |
| 292K    | console       | yiisoft/yii-console  |                                                                  |
| 212K    | validators    | yiisoft/core         |                                                                  |
| 192K    | i18n          | yiisoft/core         |                                                                  |
| 168K    | widgets       | yiisoft/core         |                                                                  |
| 152K    | caching       | yiisoft/cache        | provides psr/simple-cache-implementation                         |
| 148K    | rbac          | yiisoft/rbac         |                                                                  |
| 132K    | filters       | yiisoft/yii-web      |                                                                  |
| 124K    | views         | yiisoft/core         |                                                                  |
| 116K    | data          | yiisoft/core         |                                                                  |
| 84K     | http          | yiisoft/core         |                                                                  |
| 84K     | log           | yiisoft/log          | provides psr/log-implementation                                  |
| 76K     | behaviors     | yiisoft/core         | db related to be moved to db                                     |
| 72K     | grid          | yiisoft/core         |                                                                  |
| 60K     | di            | yiisoft/di           | provides psr/container-implementation                            |
| 52K     | requirements  | yiisoft/core         |                                                                  |
| 52K     | mail          | yiisoft/core         |                                                                  |
| 44K     | test          | yiisoft/core         | some files can be moved to db                                    |
| 36K     | mutex         | yiisoft/core         |                                                                  |
| 28K     | profile       | yiisoft/core         |                                                                  |
| 24K     | serialize     | yiisoft/core         |                                                                  |

## License

This project is released under the terms of the BSD-3-Clause [license](LICENSE).
Read more [here](http://choosealicense.com/licenses/bsd-3-clause).

Copyright © 2018, sol (http://hiqdev.com/)
