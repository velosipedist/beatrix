<?php
namespace beatrix\tests;
use beatrix\helpers\TreeBuilder;
use beatrix\iblock\IblockSectionsMenu;
use beatrix\tests\mock\CModule;
use beatrix\tests\mock\CIBlock;
use beatrix\tests\mock\CIBlockSection;
use beatrix\tests\mock\CPageOption;
use Mockery;

class MenuTest extends \PHPUnit_Framework_TestCase{
	/** @var IblockSectionsMenu */
	private $menu;

	public function setUp() {
		$ibMockResult = Mockery::mock(array('GetNext'=>array('NAME'=>'Foo', 'ID'=>1)));
		\beatrix\tests\mock\CIBlock::returnList($ibMockResult);
		class_alias(CIBlock::_cl(), 'CIBlock');
        class_alias(CModule::_cl(), 'CModule');

        $ibsMockResult = Mockery::mock(
			array(
				'GetNext'=>array('NAME'=>'FooSection', 'ID'=>1, 'CODE'=>'foo-section'),
				'NavStart'=>null,
				'SelectedRowsCount'=>1,
			)
		);
		\beatrix\tests\mock\CIBlockSection::returnList($ibsMockResult);
		class_alias(CIBlockSection::_cl(), 'CIBlockSection');
		class_alias(CPageOption::_cl(), 'CPageOption');
		$this->menu = new IblockSectionsMenu('foo');
	}
	public function testBuildTree() {
		$items = array(
			array('DEPTH_LEVEL'=> 1, 'NAME'=>'Level1-1',),
			    array('DEPTH_LEVEL'=> 2, 'NAME'=>'Level2-1',),
			array('DEPTH_LEVEL'=> 1, 'NAME'=>'Level1-2',),
                array('DEPTH_LEVEL'=> 2, 'NAME'=>'Level2-2',),
                array('DEPTH_LEVEL'=> 2, 'NAME'=>'Level2-3',),
                    array('DEPTH_LEVEL'=> 3, 'NAME'=>'Level3-1',),
                    array('DEPTH_LEVEL'=> 3, 'NAME'=>'Level3-2',),
                    array('DEPTH_LEVEL'=> 3, 'NAME'=>'Level3-3',),
			array('DEPTH_LEVEL'=> 1, 'NAME'=>'Level1-3',),
		);
		$expect = <<<TREE
|-0 Array
| |-DEPTH_LEVEL 1
| |-NAME Level1-1
| \-#children Array
|   \-0 Array
|     |-DEPTH_LEVEL 2
|     \-NAME Level2-1
|-1 Array
| |-DEPTH_LEVEL 1
| |-NAME Level1-2
| \-#children Array
|   |-0 Array
|   | |-DEPTH_LEVEL 2
|   | \-NAME Level2-2
|   \-1 Array
|     |-DEPTH_LEVEL 2
|     |-NAME Level2-3
|     \-#children Array
|       |-0 Array
|       | |-DEPTH_LEVEL 3
|       | \-NAME Level3-1
|       |-1 Array
|       | |-DEPTH_LEVEL 3
|       | \-NAME Level3-2
|       \-2 Array
|         |-DEPTH_LEVEL 3
|         \-NAME Level3-3
\-2 Array
  |-DEPTH_LEVEL 1
  \-NAME Level1-3
TREE;
		$tree = $this->menu->getTree($items)->getTreeArray();
		$it = new \RecursiveTreeIterator(new \RecursiveArrayIterator($tree),\RecursiveTreeIterator::BYPASS_CURRENT, null);
		$treeDebug = '';
		foreach ($it as $k=>$line) {
			$line = "$k {$line}".PHP_EOL;
			$treeDebug .= $line;
			print $line;
		}
		$this->assertEquals($expect, rtrim($treeDebug), 'Tree must be built correctly');

	}

    public function testBuildTreeWithCallback()
    {
        $items = array(
            array('DEPTH_LEVEL'=> 1, 'NAME'=>'Level1-1',),
            array('DEPTH_LEVEL'=> 1, 'NAME'=>'Level1-2',),
                array('DEPTH_LEVEL'=> 2, 'NAME'=>'Level2-1',),
            array('DEPTH_LEVEL'=> 1, 'NAME'=>'Level1-3',),
        );
        $builder = new TreeBuilder($items, 'DEPTH_LEVEL', '#children', function($item){
            $item['#children'][] = 'foo';
            return $item;
        });
        foreach ($builder->getTreeArray() as $item) {
            $this->assertArrayHasKey('#children', $item, 'Item not modified');
            $this->assertNotFalse(array_search('foo', $item['#children']), 'Item not modified');
        }
    }

	public function tearDown() {
		Mockery::close();
	}
}
