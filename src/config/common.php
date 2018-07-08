<?php

return [
    \yii\base\Application::class => \yii\di\Reference::to('application'),
    'application' => [
        'aliases' => [
            '@root'     => dirname(__DIR__, 5),
            '@vendor'   => dirname(__DIR__, 4),
        ],
        'params' => $params,
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
];;
