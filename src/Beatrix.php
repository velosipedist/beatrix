<?php
namespace beatrix {

	use Illuminate\Container\Container;
	use Philo\Blade\Blade;
	use PhpConsole\Connector;
	use PhpConsole\Helper;
	use Slim\Slim;

	/**
	 * Base micro http application listening certain folders for common modules routing.
	 * Also contains required dependencies for once configured libs.
	 * @property Container $ioc
	 * @property Blade $blade
	 * @property BladeView $view
	 * @property Connector $console
	 */
	class Beatrix extends Slim
	{
		public function __construct(array $userSettings = array()) {
			parent::__construct($userSettings);

			Helper::register();
			// register debugger
			$this->container->singleton('console', function(){
				return Connector::getInstance();
			});
		}

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
			//todo priority
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
			$routeParams = $this->router->getCurrentRoute()->getParams();
			$params = array_merge($routeParams, $params);

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

	/**
	 * @see Beatrix
	 */
	class Beatrix extends \beatrix\Beatrix
	{
		/**
		 * @param array $settings
		 * @return \Beatrix
		 * @see Slim::__construct()
		 */
		public static function paperbag($settings = array()) {
			return new \Beatrix($settings);
		}
	}
}
 