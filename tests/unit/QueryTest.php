<?php
namespace beatrix\tests\unit;

use beatrix\iblock\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testExtractFilterPrefix()
    {
        $cases = array(
            array('PROP_name', '', 'PROP_name'),
            array('_PROP_name', '', '_PROP_name'),
            array('!PROP_name', '!', 'PROP_name'),
            array('!_PROP_name', '!', '_PROP_name'),
            array('!><PROP_name', '!><', 'PROP_name'),
            array('!><_PROP_name', '!><', '_PROP_name'),
            array('><PROP_name', '><', 'PROP_name'),
            array('><_PROP_name', '><', '_PROP_name'),
            array('<=PROP_name', '<=', 'PROP_name'),
            array('<=_PROP_name', '<=', '_PROP_name'),
        );
        foreach ($cases as $case) {
            list($p, $code) = Query::extractFilterPrefix($case[0]);
            $this->assertEquals($p, $case[1], 'Wrong prefix in ' . $case[0]);
            $this->assertEquals($code, $case[2], 'Wrong code in ' . $case[0]);
        }
    }
}
