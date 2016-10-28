<?php
namespace beatrix;

use beatrix\exception\UnhanledException;
use beatrix\helpers\NavigationHelper;
use beatrix\view\blade\BladeView;
use beatrix\widgets\WidgetsManager;
use Illuminate\View\View;
use PhpConsole\Helper;
use Slim\Slim;

/**
 * Base micro HTTP application listening certain folders for common modules routing.
 * Also contains required dependencies for preconfigured libs.
 * @property BladeView      $view
 * @property View           $layout
 * @property WidgetsManager $widgets
 */
class Application extends Slim
{
    private $hasRoutes = false;
    private $debug = false;
    /**
     * Log into PhpConsole
     */
    const DEBUG_NORMAL = 1;
    /**
     * To dump and exit immediately
     */
    const DEBUG_AGGRESSIVE = 2;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $userSettings = array())
    {
        $this->setupDebug($userSettings);

        parent::__construct($userSettings);

        // fix trailing slash url parsing issue
        $this->environment['PATH_INFO'] = rtrim($this->environment['PATH_INFO'], '/');

        $this->view->addNamespace('beatrix', __DIR__ . '/../templates');
        $self = $this;
        $this->error(function (\Exception $e) use ($self) {
            $this->debug($e, 'exception.slim');

            $message = $e->getMessage();

            if ($bitrixError = $GLOBALS['APPLICATION']->GetException()) {
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
        $this->container->singleton('widgets', function () {
            return new WidgetsManager();
        });
        $this->container->singleton('layout', function () use ($self) {
            return $self->view
                ->getBladeEngine()
                ->make(app()->request->isAjax() ? 'beatrix::layout/empty' : SITE_TEMPLATE_ID . '_layout');
        });

        \AddEventHandler("main", "OnBeforeProlog", function () {
            // todo setup templates directory under defined template
            $templatesDir = $_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/templates/' . SITE_TEMPLATE_ID . '/.beatrix';
            if (!file_exists($templatesDir)) {
                mkdir($templatesDir);
                file_put_contents($templatesDir . '/.htaccess', 'Deny from all');
            } elseif (!is_dir($templatesDir) || !is_writable($templatesDir)) {
                throw new \RuntimeException("Cannot use templates path: $templatesDir");
            }
            $this->updateSettings(array('templates.path' => $templatesDir));
            $this->view->setTemplatesDirectory($templatesDir);
        });
        \AddEventHandler("main", "OnEpilog", function () {
            if ($this->hasRoutes) {
                $this->run();
            }
        });
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
        $parent['view'] = '\beatrix\view\blade\BladeView';
        return $parent;
    }

    /**
     * @inheritDoc
     */
    protected function mapRoute($args)
    {
        $this->hasRoutes = true;
        return parent::mapRoute($args);
    }

    /**
     * @inheritDoc
     */
    public function group()
    {
        $this->hasRoutes = true;
        parent::group();
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

    private function updateSettings(array $settings)
    {
        $this->container['settings'] = array_merge($this->container->get('settings'), $settings);
    }

    /**
     * @param array $userSettings
     *
     * @throws \Exception
     * @internal param $debug
     */
    private function setupDebug(array &$userSettings)
    {
        if (!is_admin()) {
            return;
        }
        if (isset($userSettings['debug'])) {
            $this->debug = $userSettings['debug'];
            switch ($this->debug) {
                case true:
                case static::DEBUG_NORMAL:
                    if (!class_exists('\PhpConsole\Helper')) {
                        require VENDORS_LITE . 'PhpConsole/__autoload.php';
                    }
                    Helper::register();
                    break;
                case static::DEBUG_AGGRESSIVE:
                    if (!class_exists('\Kint')) {
                        require VENDORS_LITE . 'Kint/Kint.class.php';
                    }
                    break;
            }
            unset($userSettings['debug']);

        }
        if (isset($userSettings['debug_set_error_reporting'])) {
            $level = $userSettings['debug_set_error_reporting'];
            switch ($this->debug) {
                case true:
                case static::DEBUG_NORMAL:
                    $chosenLevel =
                        E_WARNING | E_NOTICE | E_ERROR | E_RECOVERABLE_ERROR | E_COMPILE_ERROR | E_COMPILE_WARNING;
                    break;
                case static::DEBUG_AGGRESSIVE:
                    $chosenLevel = E_ALL;
                    break;
            }
            if (is_int($level)) {
                $chosenLevel = $level;
            }
            //todo make this feature work
            error_reporting($chosenLevel);
            ini_set('display_errors', 1);
            unset($userSettings['debug_set_error_reporting']);
        }

    }

    public function debug($stuff, $tag = '')
    {
        switch ($this->debug) {
            case true:
            case static::DEBUG_NORMAL:
                \PC::debug($stuff, 'beatrix.' . ($tag ?: 'debug'));
                break;
            case static::DEBUG_AGGRESSIVE:
                \Kint::dump($stuff);
                break;
        }
    }
}
