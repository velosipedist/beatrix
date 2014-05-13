<?php
namespace beatrix;
use Countable;
use Iterator;

class DbResultIterator implements Iterator, Countable {
	/** @var \CDBResult */
	private $result;
	/** @var array */
	private $current;
	/** @var bool */
	private $textHtmlAuto;
	/** @var bool */
	private $useTilda;
	private $key;

	public function __construct($result, $pageSize = 20, $textHtmlAuto = true, $useTilda = true) {
		$this->result = $result;
		$this->result->NavStart($pageSize);
		$this->textHtmlAuto = $textHtmlAuto;
		$this->useTilda = $useTilda;
		$this->key = 0;
		$this->fetchCurrent();
	}

	public static function from($result) {
		return new static($result);
	}

	public function current() {
		return $this->current;
	}

	public function next() {
		$this->key++;
		$this->fetchCurrent();
	}

	public function key() {
		return $this->key;
	}

	public function valid() {
		return ($this->key+1) < $this->count();
	}

	public function rewind() {
//		if(!is_null($this->current)){
//			throw new \BadMethodCallException("Cannot rewind forward-only result");
//		}
	}

	public function count() {
		return $this->result->SelectedRowsCount();
	}

	public function pagination($template="bootstrap") {
		//todo implement bootstrap
		return $this->result->NavPageCount;
	}

	private function fetchCurrent() {
		$this->current = $this->result->GetNext($this->textHtmlAuto, $this->useTilda);
	}

	public function toArray() {
		if($this->key == 0){
			return iterator_to_array($this);
		} else {
			throw new \BadMethodCallException("Data already fetched");
		}
	}
}
 