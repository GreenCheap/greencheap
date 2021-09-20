<?php

use GreenCheap\Installer\Package\PackageFactory;
use GreenCheap\Kernel\Exception\NotFoundException;

return [
    "name" => "installer",

    "main" => function ($app) {
        $app["package"] = function ($app) {
            return (new PackageFactory())->addPath($app["path"] . "/packages/*/*/composer.json");
        };

        if ($this->config["enabled"]) {
            $app->extend("assets", function ($factory) use ($app) {
                $factory->setVersion($app["version"]);

                return $factory;
            });

            $app["routes"]->add([
                "path" => "/installer",
                "name" => "@installer",
                "controller" => "GreenCheap\Installer\Controller\InstallerController",
            ]);

            $app->on("request", function ($event, $request) use ($app) {
                $locale = $request->get("locale") ?: $app["request"]->getPreferredLanguage();
                $available = $app->module("system/intl")->getAvailableLanguages();

                if (isset($available[$locale])) {
                    $app->module("system/intl")->setLocale($locale);
                }
            });

            $app->error(function () use ($app) {
                return $app["response"]->redirect("@installer");
            });
        }
    },

    "require" => ["application", "system/cache", "system/intl", "system/view"],

    "routes" => [
        "/system/package" => [
            "name" => "@system/package",
            "controller" => "GreenCheap\Installer\Controller\PackageController",
        ],
        "/system/appstore" => [
            "name" => "@system/appstore",
            "controller" => "GreenCheap\Installer\Controller\AppStoreController",
        ],
        "/system/update" => [
            "name" => "@system/update",
            "controller" => ["GreenCheap\Installer\Controller\UpdateController", "GreenCheap\Installer\Controller\ApiUpdateController"],
        ],
    ],

    "languages" => "/../system/languages",

    "resources" => [
        "installer:" => "",
    ],

    "permissions" => [
        "system: manage packages" => [
            "title" => "Manage extensions and themes",
            "description" => "Manage extensions and themes",
        ],
        "system: software updates" => [
            "title" => "Apply system updates",
            "trusted" => true,
        ],
    ],

    "menu" => [
        "system: extensions" => [
            "label" => "Extensions",
            "parent" => "system: system",
            "url" => "@system/package/extensions",
            "access" => "system: manage packages",
            "priority" => 5,
        ],

        "system: themes" => [
            "label" => "Themes",
            "parent" => "system: system",
            "url" => "@system/package/themes",
            "access" => "system: manage packages",
            "priority" => 10,
        ],

        "appstore" => [
            "label" => "App Store",
            "layout" => "sidebar",
            "icon" => "system/theme:assets/system-app-icons/appstore.svg",
            "url" => "@system/appstore",
            "active" => "@system/appstore*",
            "priority" => 120,
        ],

        "appstore: extensions" => [
            "label" => "Extensions",
            "parent" => "appstore",
            "url" => "@system/appstore",
            "active" => "@system/appstore",
            "priority" => 0,
        ],

        "appstore: themes" => [
            "label" => "Themes",
            "parent" => "appstore",
            "url" => "@system/appstore/themes",
            "active" => "@system/appstore/themes",
            "priority" => 5,
        ],

        "system: update" => [
            "label" => "Update",
            "parent" => "system: system",
            "url" => "@system/update",
            "access" => "system: software updates",
            "priority" => 100,
        ],
    ],

    "config" => [
        "enabled" => false,
        "release_channel" => "stable",
    ],
];
