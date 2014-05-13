<?php
namespace beatrix {

	use Illuminate\Container\Container;
	use Philo\Blade\Blade;
	use Slim\Slim;

	/**
	 * Base micro http application listening certain folders for common modules routing.
	 * Also contains required dependencies for once configured libs.
	 * @property Container $ioc
	 * @property Blade $blade
	 * @property BladeView $view
	 */
	class Beatrix extends Slim
	{
		/**
		 * @return \Beatrix
		 */
		public static function app() {
			return static::getInstance();
		}

		/**
		 * Set app templates directory
		 * @param $path
		 */
		public function addViewsDir($path) {
			// Default view
			$this->view->addTemplatesDirectory($path);
			return $this;
		}

		/**
		 * Just override for setup
		 */
		public static function getDefaultSettings() {
			$parent = parent::getDefaultSettings();
			$parent['log.enabled'] = false;
			$parent['routes.case_sensitive'] = false;
			$parent['view'] = 'beatrix\BladeView';
			return $parent;
		}

		/**
		 * @param string $name
		 * @param array $params
		 * @param array $queryParams
		 * @return string
		 */
		public function urlFor($name, $params = array(), $queryParams = array()) {
			if(is_null($name)){
				$name = $this->router->getCurrentRoute()->getName();
			}
			$queryParamsAdd = parse_url($this->request->getUrl(), PHP_URL_QUERY);
			if ($queryParamsAdd) {
				$queryParamsAdd = [parse_str($queryParamsAdd)];
			} else {
				$queryParamsAdd = array();
			}
			$queryParams = array_merge($queryParamsAdd, $queryParams);

			$url = parent::urlFor($name, $params);
			if($queryParams){
				$url .= '?' . http_build_query($queryParams);
			}
			return $url;
		}

	}
}

namespace {
	use beatrix\BladeView;

	/**
	 * @see Beatrix
	 */
	class Beatrix extends \beatrix\Beatrix
	{

	}
}
 