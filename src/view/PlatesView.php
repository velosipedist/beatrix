<?php
namespace beatrix\view;

use League\Plates\Engine;
use Slim\View;

/*
 * Plates View decorator for extending View possibilities and implement Slim contracts.
 */

class PlatesView extends View
{
    /**
     * @var PlatesEngine
     */
    private $plates;
    private $fallbackDir;

    public function __construct()
    {
        parent::__construct();
        $this->plates = new PlatesEngine();
    }

    /**
     * @return Engine
     */
    public function getPlates()
    {
        return $this->plates;
    }

    protected function render($template, $data = null)
    {
        $data = array_merge($this->data->all(), (array)$data);
        return $this->plates->make($template, $data);
    }

    public function setTemplatesDirectory($directory)
    {
        parent::setTemplatesDirectory($directory);
        $this->fallbackDir = $directory;
        $this->plates->setDirectory($directory);
    }

    public function addTemplatesDirectory($alias, $directory, $fallback = null)
    {
        $this->plates->addFolder($alias, $directory, $fallback);
    }
}
