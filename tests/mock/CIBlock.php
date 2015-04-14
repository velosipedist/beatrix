<?php
namespace beatrix\tests\mock;
class CIBlock extends BaseMock{

	/** @var array */
	private static $returnResult;

	public static function returnList($returnResult){
		self::$returnResult = $returnResult;
	}

	public static function GetList() {
		return self::$returnResult;
	}
}
