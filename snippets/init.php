<?
// bitrix/php_interface/init.php
require_once '/downloaded/beatrix/autoload.php';
beatrix\init(array(
    'templates.path' => $_SERVER['DOCUMENT_ROOT'] . '/.tpl' // where to look for templates
));
//Beatrix::view()->addFolder('email', '<somewhere else folder>');