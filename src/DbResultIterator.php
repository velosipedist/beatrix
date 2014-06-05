<?php
namespace beatrix;
use beatrix\iblock\ElementsResult;
use beatrix\iblock\SectionsResult;
use beatrix\iblock\URL;
use Countable;
use Iterator;

\CModule::IncludeModule('iblock');

class DbResultIterator implements Iterator, Countable {
	/** @var \CDBResult | \CSearch | \CIBlockResult | ElementsResult | SectionsResult */
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
		if (!$this->result->bNavStart) {
			$this->result->NavStart($pageSize, true, isset($_GET[$pageUrlParam]) ? (int)$_GET[$pageUrlParam] : false);
		}
		$this->textHtmlAuto = $textHtmlAuto;
		$this->useTilda = $useTilda;
		$this->key = 0;
		$this->fetchCurrent();
		$this->pageSize = $pageSize;
		$this->pageUrlParam = $pageUrlParam;
	}

	public static function from($result, $pageSize = 20, $pageUrlParam = 'nav_page', $textHtmlAuto = true, $useTilda = true) {
		return new static($result, $pageSize, $pageUrlParam, $textHtmlAuto, $useTilda);
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
		return count($this->result->arResult);
	}
	public function totalCount() {
		return (int) $this->result->SelectedRowsCount();
	}

	/**
	 * @param array $variables See `templates/bootstrap/pagination.php` for available vars doc
	 */
	public function pagination(array $variables = array()) {
		if($this->result->NavPageCount < 2) {
			return;
		}
		//todo parametrize placeholder ?
		$params = array($this->pageUrlParam => '__PAGENUMBER__');
		$queryString = URL::extendQueryParams($params);
		$urlTemplate = $_SERVER['PATH_INFO'] . ($queryString ? '?' . $queryString : '');
		$paramsCurrent = array();
		parse_str($_SERVER['QUERY_STRING'], $paramsCurrent);
		unset($paramsCurrent[$this->pageUrlParam]);
		$urlStartTemplate = $_SERVER['PATH_INFO'].($paramsCurrent ? '?'.http_build_query($paramsCurrent) : '');
		$result = $this->result;
		unset($url, $params, $paramsCurrent);

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
		// special, street magic for extract properties
		if ($this->result instanceof ElementsResult) {
			$elem = $this->result->GetNextElement($this->textHtmlAuto, $this->useTilda);
			if(!$elem){
				$this->current = array();
				return;
			}
			$elemData = $elem->GetFields();
			if ($elem instanceof \_CIBElement) {
				$elemData['PROPERTIES'] = $elem->GetProperties();
				foreach ($elemData['PROPERTIES'] as $code => $prop) {
					$elemData['PROPERTY_' . strtoupper($code) . '_VALUE'] = $prop['VALUE'];
				}
			}

			$this->current = $elemData;
		} else {
			$this->current = $this->result->GetNext($this->textHtmlAuto, $this->useTilda);
		}
	}

	/**
	 * @return array
	 */
	public function toArray($keyField = null) {
		if($this->key == 0){
			if($keyField){
				$array = array();
				foreach ($this as $item) {
					$array[$item[$keyField]] = $item;
				}
			} else {
				$array = iterator_to_array($this);
			}
			return $array;
		} else {
			throw new \BadMethodCallException("Data already fetched");
		}
	}
}
