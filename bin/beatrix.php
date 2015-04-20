<?php
require_once __DIR__ .'/../vendor/autoload.php';
if(!in_array(PHP_SAPI, array('cli','cgi-fcgi'))){
    return;
}
$app = new \Symfony\Component\Console\Application('Beatrix console', '1.0');
$app->addCommands(array(
	new \beatrix\commands\PageCommand('page'),
));
$app->run();
