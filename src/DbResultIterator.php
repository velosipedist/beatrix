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
	/** @var int */
	private $pageSize;
	/** @var string */
	private $pageUrlParam;

	public function __construct($result, $pageSize = 20, $pageUrlParam = 'nav_page', $textHtmlAuto = true, $useTilda = true) {
		\CPageOption::SetOptionString("main", "nav_page_in_session", "N");
		$this->result = $result;
		$this->result->NavStart($pageSize, true, isset($_GET[$pageUrlParam]) ? (int) $_GET[$pageUrlParam] : false);
		$this->textHtmlAuto = $textHtmlAuto;
		$this->useTilda = $useTilda;
		$this->key = 0;
		$this->fetchCurrent();
		$this->pageSize = $pageSize;
		$this->pageUrlParam = $pageUrlParam;
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
		return $this->key < $this->count();
	}

	public function rewind() {
		return null;
	}

	public function count() {
		return $this->result->SelectedRowsCount();
	}

	/**
	 * @param array $variables See `templates/bootstrap/pagination.php` for available vars doc
	 */
	public function pagination(array $variables = array()) {
		if($this->result->NavPageCount < 2) {
			return;
		}
		$url = parse_url($_SERVER['REQUEST_URI']);
		$params = $url['query'] ? parse_str($url['query']) : array();
		//todo parametrize placeholder ?
		$params[$this->pageUrlParam] = '__PAGENUMBER__';

		$urlTemplate = $url['path'].'?'.http_build_query($params);
		$urlStartTemplate = $url['path'];
		$result = $this->result;
		unset($url, $params);

		// get passed options
		extract($variables);

		//then override computed values
		$isPrevArrowActive = ($result->NavPageNomer > 1);
		$isNextArrowActive = ($result->NavPageNomer < $result->NavPageCount);
		$isStartArrowActive = ($result->NavPageNomer != 1);
		$isEndArrowActive = ($result->NavPageNomer != $result->NavPageCount);

		// print pager here
		require __DIR__.'/templates/bootstrap/pagination.php';
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
 