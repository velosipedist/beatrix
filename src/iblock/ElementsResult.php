<?php
namespace beatrix\iblock;
/**
 * @mixin \CIBlockResult
 */
class ElementsResult{
	private $_result;

	public function __construct($result) {
		$this->_result = $result;
	}

	function __call($name, $arguments) {
		return call_user_func_array(array($this->_result, $name), $arguments);
	}

	public static function __callStatic($name, $arguments) {
		return call_user_func_array(array('\CIBlockResult', $name), $arguments);
	}

	function __get($name) {
		return $this->_result->{$name};
	}

	function __set($name, $value) {
		$this->_result->{$name} = $value;
	}
}
 