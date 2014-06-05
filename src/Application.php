<?php
namespace beatrix {

	use beatrix\iblock\URL;
	use beatrix\middlewares\AfterApiMethod;
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
	class Application extends Slim
	{
		public function __construct(array $userSettings = array()) {
			parent::__construct($userSettings);

			Helper::register();
			// register debugger
			$this->container->singleton('console', function(){
				return Connector::getInstance();
			});
			$self = $this;
			$this->error(function(\Exception $e) use ($self){
				$message = $e->getMessage();

				if($bitrixError = app()->GetException()){
					$message.= "\r\n<br>[ ". $bitrixError->msg." ]";
				}

				if ($self->request->isAjax()) {
					$self->config('debug', false);
					$self->response->headers['content-type'] = 'application/json';
					print json_encode(array('message' => $message));
				} else {
					if($this->config('debug')){
						throw new UnhanledException($message, 0, $e);
					} else {
						print "error 500";
					}
				}
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
		 * @return $this
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

			$queryParamsExtended = URL::extendQueryParams($queryParams);

			$url = parent::urlFor($name, $params);
			if($queryParams){
				$url .= '?' . $queryParamsExtended;
			}
			return $url;
		}

	}

	class UnhanledException extends \Exception{}
}

namespace {

	/**
	 * @see Beatrix
	 */
	class Beatrix extends \beatrix\Application
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
 