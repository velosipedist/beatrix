<?
// bitrix/php_interface/init.php
require_once '/downloaded/beatrix.phar';
Beatrix::init(array(
	Beatrix::SETTINGS_TEMPLATES_DIR => $_SERVER['DOCUMENT_ROOT'].'/.tpl' // where to look for templates
));
//Beatrix::view()->addFolder('email', '<somewhere else folder>');