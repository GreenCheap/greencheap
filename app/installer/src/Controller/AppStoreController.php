<?php

namespace GreenCheap\Installer\Controller;

use GreenCheap\Application as App;
use GreenCheap\Routing\Annotation\Request;
use GreenCheap\User\Annotation\Access;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use JetBrains\PhpStorm\ArrayShape;

/**
 * @Access("system: manage packages", admin=true)
 */
class AppStoreController
{
    /**
     * @Request({"filter":"array" , "page":"int"})
     * @param array $filter
     * @param int|null $page
     * @return array
     */
    #[ArrayShape(['$view' => "array", '$data' => "array"])]
    public function indexAction(array $filter = [], int $page = null): array
    {
        return [
            '$view' => [
                "title" => __("Extension List"),
                "name" => "installer:views/marketplace.php",
            ],
            '$data' => [
                "config" => [
                    "filter" => (object)$filter,
                    "page" => $page,
                ],
                "app_type" => 1,
                "installedPackages" => App::package()->all("greencheap-extension"),
            ],
        ];
    }

    /**
     * @Request({"filter":"array" , "page":"int"})
     * @param array $filter
     * @param int|null $page
     * @return array
     */
    #[ArrayShape(['$view' => "array", '$data' => "array"])]
    public function themesAction(array $filter = [], int $page = null): array
    {
        return [
            '$view' => [
                "title" => __("Themes List"),
                "name" => "installer:views/marketplace.php",
            ],
            '$data' => [
                "config" => [
                    "filter" => (object)$filter,
                    "page" => $page,
                ],
                "app_type" => 2,
                "installedPackages" => App::package()->all("greencheap-theme"),
            ],
        ];
    }

    /**
     * @Route("/package")
     * @Request({"name":"string"})
     * @param string $name
     * @return array
     * @throws GuzzleException
     */
    #[ArrayShape(['$view' => "string[]", '$data' => "array"])]
    public function packageAction(string $name = ""): array
    {
        $client = new Client([
            "verify" => false,
            "base_uri" => App::get("system.api")
        ]);

        $hasPackage = false;
        $isUpdate = false;

        if (!$name) {
            App::abort(500, __("Name parameter missing"));
        }

        try {
            $w = $client->get(
                "/api/atomy/app_store_packages/getpackage",
                [
                    RequestOptions::JSON => [
                        "package" => $name
                    ]
                ]
            );
            $package = json_decode($w->getBody()->getContents());
        } catch (ClientException $clientException) {
            App::abort($clientException->getResponse()->getStatusCode(), $clientException->getResponse()->getBody()->getContents());
        }

        $package->content = App::content()->applyPlugins($package->content, ['package' => $package, 'markdown' => true]);
        $package->changelog = App::content()->applyPlugins($package->changelog, ['package' => $package, 'markdown' => true]);
        $packages = App::package()->all();

        if(array_key_exists($package->package_name, $packages)){
            $origin_package = App::package()->get($package->package_name);
            $hasPackage = true;
            
            if(version_compare($package->version, $origin_package->get("version"), ">")){
                $isUpdate = true;
            }
        }

        return [
            '$view' => [
                "title" => $package->title,
                "name" => "installer:views/package.php"
            ],
            '$data' => [
                "package" => $package,
                "hasPackage" => $hasPackage,
                "isUpdate" => $isUpdate,
                "system_api" => App::get("system.api")
            ]
        ];
    }
}
