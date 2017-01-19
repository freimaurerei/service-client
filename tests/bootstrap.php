<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');
require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
Yii::setAlias('@app', dirname(__DIR__));

$config = [
    'id' => 'unit',
    'basePath' => __DIR__,
    'aliases' => [
        'tests' => __DIR__,
        'vendor' => realpath(__DIR__ . '/../vendor'),
    ],
    'components' => [
        'i18n' => [
            'translations' => [
                '*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => '@app/messages',
                ],
            ],
        ],
        'cache' => \yii\caching\DummyCache::class,
    ],
];

new \yii\console\Application($config);