<?php
namespace beatrix\view\blade;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Philo\Blade\Blade;
use Slim\View;

/*
 * Plates View decorator for extending View possibilities and implement Slim contracts.
 */

class BladeView extends View
{
    /**
     * @var Blade
     */
    private $bladeWrapper;

    public function __construct()
    {
        parent::__construct();
        $bladeCachePath = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/blade/';
        if (!file_exists($bladeCachePath)) {
            mkdir($bladeCachePath, 0777, true);
        }
        //todo configure compilation mode - in debug mode templates always should recompile
        $this->bladeWrapper = new Blade([], $bladeCachePath);
    }

    /**
     * @return Factory
     */
    public function getBladeEngine()
    {
        return $this->bladeWrapper->view();
    }

    /**
     * @return BladeCompiler
     */
    public function getBladeCompiler()
    {
        return $this->bladeWrapper->getCompiler();
    }

    protected function render($template, $data = null)
    {
        return $this->getBladeEngine()->make($template, $this->data->all(), (array)$data);
    }

    public function setTemplatesDirectory($directory)
    {
        parent::setTemplatesDirectory($directory);
        $this->getBladeEngine()->addLocation($directory);
    }

    /**
     * @param string $namespace Templates group alias, to be uesd as alias::path/subdir/etc
     * @param string $directory
     */
    public function addNamespace($namespace, $directory)
    {
        $this->getBladeEngine()->addNamespace($namespace, $directory);
    }
}
