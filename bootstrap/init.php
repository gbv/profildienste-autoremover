<?php

use Config\Config;
use Services\LogService;
use Services\MailerService;
use Services\DatabaseService;
use Services\StatsService;
use Services\ValidatorService;

/**
 * Initializes the DI container
 *
 * @param \Pimple\Container $container
 */
function initContainer(\Pimple\Container $container) {

    $container['config'] = function () {
        return new Config();
    };

    $container['mailerService'] = function ($container) {
        return new MailerService($container['logService'], $container['config'], $container['statsService'],
            $container['resourceFolder']);
    };

    $container['logService'] = function ($container) {
        return new LogService($container['config']);
    };

    $container['databaseService'] = function ($container) {
        return new DatabaseService($container['config']);
    };

    $container['validatorService'] = function ($container) {
        return new ValidatorService($container['config'], $container['databaseService'],
            $container['logService'], $container['mailerService']);
    };

    $container['statsService'] = function () {
        return new StatsService();
    };
}