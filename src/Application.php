<?php
namespace beatrix;

use beatrix\exception\UnhanledException;
use beatrix\iblock\NavigationHelper;
use beatrix\view\PlatesView;
use Slim\Slim;

/**
 * Base micro HTTP application listening certain folders for common modules routing.
 * Also contains required dependencies for preconfigured libs.
 * @property PlatesView $view
 */
class Application extends Slim
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $userSettings = array())
    {
        parent::__construct($userSettings);
        // fix trailing slash url parsing issue
        $this->environment['PATH_INFO'] = rtrim($this->environment['PATH_INFO'], '/');

        $this->view->addTemplatesDirectory('beatrix', __DIR__ . '/../templates');
        $self = $this;
        $this->error(function (\Exception $e) use ($self) {
            $message = $e->getMessage();

            if ($bitrixError = app()->GetException()) {
                $message .= "\r\n<br>[ " . $bitrixError->msg . " ]";
            }

            if ($self->request->isAjax()) {
                $self->config('debug', false);
                $self->response->headers['content-type'] = 'application/json';
                print json_encode(array('message' => $message));
            } else {
                if ($self->config('debug')) {
                    throw new UnhanledException($message, 0, $e);
                } else {
                    print "error 500";
                }
            }
        });
        $this->setupRoutes();
    }

    /**
     * Override for some default bootstrap
     * {@inheritdoc}
     */
    public static function getDefaultSettings()
    {
        $parent = parent::getDefaultSettings();
        $parent['log.enabled'] = false;
        $parent['routes.case_sensitive'] = false;
        $parent['view'] = '\beatrix\view\PlatesView';
        return $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function urlFor($name, $params = array(), $queryParams = array())
    {
        if (is_null($name)) {
            $name = $this->router->getCurrentRoute()->getName();
        }

        $url = parent::urlFor($name, $params);
        if ($queryParams) {
            $url .= '?' . NavigationHelper::extendQueryParams($queryParams);
        }
        return $url;
    }

    private function setupRoutes()
    {
        //todo centralize routing management
//        $routes = $this->config(\Beatrix::SETTINGS_ROUTES) ?: array();
//        foreach ($routes as $name => $config) {
//            $this->router->addNamedRoute($name, new Route($config['pattern']));
//        }
    }
}
