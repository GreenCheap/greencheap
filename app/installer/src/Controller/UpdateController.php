<?php

namespace GreenCheap\Installer\Controller;

use GreenCheap\Application as App;
use GreenCheap\Installer\SelfUpdater;
use GreenCheap\Routing\Annotation\Request;
use GreenCheap\User\Annotation\Access;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * @Access("system: software updates", admin=true)
 */
class UpdateController
{

    /**
     * @return array[]
     */
    public function indexAction(): array
    {
        return [
            '$view' => [
                'title' => __('Update System'),
                'name' => 'installer:views/update.php'
            ],
            '$data' => [
                'version' => App::version(),
                'system_api' => App::get('system.api'),
                'settings' => App::module('system')->config(['beta']),
                'project_uri' => App::url()->base(0),
                'project_title' => App::config('system/site')->get('title')
            ]
        ];
    }

    /**
     * @Request({"constraint": "string"}, csrf=true)
     * @param $constraint
     * @return array
     */
    public function downloadAction($constraint): array
    {
        $url = App::get('system.api').'/api/brain/download/'.$constraint;
        $file = tempnam(App::get('path.temp'), 'update_');

        App::session()->set('system.update', $file);
        if (!$hi = file_put_contents($file, @fopen($url, 'r'))) {
            App::abort(500, $hi);
        }
        return [];
    }

    /**
     * @Request(csrf=true)
     */
    public function updateAction()
    {
        if (!$file = App::session()->get('system.update')) {
            App::abort(400, __('You may not call this step directly.'));
        }
        App::session()->remove('system.update');

        return App::response()->stream(function () use ($file) {
            $output = new StreamOutput(fopen('php://output', 'w'));
            try {

                if (!file_exists($file) || !is_file($file)) {
                    throw new \RuntimeException('File does not exist.');
                }

                $updater = new SelfUpdater($output);
                $updater->update($file);

            } catch (\Exception $e) {
                $output->writeln(sprintf("\n<error>%s</error>", $e->getMessage()));
                $output->write("status=error");
            }

        });
    }
}
