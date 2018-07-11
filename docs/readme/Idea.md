
- разделён на части, НЕ радикально, а конструктивно:
    - yiisoft/core - всё кроме выделенных нижеперечисленных частей
    - yiisoft/di - работает как есть без переделки, нашёл пару багов
    - yiisfot/web - всё из папки web [hiqsol/web]
    - yiisfot/console - всё из папки console, но надо ещё смотреть, не делал ещё
    - yiisoft/log - всё из папки log, с прицелом на заменяемость любым PSR логгером [hiqsol/log]
    - yiisoft/cache - всё из папки cache, с прицелом на заменяемость любым PSR кешом
    - yiisoft/db - не всегда нужна база
    - yiisoft/rbac - не всегда нужен rbac
    - можно переводы по языкам: messages-ru, messages-uk
    - что ещё? см. [ниже](#parts)
- в каждой части своя конфигурация, для понимания см. примеры ниже
    - собирается с помощью [composer-config-plugin], можно подумать о другом
      собирателе конфигов, но этот уже оттесченый, а других нет :)
    - понимаю, спорный момент с первого взгляда, но на самом делал это может
      стать главной фишкой фреймфорка
    - выкидываются нахер всякие coreComponents
      и костыли в виде мержа кусков конфига раскиданные по коду
    - конфиг превращается из конфига приложения в конфиг контейнера и в нём уже
      есть конфиг приложения и сервисов
- yii2-composer - не нужен
    - тип пакета yii2-extension - не нужен, extension'ы будут просто library
    - ещё yii2-composer собирает extensions.php который используется для алиасов и bootstrap'а
    - composer-config-plugin делает всё то же только более эффективно
      так как запускается вне приложения из композера
- выпиливается поддержка механизма bootstrap'а
    - чтобы дописать конфиг - composer-config-plugin
    - для всего остального конфигурировать триггеры на ивенты
- DI
    - выпиливается `ServiceLocator`, везде юзается DI
    - выпиливаются компоненты из `Application` и модулей
        - из моего опыта DI вполне достаточно
    - `Application` сииильно сокращается, я ещё не всё повыпиливал
- выпиливается `Configurable` и `Yii::configure(),` с ними выпиливается `init()`
    - выпиливается $config (последний параметр конструктора)
    - Configurable объекты работали так:
        - в конструктор передаётся массив конфиг
        - конфиг применяется с помощью `Yii::configure()`
        - конструктор вызывает `init()`
        - это всё не работает с новым DI так как он:
            - вызывает конструктор, а потом уже устанавливает свойства
            - теоретически можно вызывать `init()` через конфиг DI,
              но надо заботится чтоб он вызывался после свойств
    - нужно фиксить все классы где есть `init()`
    - это серьёезный BC-break, но всё становится понятнее
        - не нужно пилить yiisoft/di под `Configurable`
        - инициализацию надо заменять геттерами что делает инициализацию отложенной
- Yii становится чистым хелпером без статических глобальных свойств
    - НЕ НУЖНО реквайрить Yii в entry script'е
    - переносится к остальным хелперам -> `yii\helpers\Yii`
    - выпиливаются "глобальные переменные" `Yii::$app`, `Yii::$logger` и др.
    - алиасы переносятся в `Application`
    - остаются: `Yii::t()`, `Yii::createObject()` + функции логгинга и профайлинга
    - имхо `Yii::createObject()` надо деприкейтить и для начала выпиливать использование,
      а потом и прибить совсем в пользу `Factory::create()`
    - стопку define'ов пока перенёс в `yii\base\Application`, но будет перенесена в
      `src/config/defines.php`, composer-config-plugin собирает и дефайны
    - XXX: не придумал как лучше: чтоб оно работало надо делать `Yii::setContainer($container)` в entry script'е
- cleanup
    - папку web заменить на public и алиас `@webroot` заменить на `@public` ?
    - хочу сделать меньше файлов в нагруженых папках (base, web)- чуть больше папок, но не выращивая глубину
        - эксепшены в папку exceptions и в base и в web
        - web/formatters
        - подумать: url, action

[hiqsol/web]:               https://github.com/hiqsol/web
[hiqsol/log]:               https://github.com/hiqsol/log
[composer-config-plugin]:   https://github.com/hiqdev/composer-config-plugin

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

Часть конфига из `yiisoft/core`, [src/config/common.php](https://github.com/hiqsol/core/blob/master/src/config/common.php):

```php
<?php

return [
    \yii\base\Application::class => Reference::to('app'),
    'app' => [
        'aliases' => [
            '@root'     => dirname(__DIR__, 5),
            '@vendor'   => dirname(__DIR__, 4),
        ],
        'params' => $params,
    ],

    \yii\base\Request::class => Reference::to('request'),
    \yii\base\View::class => Reference::to('view'),
];
```

Часть конфига из `yiisoft/web`, [src/config/web.php](https://github.com/hiqsol/web/blob/master/src/config/web.php):

```php
<?php

return [
    'app' => [
        '__class' => \yii\web\Application::class,
        'id' => 'web',
        'name' => 'web',
    ],

    'request' => [
        '__class' => \yii\web\Request::class,
    ],
    'view' => [
        '__class' => \yii\web\View::class,
    ],
];
```

Часть конфига из `yiisoft/console`, [src/config/console.php](https://github.com/hiqsol/web/blob/master/src/config/console.php):

```php
<?php

return [
    'app' => [
        '__class' => \yii\console\Application::class,
        'id' => 'console',
        'name' => 'console',
    ],

    'request' => [
        '__class' => \yii\console\Request::class,
    ],
    'view' => [
        '__class' => \yii\console\View::class,
    ],
];
```

Часть конфига из `yiisoft/log`, [src/config/common.php](https://github.com/hiqsol/log/blob/master/src/config/common.php):

```php
<?php

return [
    'logger' => [
        '__class' => \yii\log\Logger::class,
    ],

    \Psr\Log\LoggerInterface::class => \yii\di\Reference::to('logger'),
];
```

### Parts

| Size    | folder        | destination          | comments                         |
|--------:|---------------|----------------------|----------------------------------|
| 1008K   | db            | yiisoft/db           |                                  |
| 828K    | messages      | yiisoft/core         | split to yiisoft/messages-ru/uk  ???  |
| 588K    | web           | yiisoft/web          |                                  |
| 468K    | helpers       | yiisoft/core         |                                  |
| 412K    | base          | yiisoft/core         |                                  |
| 292K    | console       | yiisoft/console      |                                  |
| 212K    | validators    | yiisoft/core         |                                  |
| 192K    | i18n          | yiisoft/core         |                                  |
| 168K    | widgets       | yiisoft/core         |                                  |
| 152K    | caching       | yiisoft/cache        | provides psr/simple-cache        |
| 148K    | rbac          | yiisoft/rbac         |                                  |
| 132K    | filters       | yiisoft/web          |                                  |
| 124K    | views         | yiisoft/core         |                                  |
| 116K    | data          | yiisoft/core         |                                  |
| 84K     | http          | yiisoft/core         |                                  |
| 84K     | log           | yiisoft/log          | provides psr/log                 |
| 76K     | behaviors     | yiisoft/core         |                                  |
| 72K     | grid          | yiisoft/core         |                                  |
| 60K     | di            | yiisoft/di           | provides psr/container-implementation |
| 52K     | requirements  | yiisoft/core         |                                  |
| 52K     | mail          | yiisoft/core         |                                  |
| 44K     | test          | yiisoft/core         | часть в core, часть в db         |
| 36K     | mutex         | yiisoft/core         |                                  |
| 28K     | profile       | yiisoft/core         |                                  |
| 24K     | serialize     | yiisoft/core         |                                  |

