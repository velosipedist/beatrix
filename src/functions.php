<?php
namespace beatrix;

use Illuminate\View\View;
use Slim\Route;

define('VENDORS_LITE', __DIR__ . '/../vendor-lite/');

/**
 * Must be invoked at app start, i.e. in init.php, before the rendering occurs
 *
 * @param array $settings
 *
 * @return Application
 * @see Slim::__construct()
 */
function init($settings = array())
{
    if (defined('ADMIN_SECTION') && ADMIN_SECTION) {
        // if admin section - separate process
        return;
    }
    new Application($settings);
}

/**
 * @param string $name
 * @param array  $aReplace
 *
 * @return string
 */
function _($name, $aReplace = null)
{
    return \GetMessage($name, $aReplace);
}

/**
 * @return Application
 * @throws \BadMethodCallException
 */
function app()
{
    $app = Application::getInstance();
    if (!$app) {
        throw new \BadMethodCallException("Setup Beatrix first, using beatrix\\init()");
    }
    return $app;
}

/**
 * @return Route
 */
function get()
{
    return call_user_func_array([app(), 'get'], func_get_args());
}

/**
 * @return Route
 */
function post()
{
    return call_user_func_array([app(), 'post'], func_get_args());
}

/**
 * @return void
 */
function group()
{
    call_user_func_array([app(), 'group'], func_get_args());
}

/**
 * @param string $name        Slim route name
 * @param array  $params      Slim route params
 * @param array  $queryParams rest of params to be appended after ?
 *
 * @return string
 */
function url($name, $params = array(), $queryParams = array())
{
    return app()->urlFor($name, $params, $queryParams);
}


/**
 * Render {@link http://laravel.com/docs/templates Blade} template located in /inc/blade
 *
 * @param string $view
 * @param array  $data   variables to be passed
 * @param bool   $return Whether to return rendering result or print immediately
 *
 * @return View
 */
function render($view, $data = array(), $return = false)
{
    $view = blade()->make($view, $data);
    if ($return) {
        return $view;
    } else {
        print $view;
    }
}

function is_ajax()
{
    return app()->request->isAjax();
}

function date_range($from, $to, $sep = ' ')
{
    $ret = $from;
    if (!$from && $to) {
        $ret = 'до ' . $to;
    } else {
        if ($to && ($from != $to)) {
            $ret = $from . $sep . $to;
        }
    }
    return $ret;
}

function thumb($fileId, $width, $height = null, $method = BX_RESIZE_IMAGE_PROPORTIONAL)
{
    $height = $height ? $height : $width;
    $arSize = compact('width', 'height');
    $data = \CFile::ResizeImageGet($fileId, $arSize, $method);
    return $data['src'];
}

function crop($fileId, $size, $height = null, $pattern = '{dirname}/{basename}_crop_{size}.{extension}')
{
    //todo pattern can be callback
    \Intervention\Image\ImageManagerStatic::configure(array(
        'driver' => extension_loaded('imagick') ? 'imagick' : 'gd'
    ));
    $webPath = \CFile::GetPath($fileId);
    $webParts = pathinfo($webPath);
    $thumbUrl = strtr($pattern, array(
        '{dirname}' => $webParts['dirname'],
        '{basename}' => $webParts['filename'],
        '{size}' => (int)$size . 'x' . (!is_null($height) ? (int)$height : (int)$size),
        '{extension}' => $webParts['extension'],
    ));
    $thumbFile = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . $thumbUrl;
    if (!file_exists($thumbFile)) {
        $imagePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . $webPath;
        \Intervention\Image\ImageManagerStatic::make($imagePath)
            ->fit($size, $height)
            ->save($thumbFile);
    }
    return $thumbUrl;
}


/**
 * Real URL path
 * @return mixed
 */
function current_path()
{
    return '/' . trim((string)\beatrix\helpers\NavigationHelper::currentUrl()->getPath(), '/') . '/';
}


/**
 * Breadcrumbs generated as `[['LINK'=>'/path/', 'TITLE'=>'MenuTitle'], ...]`
 * @param bool $path
 * @return array
 */
function breadcrumbs($path = false)
{
    static $chainsCache;
    if (is_null($chainsCache)) {
        $chainsCache = [];
    }
    /** @var \CMain $app */
    $app = $GLOBALS['APPLICATION'];
    $chainTemplatePath = \CSite::GetSiteDocRoot(SITE_ID) . BX_PERSONAL_ROOT . '/templates/.default/chain_template.php';
    if (!file_exists($chainTemplatePath)) {
        file_put_contents($chainTemplatePath, '<? return $arResult;');
    }
    if ($path) {
        if (!isset($chainsCache[$path])) {
            $chainsCache[$path] = $app->GetNavChain($path, false, false, true);
        }
        return $chainsCache[$path];
    } else {
        return $app->GetNavChain(false, 0, false, true);
    }
}

/**
 * Call it once anywhere at bitrix/templates/your-template-id/header.php
 */
function template_header()
{
    ob_start();
}

/**
 * Call it once at end of bitrix/templates/your-template-id/footer.php
 */
function template_footer()
{
    if (is_ajax()) {
        print ob_get_clean();
    } else {
        print app()->layout->with('content', ob_get_clean());
    }
}

function widgets()
{
    return app()->widgets;
}

function blade()
{
    return app()->view->getBladeEngine();
}

function is_admin()
{
    return isset($GLOBALS['USER']) && $GLOBALS['USER']->IsAdmin();
}