<?php

use GreenCheap\Log\Handler\DebugBarHandler;
use GreenCheap\Log\Logger;

return [
    "name" => "log",

    "main" => function ($app) {
        $app["log"] = function ($app) {
            $logger = new Logger($this->name);

            if (isset($app["debugbar"])) {
                $logger->pushHandler($app["log.debug"]);
            }

            return $logger;
        };

        $app["log.debug"] = function () {
            return new DebugBarHandler();
        };
    },

    "autoload" => [
        "GreenCheap\\Log\\" => "src",
    ],

    "config" => [
        "name" => "log",
        "level" => 100,
    ],
];
