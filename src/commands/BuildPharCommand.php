<?php
namespace beatrix\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildPharCommand extends Command
{
    protected function configure()
    {
        $this->setName('build-phar')
            ->setDescription('Build phar package for distribution');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $workingDir = realpath(__DIR__ .'/../..');
        $brg = new \Burgomaster($workingDir . '/build/phar', $workingDir);
        $dirs = [
            'vendor/composer',
            'vendor/illuminate/support',
            'vendor/league/plates/src',
            'vendor/league/url/src',
            'vendor/slim/slim/Slim',
            'vendor/true/punycode/src',
            'vendor/siriusphp/validation/src',
            'vendor/suin/php-rss-writer/Source',
            'vendor/intervention/image/src',
            'src',
            'templates',
        ];
        $files = [
            'vendor/symfony/console/Symfony/Component/Console/Application.php',
            'vendor/symfony/console/Symfony/Component/Console/ConsoleEvents.php',
            'vendor/symfony/console/Symfony/Component/Console/Shell.php',
            'vendor/symfony/finder/Symfony/Component/Finder/Finder.php',
            'vendor/symfony/finder/Symfony/Component/Finder/Glob.php',
            'vendor/symfony/finder/Symfony/Component/Finder/SplFileInfo.php',
            'vendor/siriusphp/validation/autoload.php',
            'vendor/hamcrest/hamcrest-php/hamcrest/Hamcrest.php',
            'bin/beatrix.php',
            'src/Beatrix.php',
            'src/functions.php',
            'vendor/autoload.php',
            'vendor/mtdowling/burgomaster/src/Burgomaster.php',
        ];

        foreach ($dirs as $dir) {
            $brg->recursiveCopy($dir, $dir);
        }

        foreach ($files as $file) {
            $brg->deepCopy($file, $file);
        }


        //symfony console
        foreach (['Command', 'Descriptor', 'Event', 'Formatter', 'Helper', 'Input', 'Output', 'Resources'] as $subdir) {
            $subdir = 'vendor/symfony/console/Symfony/Component/Console/' . $subdir;
            $brg->recursiveCopy($subdir, $subdir);
        }
        // symfony finder
        foreach (['Adapter', 'Comparator', 'Exception', 'Expression', 'Iterator', 'Shell'] as $subdir) {
            $subdir = 'vendor/symfony/finder/Symfony/Component/Finder/' . $subdir;
            $brg->recursiveCopy($subdir, $subdir);
        }

        // non-PSR sources
        $brg->createPhar('build/beatrix.phar', null, 'bin/beatrix.php');
    }

}
