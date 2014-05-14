<?php
namespace beatrix\tests\mock;
class IBlockSection {

	/** @var array */
	private static $returnResult;

	public static function returnList($returnResult){
		self::$returnResult = $returnResult;
		return __CLASS__;
	}

	public static function GetList() {
		return self::$returnResult;
	}
	public static function GetTreeList() {
		return self::$returnResult;
	}
}
 