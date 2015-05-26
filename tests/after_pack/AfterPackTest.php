<?php
namespace velosipedist\SculptorClient\tests\after_pack;


class AfterPackTest extends \PHPUnit_Framework_TestCase
{

    public function testPharIncludable()
    {
        require_once __DIR__ . '/../../build/beatrix.phar';
        $this->assertTrue(class_exists('\beatrix\Application'));
    }
}
