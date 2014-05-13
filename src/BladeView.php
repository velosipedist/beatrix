<?php
namespace beatrix;
use Illuminate\View\FileViewFinder;
use Philo\Blade\Blade;
use Slim\View;

class BladeView extends View{
	/** @var Blade */
	private $blade;

	public function __construct() {
		parent::__construct();
		$cachePath = $this->defaultCachePath();
		$viewPaths = $this->getTemplatesDirectory();
		$this->rebindBlade($viewPaths, $cachePath);
	}

	protected function render($template, $data = null) {
		$data = array_merge($this->data->all(), (array) $data);
		return $this->blade->view()->make($template, $data);
	}
	public function blade($template, $data = null) {
		return $this->render($template, $data);
	}

	public function addTemplatesDirectory($directory) {
		$dirs = array_unique(array_merge($this->blade->viewPaths, (array)$directory));
		$this->rebindBlade($dirs);
	}
	public function setTemplatesDirectory($directory) {
		$this->rebindBlade($directory);
	}

	/**
	 * @param $viewPaths
	 * @param $cachePath
	 */
	private function rebindBlade($viewPaths, $cachePath = null) {
		if(is_null($cachePath)){
			$cachePath = $this->defaultCachePath();
		}
		$this->blade = new Blade(
			$viewPaths,
			$cachePath
		);
	}

	/**
	 * @return string
	 */
	private function defaultCachePath() {
		$cachePath = str_finish($_SERVER['DOCUMENT_ROOT'], '/') . '/bitrix/cache/blade';
		mkdir($cachePath, 0777, true);
		return $cachePath;
	}

}
 