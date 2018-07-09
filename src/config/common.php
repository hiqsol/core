<?php

use yii\di\Reference;

return [
    \yii\di\Container::class => function ($container) {
        return $container;
    },

    \yii\di\Factory::class => Reference::to('factory'),
    'factory' => [
        '__class' => \yii\di\Factory::class,
        '__construct()' => [
            0 => [],
            1 => Reference::to(\yii\di\Container::class),
        ],
    ],

    \yii\di\Injector::class => Reference::to('injector'),
    'injector' => [
        '__class' => \yii\di\Injector::class,
    ],

    \yii\base\Application::class => Reference::to('application'),
    'application' => [
        'aliases' => [
            '@root'     => dirname(__DIR__, 5),
            '@vendor'   => dirname(__DIR__, 4),
        ],
        'params' => $params,
    ],

    \yii\base\ErrorHandler::class => Reference::to('errorHandler'),
    'errorHandler' => [
    ],

    \yii\base\View::class => Reference::to('view'),
    'view' => [
    ],

    \yii\base\Request::class => Reference::to('request'),
    'request' => [
    ],

    \yii\base\Response::class => Reference::to('response'),
    'response' => [
    ],

    'security' => [
        '__class' => \yii\base\Security::class,
    ],
    'formatter' => [
        '__class' => \yii\i18n\Formatter::class,
    ],
    'i18n' => [
        '__class' => \yii\i18n\I18N::class,
    ],
    'profiler' => [
        '__class' => \yii\profile\Profiler::class,
    ],

    \yii\profile\ProfilerInterface::class => Reference::to('profiler'),
];
