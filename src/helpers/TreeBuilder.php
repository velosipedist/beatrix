<?php
namespace beatrix\helpers;
class TreeBuilder {

	private $depthKey;
	private $childrenKey;
	private $tree;

	function __construct($plainList, $depthKey, $childrenKey = '__children') {
		$this->depthKey = $depthKey;
		$this->childrenKey = $childrenKey;
		$this->tree = $this->build($plainList);
	}

	function __invoke() {
		return $this->getTree();
	}


	private function build($items) {
		$itemSet = array();
		$currentDepth = current($items)[$this->depthKey];
		$skip = 0;
		foreach ($items as $i => $nextItem) {
			$itemDepth = $nextItem[$this->depthKey];
			if (($itemDepth > $currentDepth) && ($skip > 0)) {
				$skip--;
				continue;
			}
			if ($itemDepth < $currentDepth) {
				break;
			} elseif ($itemDepth > $currentDepth) {
				$children = $this->build(array_slice($items, $i));
				end($itemSet);
				$prevItemIndex = key($itemSet);
				$itemSet[$prevItemIndex][$this->childrenKey] = $children;
				$skip = count($children);
			} else {
				$itemSet[] = $nextItem;
				$skip = 0;
			}
		}
		return $itemSet;
	}

	/**
	 * @return array
	 */
	public function getTree() {
		return $this->tree;
	}

}
 