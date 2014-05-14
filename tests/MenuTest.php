<?php
namespace beatrix\tests;
use beatrix\iblock\Menu;
use Mockery;

class MenuTest extends \PHPUnit_Framework_TestCase{
	/** @var Menu */
	private $menu;

	public function setUp() {
		$ibMockResult = Mockery::mock(array('GetNext'=>array('NAME'=>'Foo', 'ID'=>1)));
		$ibMockClass = \beatrix\tests\mock\IBlock::returnList($ibMockResult);
		class_alias($ibMockClass, 'CIBlock');

		$ibsMockResult = Mockery::mock(
			array(
				'GetNext'=>array('NAME'=>'FooSection', 'ID'=>1, 'CODE'=>'foo-section'),
				'NavStart'=>null,
				'SelectedRowsCount'=>1,
			)
		);
		$ibsMockClass = \beatrix\tests\mock\IBlockSection::returnList($ibsMockResult);
		class_alias($ibsMockClass, 'CIBlockSection');
		$this->menu = new Menu('foo');
	}
	public function testBuildTree() {
		$items = array(
			array('DEPTH_LEVEL'=>1, 'NAME'=>'Level1-1',),
			array('DEPTH_LEVEL'=>2, 'NAME'=>'Level2-1',),
			array('DEPTH_LEVEL'=>1, 'NAME'=>'Level1-2',),
			array('DEPTH_LEVEL'=>2, 'NAME'=>'Level2-2',),
			array('DEPTH_LEVEL'=>2, 'NAME'=>'Level2-3',),
			array('DEPTH_LEVEL'=>3, 'NAME'=>'Level3-1',),
			array('DEPTH_LEVEL'=>1, 'NAME'=>'Level1-3',),
		);
		$expect = <<<TREE
|-0 Array
| |-DEPTH_LEVEL 1
| |-NAME Level1-1
| \-__children Array
|   \-0 Array
|     |-DEPTH_LEVEL 2
|     \-NAME Level2-1
|-1 Array
| |-DEPTH_LEVEL 1
| |-NAME Level1-2
| \-__children Array
|   |-0 Array
|   | |-DEPTH_LEVEL 2
|   | \-NAME Level2-2
|   \-1 Array
|     |-DEPTH_LEVEL 2
|     |-NAME Level2-3
|     \-__children Array
|       \-0 Array
|         |-DEPTH_LEVEL 3
|         \-NAME Level3-1
\-2 Array
  |-DEPTH_LEVEL 1
  \-NAME Level1-3
TREE;
		$expect = str_replace("\r\n", "\n", $expect);
		$tree = $this->menu->getTree($items);
		$it = new \RecursiveTreeIterator(new \RecursiveArrayIterator($tree),\RecursiveTreeIterator::BYPASS_CURRENT, null);
		$treeDebug = '';
		foreach ($it as $k=>$line) {
			$line = "$k {$line}\n";
			$treeDebug .= $line;
			print $line;
		}
		$this->assertEquals($expect, rtrim($treeDebug), 'Tree must be built correctly');

	}

	public function tearDown() {
		Mockery::close();
	}
}
 