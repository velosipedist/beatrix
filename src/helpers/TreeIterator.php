<?php
namespace beatrix\helpers;

use RecursiveArrayIterator;

/**
 * Recursive tree array iterator, for nested structures like menus.
 */
class TreeIterator extends \RecursiveArrayIterator {
    /**
     * @var string
     */
    private $childrenKey;

    function __construct(array $array, $childrenKey = '#children', $flags = 0)
    {
        parent::__construct($array, $flags);
        $this->childrenKey = $childrenKey;
    }

    public function hasChildren()
    {
        $current = $this->current();
        return isset($current[$this->childrenKey]) && is_array($current[$this->childrenKey]);
    }

    public function getChildren()
    {
        $current = $this->current();
        return $current[$this->childrenKey];
    }

//    function __toString()
//    {
        // todo: Implement rendering depending of level, child presence etc
//    }


}
