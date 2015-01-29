<?php
$app = new \Symfony\Component\Console\Application('Beatrix console', '1.0');
$app->addCommands(array(
	new \beatrix\commands\PageCommand('page'),
));
$app->run();