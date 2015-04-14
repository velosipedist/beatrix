<?php
namespace beatrix\iblock;
/**
 * CIBlockResult decorator to extend it before iteration.
 * @mixin \CIBlockResult
 */
class ElementsResult{
    /**
     * @var \CIBlockResult
     */
	private $_result;
    /**
     * @var Query
     */
    private $query;

	public function __construct($result, Query $query) {
		$this->_result = $result;
        $this->query = $query;
    }

	function __call($name, $arguments) {
		return call_user_func_array(array($this->_result, $name), $arguments);
	}

	public static function __callStatic($name, $arguments) {
        //todo test? drop?
		return call_user_func_array(array('\CIBlockResult', $name), $arguments);
	}

	function __get($name) {
		return $this->_result->{$name};
	}

	function __set($name, $value) {
		$this->_result->{$name} = $value;
	}

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }
}
 