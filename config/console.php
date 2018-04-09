<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    //  如果直接使用原生的命令行  ./vendor/bin/apidoc api ./controllers  ./output
    //  会因为bower的目录为vender/bower(少了asset，在该目录下的composer.json文件可以看到)而无法找到对应的样式文件报错
    //  所以我就把这个路由加在了console里面，这里的aliases重新定义了路径
    //  现在的执行命令是 ./yii api ./controllers  ./output
    'controllerMap' => [
        'api' => [
            'class' => 'yii\apidoc\commands\ApiController',
        ],
        'guide' => [
            'class' => 'yii\apidoc\commands\GuideController',
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
