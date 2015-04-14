<?php
namespace beatrix\tests\mock;
class BaseMock {
    public static function _cl()
    {
        return get_called_class();
	}
}
