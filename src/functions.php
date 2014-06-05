<?php
function slimUrl($name, $params = array(), $queryParams = array()) {
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
function blade($view, $data = array(), $return = false) {
	$view = Beatrix::app()->view->blade($view, $data);
	if ($return) {
		return $view;
	} else {
		print $view;
	}
}

function is_ajax(){
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}