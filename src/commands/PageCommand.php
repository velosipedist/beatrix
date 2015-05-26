<?php
namespace beatrix\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class PageCommand extends Command
{
    protected function configure()
    {
        $this->setName('page')->setDescription('Creates site page');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // defining directories and url
        $curDir = getcwd();
        $docRoot = $this->detectWebRoot();

        /** @var DialogHelper $dialog */
        $dialog = $this->getHelper('dialog');
        $dirname = $dialog->askAndValidate($output, "Enter dir name: ", function ($name) use ($curDir) {
            $name = trim($name);
            if (empty($name)) {
                throw new \InvalidArgumentException("Empty input");
            }
            if (file_exists($curDir . '/' . $name)) {
                throw new \InvalidArgumentException("File {$curDir}/{$name} already exist");
            }
            return $name;
        });

        $title = $dialog->ask($output, "Enter page title: ", "New page");

        $titleEscaped = addslashes($title);

        $targetDir = $curDir . "/" . $dirname;
        $targetFile = $targetDir . "/index.php";

        $url = '/' . trim($dirname, '/\\') . '/';

        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        if (file_exists($targetFile)) {
            $overwrite = $dialog->ask($output, "file [$targetFile] already exists. Overwrite? [y/n] ");
            if (!strcasecmp($overwrite, "y")) {
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
        $sectionContents = '<?php $sSectionName="' . $titleEscaped . '"; ?>';
        $sectionWritten = file_put_contents($targetDir . '/.section.php', $sectionContents);
        if ($indexWritten === false) {
            $output->writeln("<error>Failed writing index file to [$targetFile]</error>");
        }

        $createMenu = $dialog->ask($output, "Add to menu? [y|n]:", 'n');
        if ($createMenu != 'y') {
            exit("Menu won't be created");
        }
        $menuDir = realpath($targetDir . '/..');

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
        $prompt = "Select menu type to add\n";
        foreach ($menuList as $m => $menu) {
            $existMarker = file_exists($menuDir . '/' . $menu) ? '' : ' +';
            $prompt .= "[$m] $menu $existMarker\n";
        }
        $itemMax = count($menuList) - 1;
        $prompt .= "Enter item number [0-" . $itemMax . "] (0): ";
        $menuNum = $dialog->ask($output, $prompt, 0);
        $menuNum = min(abs((int)$menuNum), $itemMax);
        $menuName = $menuList[$menuNum];

        // build menu
        $menuFile = $menuDir . '/' . $menuName;

        $newMenuItem = array(
            $titleEscaped,
            $url,
            array(),
            array(),
            ""
        );
        if (file_exists($menuFile)) {
            require $menuFile;
            $itemExists = false;
            /** @var array $aMenuLinks */
            foreach ($aMenuLinks as $it => $item) {
                if (rtrim($item[1], '/\\') == rtrim($url, '/\\')) {
                    $itemExists = $it;
                    break;
                }
            }
            if ($itemExists === false) {
                $aMenuLinks[] = $newMenuItem;
            } else {
                $aMenuLinks[$itemExists][0] = $titleEscaped;
                $aMenuLinks[$itemExists][1] = $url;
            }
        } else {
            $aMenuLinks = array($newMenuItem);
        }

        $menuCode = "";
        foreach ($aMenuLinks as $menuLink) {
            $menuCode .= "Array(
	        \"{$menuLink[0]}\",
	        \"{$menuLink[1]}\",
	        Array(),
	        Array(),
	        \"\"
	    ),\n";
        }

        $menuContents = <<<PHP
<?php
	\$aMenuLinks = Array(
		$menuCode
	);
PHP;

        $menuWritten = file_put_contents($menuFile, $menuContents);
        if ($menuWritten === false) {
            $output->writeln("<error>Failed to write menu file [$menuFile]</error>");
        }
    }

    private function detectWebRoot()
    {
        $cwd = realpath(__DIR__ . '/../..');
        while (file_exists($cwd . '/index.php')) {
            $cwd = realpath($cwd . '/..');
        }
        return $cwd;
    }
}