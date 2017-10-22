<?php

use Brisum\Lib\ObjectManager;
use Symfony\Component\Console\Application;

define('DEPLOY_ROOT_DIR', dirname(__FILE__) . '/');
define('DEPLOY_CONFIG_DIR', DEPLOY_ROOT_DIR . 'config/');
define('DEPLOY_EXPLOIT_DIR', DEPLOY_ROOT_DIR . 'exploit/');
define('DEPLOY_TMP_DIR', DEPLOY_ROOT_DIR . 'tmp/');

$autoload = require(DEPLOY_ROOT_DIR . 'vendor/autoload.php');

$configList = [];
$config = [
    'preference' => [],
    'virtualType' => [],
    'type' => [
        'Brisum\Lib\ObjectManager' => [
            'shared' => true
        ]
    ],
];
$sharedInstances = [
    'Composer\Autoloader' => $autoload,
];

foreach ($configList as $configItem) {
    $config['preference'] = array_merge($config['preference'], $configItem['preference']);
    $config['virtualType'] = array_merge($config['virtualType'], $configItem['virtualType']);
    $config['type'] = array_merge($config['type'], $configItem['type']);
}

$objectManager = new ObjectManager($config, $sharedInstances);
ObjectManager::setInstance($objectManager);

/** @var Application $application */
$application = $objectManager->create('Symfony\Component\Console\Application');

$application->add($objectManager->create('Brisum\Deploy\Console\TestCommand'));
$application->add($objectManager->create('Brisum\Deploy\Console\FilesFetchCommand'));
$application->add($objectManager->create('Brisum\Deploy\Console\DbExportCommand'));

$application->run();
