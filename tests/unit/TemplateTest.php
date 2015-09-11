<?php
namespace beatrix\tests\unit;

use beatrix\view\PlateTemplate;
use League\Plates\Engine;

class TemplateTest extends \PHPUnit_Framework_TestCase
{
    private $engine;

    public function setUp()
    {
        $this->engine = new Engine(__DIR__ . '/tpl');
    }

    private function captureStart()
    {
        ob_start();
    }

    private function captureEnd()
    {
        return ob_get_clean();
    }

    public function testIsAnySection()
    {
        $this->captureStart();
        $template = new PlateTemplate($this->engine, 'layout');

        $this->assertFalse($template->hasAnySection('left.*'));

        $template->setSection('left.foo', '123');
        $this->assertTrue($template->hasAnySection('left.*'));

        $template->setSection('left.foo', '');
        $this->assertFalse($template->hasAnySection('left.*'));

        $output = $this->captureEnd();
        $this->assertEquals('', $output);
    }
}
