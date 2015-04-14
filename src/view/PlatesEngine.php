<?php
namespace beatrix\view;
use League\Plates\Engine;

/**
 * Plates Engine wrapper.
 */
class PlatesEngine extends Engine{
    public function make($name)
    {
        return new PlateTemplate($this, $name);
    }
}
