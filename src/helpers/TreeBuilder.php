<?php
namespace beatrix\helpers;

/**
 * Transforms plain menu items with depth values into nested array.
 * Also can create recursive tree iterator.
 */
class TreeBuilder
{

    private $depthKey;
    private $childrenKey;
    private $tree;
    /**
     * @var callable
     */
    private $itemBuiltHook;

    function __construct($plainList, $depthKey, $childrenKey = '#children', $itemBuiltHook = null)
    {
        $this->depthKey = $depthKey;
        $this->childrenKey = $childrenKey;
        $list = array();
        foreach ($plainList as $k => $val) {
            if (is_int($k)) {
                $list[$k] = $val;
            }
        }
        $this->itemBuiltHook = $itemBuiltHook;
        $this->tree = $this->buildTreeFromItems($list, 1);
    }

    private function buildTreeFromItems($items, $level, &$lastAddedItemIndex = null)
    {
        $result = array();
        foreach ($items as $i => $nextItem) {
            $nextItemLevel = $nextItem[$this->depthKey];
            if ($nextItemLevel == $level) {
                $result[] = $nextItem;
            } elseif ($nextItemLevel == ($level + 1)) {
                end($result);
                $lastResultKey = key($result);
                if (isset($result[$lastResultKey][$this->childrenKey])) {
                    continue;
                }
                $itemsDeeper = array();
                foreach (array_slice($items, $i) as $d => $subItem) {
                    $subItemLevel = $subItem[$this->depthKey];
                    if ($subItemLevel < $nextItemLevel) {
                        break;
                    }
                    $itemsDeeper[$d] = $subItem;
                }

                $childTree = $this->buildTreeFromItems(
                    $itemsDeeper,
                    $level + 1,
                    $lastAddedItemIndex
                );
                $hasSelectedChild = false;
                foreach ($childTree as $child) {
                    if ($child['SELECTED']) {
                        $hasSelectedChild = true;
                        break;
                    }
                }
                //todo make special keys prefixed with #
                $result[$lastResultKey]['HAS_SELECTED_CHILD'] = $hasSelectedChild;
                $result[$lastResultKey][$this->childrenKey] = $childTree;
            }
        }
        if ($this->itemBuiltHook) {
            foreach ($result as &$item) {
                $item = call_user_func_array($this->itemBuiltHook, [$item]);
            }
        }

        return $result;
    }

    /**
     * Tree of array items, with sub-items under [#childrenKey] each
     * @return array
     */
    public function getTreeArray()
    {
        return $this->tree;
    }

    /**
     * Iterate for tree rendering, beginning from root by default.
     * @param int $mode
     * @param int $flags
     * @return \RecursiveTreeIterator
     */
    public function getTreeIterator($mode = null, $flags = 0)
    {
        if (is_null($mode)) {
            $mode = \RecursiveIteratorIterator::SELF_FIRST;
        }
        return new \RecursiveTreeIterator(
            new TreeIterator($this->tree, $this->childrenKey),
            $mode,
            $flags
        );
    }
}
