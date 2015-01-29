<?php
namespace beatrix\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

function prompt($text) {
	fwrite(STDOUT, $text);
	return trim(fgets(STDIN));
}

function stripPrefix($str, $prefix) {
	return substr($str, strlen($prefix));
}

class PageCommand extends Command
{
	protected function execute(InputInterface $input, OutputInterface $output) {
		// defining directories and url
		$curDir = getcwd();
		if (empty($argv[1]))
			exit('No directory specified!');
		$docRoot = Helpers::detectWebRoot();

		$startDir = strtr(rtrim($argv[1], '/\\'), '\\', '/');

		/** @var DialogHelper $dialog */
		$dialog = $this->getHelper('dialog');
		$dirname = $dialog->askAndValidate($output, "Enter dir name: ", function($name) use ($curDir){
			$name = trim($name);
			if(empty($name)) {
				throw new \InvalidArgumentException("Empty input");
			}
			if(file_exists($curDir.'/'.$name)){
				throw new \InvalidArgumentException("File {$curDir}/{$name} already exist");
			}
			return $name;
		});

		$title = $dialog->ask($output, "Enter page title: ", "New page");

		$titleEscaped = addslashes($title);

		$targetDir = $startDir . "/" . $dirname;
		$targetFile = $targetDir . "/index.php";

		$url = stripPrefix($targetDir, $docRoot);

		if (!file_exists($targetDir)) {
			mkdir($targetDir, 0777, true);
		}
		if (file_exists($targetFile)) {
			$overwrite = strtolower(
				prompt("file [$targetFile] already exists. Overwrite? [y/n] ")
			);
			if ($overwrite != "y") {
				exit("Operation cancelled");
			}
		}

		// save index page
		$indexContents = <<<PHP
	<?php
	require(\$_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
	\$APPLICATION->SetTitle("{$titleEscaped}");?>
	<p>Страница {$title}</p>
	<?php require(\$_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
PHP;

		$indexWritten = file_put_contents($targetFile, $indexContents);
		$sectionWritten = file_put_contents($targetDir . '/.section.php', '<?php $sSectionName="' . $titleEscaped . '"; ?>');
		if ($indexWritten === false)
			exit("Error writing index file to [$targetFile]");

		$createMenu = prompt("Add to menu? [y|n] ");
		if ($createMenu != 'y') {
			exit("Menu won't be created");
		}
		$menuDir = $startDir;

		// search existing or use default menus
		$menuList = array(
			'.top.menu.php',
			'.left.menu.php',
		);
		$menus = glob($menuDir . '/.*.menu.php');
		if (count($menus)) {
			foreach ($menus as $menu) {
				$menuList[] = basename($menu);
			}
			$menuList = array_unique($menuList);
		}

		// select menu file
		print "Select menu type to add\n";
		foreach ($menuList as $m => $menu) {
			$existMarker = file_exists($menuDir . '/' . $menu) ? '' : ' +';
			print "[$m] $menu $existMarker\n";
		}
		$itemMax = count($menuList) - 1;
		$menuNum = (int)prompt("Enter item number [0-" . $itemMax . "]: ");
		$menuNum = min($menuNum, $itemMax);
		$menuName = $menuList[$menuNum];

		// build menu

		$menuFile = $menuDir . '/' . $menuName;

		$newMenuItem = array(
			$titleEscaped,
			$url . '/',
			Array(),
			Array(),
			""
		);
		if (file_exists($menuFile)) {
			require $menuFile;
			$itemExists = false;
			foreach ($aMenuLinks as $it => $item) {
				if (rtrim($item[1], '/\\') == $url) {
					$itemExists = $it;
					break;
				}
			}
			if ($itemExists === false) {
				$aMenuLinks[] = $newMenuItem;
			} else {
				$aMenuLinks[$itemExists][0] = $titleEscaped;
				$aMenuLinks[$itemExists][1] = $url . '/';
			}
		} else {
			$aMenuLinks = array(
				$newMenuItem
			);
		}

		//$menuCode = var_export($aMenuLinks, true);
		$menuCode = "";
		foreach ($aMenuLinks as $menuLink) {
			$menuCode .= "
	    Array(
	        \"{$menuLink[0]}\",
	        \"{$menuLink[1]}\",
	        Array(),
	        Array(),
	        \"\"
	    ),";
		}

		$menuContents = <<<PHP
	<?php
	\$aMenuLinks = Array($menuCode
	);
PHP;

		file_put_contents($menuFile, $menuContents);
	}

}

class Helpers
{
	public static function detectWebRoot() {
		$cwd = realpath(__DIR__ . '/../..');
		while (file_exists($cwd . '/index.php')) {
			$cwd = realpath($cwd . '/..');
		}
		return $cwd;
	}
}