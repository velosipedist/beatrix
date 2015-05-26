<?php
function slim_url($name, $params = array(), $queryParams = array())
{
    return Beatrix::app()->urlFor($name, $params, $queryParams);
}


/**
 * Render {@link http://laravel.com/docs/templates Blade} template located in /inc/blade
 *
 * @param string $view
 * @param array $data
 * @param bool $return Whether to return rendering result
 * @internal param array $mergeData
 */
function view($view, $data = array(), $return = false)
{
    $view = Beatrix::view()->render($view, $data);
    if ($return) {
        return $view;
    } else {
        print $view;
    }
}

function is_ajax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}

function dateRange($from, $to, $sep = ' ')
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
    $data = CFile::ResizeImageGet($fileId, $arSize, $method);
    return $data['src'];
}

function crop($fileId, $size, $height = null, $pattern = '{dirname}/{basename}_crop_{size}.{extension}')
{
    //todo pattern can be callback
    \Intervention\Image\ImageManagerStatic::configure(array(
        'driver' => extension_loaded('imagick') ? 'imagick' : 'gd'
    ));
    $webPath = CFile::GetPath($fileId);
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
function getNavPath()
{
    return '/' . trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/') . '/';
}

/**
 * Gets current or passed path, exploded to array
 * @param boolean $path
 * @return array Path parts as ['/root/', '/root/child/', '/root/child/current/']
 */
function getPathChain($path = '')
{
    $path = $path ? $path : getNavPath();
    $parts = explode('/', trim($path, '/'));
    $ret = array();
    foreach ($parts as $i => $part) {
        $piece = array_slice($parts, 0, $i + 1);
        $ret[] = '/' . implode('/', $piece) . '/';
    }
    return $ret;
}

/**
 * Breadcrumbs generated as `[['LINK'=>'/path/', 'TITLE'=>'MenuTitle'], ...]`
 * @param bool $path
 * @return array
 */
function getNavChain($path = false)
{
    /** @var CMain $app */
    $app = $GLOBALS['APPLICATION'];
    $chainTemplatePath = CSite::GetSiteDocRoot(SITE_ID) . BX_PERSONAL_ROOT . '/templates/.default/chain_template.php';
    if (!file_exists(BX_PERSONAL_ROOT . "/templates/.default/chain_template.php")) {
        file_put_contents($chainTemplatePath, '<? return $arResult;');
    }
    if ($path) {
        $chainIndex = $path;
        if (!$app->navChains[$chainIndex]) {
            $app->navChains[$chainIndex] = $app->GetNavChain($path, false, false, true);
        }
        return $app->navChains[$chainIndex];
    } else {
        return $app->GetNavChain(false, 0, false, true);
    }
}