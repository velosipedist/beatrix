<?php
use beatrix\Application;
use beatrix\view\PlatesEngine;
use beatrix\view\PlateTemplate;

/**
 * Helper facade for main beatrix routines:
 * - starting & configuring Slim instance
 * - accessing to global Plates layout (not necessary for use on any page)
 * - accessing to templating engine
 */
class Beatrix
{
    const SETTINGS_TEMPLATES_DIR = 'templates.path';
    const SETTINGS_ROUTES = 'beatrix.routes';

    /**
     * @var PlateTemplate
     */
    private static $layout;

    /**
     * Must be invoked at app start, i.e. in init.php, before the rendering occurs
     * @param array $settings
     * @return Application
     * @see Slim::__construct()
     */
    public static function init($settings = array())
    {
        return new Application($settings);
    }

    /**
     * @return Application Slim application instance preconfigured with Beatrix::init()
     */
    public static function app()
    {
        $app = Application::getInstance() or new Application();
        if (!$app) {
            $app = new Application();
        }
        return $app;
    }

    /**
     * Plates engine, allows manipulate directories, rendering views and partials
     * @return PlatesEngine
     */
    public static function view()
    {
        return static::app()->view->getPlates();
    }

    /**
     * Main template view used in main Bitrix template to render captured page.
     * Available from any page or bitrix template.
     * @return PlateTemplate
     */
    public static function layout()
    {
        if (!static::$layout) {
            static::$layout = static::view()->make('beatrix::layout/empty');
            if (!static::app()->request->isAjax()) {
                static::$layout->setLayout('layout/' . SITE_TEMPLATE_ID);
            }
        }
        return static::$layout;
    }

    /**
     * Call it once anywhere at bitrix/templates/your-template-id/header.php
     */
    public static function templateHeader()
    {
        ob_start();
    }

    /**
     * Call it once at end of bitrix/templates/your-template-id/footer.php
     */
    public static function templateFooter()
    {
        $buffered = ob_get_clean();
        print static::layout()->setSection('content', $buffered)
            ->render();
    }
}
