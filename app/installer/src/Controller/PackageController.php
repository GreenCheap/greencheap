<?php

namespace GreenCheap\Installer\Controller;

use GreenCheap\Application as App;
use GreenCheap\Installer\Package\PackageManager;
use Curl\Curl;
use GreenCheap\Routing\Annotation\Request;
use GreenCheap\Routing\Annotation\Route;
use GreenCheap\User\Annotation\Access;
use JetBrains\PhpStorm\ArrayShape;

/**
 * @Access("system: manage packages", admin=true)
 */
class PackageController
{
    /**
     * @var PackageManager
     */
    protected PackageManager $manager;

    public function __construct()
    {
        $this->manager = new PackageManager();
    }

    /**
     * @return array[]
     */
    #[ArrayShape(['$view' => "array", '$data' => "array"])]
    public function themesAction(): array
    {
        $packages = array_values(App::package()->all("greencheap-theme"));

        foreach ($packages as $package) {
            if ($module = App::module($package->get("module"))) {
                if (($settings = $module->get("settings")) and $settings[0] === "@") {
                    $settings = App::url($settings);
                }

                $package->set("enabled", true);
                $package->set("settings", $settings);
                $package->set("config", $module->config);
            }
        }

        return [
            '$view' => [
                "title" => __("Themes"),
                "name" => "installer:views/themes.php",
            ],
            '$data' => [
                "api" => App::get("system.api"),
                "packages" => $packages,
            ],
        ];
    }

    public function extensionsAction(): array
    {
        $packages = array_values(App::package()->all("greencheap-extension"));

        foreach ($packages as $package) {
            if ($module = App::module($package->get("module"))) {
                if (($settings = $module->get("settings")) and $settings[0] === "@") {
                    $settings = App::url($settings);
                }

                $package->set("enabled", true);
                $package->set("settings", $settings);
                $package->set("config", $module->config);
                $package->set("permissions", (bool) $module->get("permissions"));
            }
        }

        return [
            '$view' => [
                "title" => __("Extensions"),
                "name" => "installer:views/extensions.php",
            ],
            '$data' => [
                "api" => App::get("system.api"),
                "packages" => $packages,
            ],
        ];
    }

    /**
     * @Request({"name"}, csrf=true)
     * @param $name
     * @return string[]
     */
    public function enableAction($name): array
    {
        $handler = $this->errorHandler($name);

        if (!($package = App::package($name))) {
            App::abort(400, __('Unable to find "%name%".', ["%name%" => $name]));
        }

        App::module()->load($package->get("module"));

        if (!($module = App::module($package->get("module")))) {
            App::abort(400, __('Unable to enable "%name%".', ["%name%" => $package->get("title")]));
        }

        $module->runMarketplaceFile();

        if (!$module->isCompatibleSystem(App::version(), $module->getCompatibleSystemVersion(), ">")) {
            App::abort(400, __("The Package Is Not Suitable For GreenCheap Version."));
        }

        foreach ($module->getRequirements() as $name => $version) {
            $requireModule = App::package()->get($name);
            if (!$requireModule || !App::module($requireModule->get("module"))) {
                App::abort(400, __("To activate this module, the %package% module must be installed.", ["%package%" => $requireModule->get("module")]));
            }
            if (!$module->isCompatibleSystem($requireModule->get("version"), $version)) {
                App::abort(400, __("The version of the %package% module that needs to be installed does not match or is outdated.", ["%package%" => $requireModule->get("module")]));
            }
        }

        $this->manager->enable($package);

        App::exception()->setExceptionHandler($handler);

        return ["message" => "success"];
    }

    /**
     * @Request({"name"}, csrf=true)
     * @param $name
     * @return string[]
     */
    public function disableAction($name)
    {
        if (!($package = App::package($name))) {
            App::abort(400, __('Unable to find "%name%".', ["%name%" => $name]));
        }

        if (!($module = App::module($package->get("module")))) {
            App::abort(400, __('"%name%" has not been loaded.', ["%name%" => $package->get("title")]));
        }

        $this->manager->disable($package);

        App::module("system/cache")->clearCache();

        return ["message" => "success"];
    }

    /**
     * @Request({"type": "string"}, csrf=true)
     * @param $type
     * @return array
     */
    public function uploadAction($type)
    {
        $file = App::request()->files->get("file");

        if ($file === null || !$file->isValid()) {
            App::abort(400, __("No file uploaded."));
        }

        $package = $this->loadPackage($file->getPathname());

        if (!$package->getName() || !$package->get("title") || !$package->get("version")) {
            App::abort(400, __('"composer.json" file not valid.'));
        }

        if ($package->get("type") !== "greencheap-" . $type) {
            App::abort(400, __("No GreenCheap %type%", ["%type%" => $type]));
        }

        $filename = str_replace("/", "-", $package->getName()) . "-" . $package->get("version") . ".zip";

        $file->move(App::get("path") . "/tmp/packages", $filename);

        return compact("package");
    }

    /**
     * @param int $id
     * @return array|null
     * @Request({"package_name":"string"}, csrf=true)
     */
    public function downloadPackageAction(string $package_name)
    {
        if (!$package_name) {
            App::abort(404, __("Not Found ID"));
        }

        $url = App::get("system.api") . "/api/atomy/app_store_packages/download?package_name=" . $package_name;

        $file = App::file();

        $zip_name = tempnam(App::get("path.temp"), "package_");

        $path = App::get("path.temp") . "/";

        $curl = new Curl();
        $curl->setOpt(CURLOPT_ENCODING, "");
        $curl->setOpt(CURLOPT_TIMEOUT, 1000);
        $curl->setOpt(CURLOPT_CONNECTTIMEOUT, 1000);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, false);
        $curl->download($url, $zip_name);

        if ($curl->error) {
            return App::jsonabort(500, $curl->errorMessage);
        }

        $package = $this->loadPackage($zip_name);

        if (!$package->getName() || !$package->get("title") || !$package->get("version")) {
            return App::jsonabort(400, __('"composer.json" file not valid.'));
        }

        $filename = str_replace("/", "-", $package->getName()) . "-" . $package->get("version") . ".zip";

        $file->copy($zip_name, App::get("path") . "/tmp/packages/" . $filename);

        return compact("package");
    }

    /**
     * @Route(methods="POST")
     * @Request({"package": "array", "packagist": "boolean"}, csrf=true)
     * @param array $package
     * @param bool $packagist
     * @return
     */
    public function installAction($package = [], $packagist = false)
    {
        $file = App::path() . "/tmp/temp/composer/composer.json";

        if (!file_exists(dirname($file))) {
            mkdir(dirname($file), 0755, true);
            file_put_contents($file, "{}");
        }

        return App::response()->stream(function () use ($package, $packagist) {
            try {
                $package = App::package()->load($package);

                if (!$package) {
                    throw new \RuntimeException("Invalid parameters.");
                }

                $this->manager->install([(string) $package->getName() => $package->get("version")], $packagist);

                echo "\nstatus=success";
            } catch (\Exception $e) {
                printf("%s\nstatus=error", $e->getMessage());
            }
        });
    }

    /**
     * @Route(methods="POST")
     * @Request({"name":"string"}, csrf=true)
     * @param $name
     * @return
     */
    public function uninstallAction($name)
    {
        return App::response()->stream(function () use ($name) {
            try {
                $this->manager->uninstall($name);

                echo "\nstatus=success";
            } catch (\Exception $e) {
                printf("%s\nstatus=error", $e->getMessage());
            }
        });
    }

    protected function loadPackage($file)
    {
        if (is_file($file)) {
            $zip = new \ZipArchive();

            if ($zip->open($file) === true) {
                $json = $zip->getFromName("composer.json");

                if ($json && ($package = App::package()->load($json))) {
                    $extra = $package->get("extra");

                    if (isset($extra["icon"]) || isset($extra["image"])) {
                        unset($extra["icon"]);
                        unset($extra["image"]);
                        $package->set("extra", $extra);
                    }

                    $package->set("shasum", sha1_file($file));
                }

                $zip->close();
            }
        }

        if (isset($package) && $package) {
            return $package;
        }

        App::abort(400, __('Can\'t load json file from package.'));
    }

    /**
     * @param  string $name
     * @return callable|null
     */
    protected function errorHandler($name)
    {
        ini_set("display_errors", 0);

        return App::exception()->setExceptionHandler(function ($exception) use ($name) {
            while (ob_get_level()) {
                ob_get_clean();
            }

            $message = __('Unable to activate "%name%".<br>A fatal error occured.', ["%name%" => $name]);

            if (App::debug()) {
                $message .= "<br><br>" . $exception->getMessage();
            }

            App::response()
                ->json($message, 500)
                ->send();
        });
    }
}
