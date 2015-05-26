<?php
namespace beatrix\helpers;

use RecursiveArrayIterator;

/**
 * Recursive tree array iterator, for nested structures like menus.
 */
class TreeIterator extends \RecursiveArrayIterator {

    function __construct(array $array, $flags = 0)
    {
        parent::__construct($array, $flags);
    }

    public function hasChildren()
    {
        $current = $this->current();
        return isset($current[TreeBuilder::CHILDREN]) && is_array($current[TreeBuilder::CHILDREN]);
    }

    public function getChildren()
    {
        $current = $this->current();
        return $current[TreeBuilder::CHILDREN];
    }

//    function __toString()
//    {
        // todo: Implement rendering depending of level, child presence etc
//    }


}
