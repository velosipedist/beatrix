<?php
namespace beatrix\iblock;

use beatrix\DbResultIterator;
use CIBlockElement;

class Query
{
	private $iblockCode;
	private $selectFields;
	private $filter;
	private $limit;
	private $pageNumber;
	private $sectionCodes;
	private $order;
	private $activeOnly = true;
	private $grouping;
	private $selectId;

	function __construct($iblockCode) {
		$this->iblockCode = $iblockCode;
	}

	//todo merging/resetting operations

	public static function from($iblockCode) {
		//todo cache metadata
		return new static($iblockCode);
	}

	public function select(array $fields = array()) {
		$this->selectFields = $fields;
		//todo all using * from metadata
		return $this;
	}

	public function filter(array $filter) {
		$this->filter = $filter;
		return $this;
	}

	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	public function page($offset) {
		$this->pageNumber = $offset;
		return $this;
	}

	public function order($order) {
		$this->order = $order;
		return $this;
	}

	public function group($group) {
		$this->grouping = $group;
		return $this;
	}

	public function byId($id) {
		$this->selectId = $id;
		return $this;
	}

	public function inSections($codes) {
		$this->sectionCodes = (array)$codes;
		return $this;
	}

	public function countElements() {
		$filter = $this->normalizeFilter();
		$group = $this->normalizeGrouping(false);
		return CIBlockElement::GetList(
			array(),
			$filter,
			$group
		);
	}

	public function getElements($pageSize = null, $pageNumber = null) {
		if (!is_null($pageSize)) {
			$this->limit($pageSize);
		}
		if (!is_null($pageNumber)) {
			$this->page($pageNumber);
		}
		$order = $this->normalizeOrder();
		$filter = $this->normalizeFilter();
		$group = $this->normalizeGrouping(false);
		$navParams = $this->normalizeNavParams();
		$select = $this->normalizeSelect();
		return CIBlockElement::GetList(
			$order,
			$filter,
			$group,
			$navParams,
			$select
		);
	}

	public function getSections($includeCount = false) {
		$order = $this->normalizeOrder();
		$filter = $this->normalizeFilter();
		$navParams = $this->normalizeNavParams();
		$select = $this->normalizeSelect();
		return \CIBlockSection::GetList(
			$order,
			$filter,
			$includeCount,
			$select,
			$navParams
		);
	}

	/**
	 * @return array
	 */
	private function normalizeOrder() {
		$order = is_null($this->order) ? array('SORT' => 'ASC') : (array)$this->order;
		return $order;
	}

	private function normalizeFilter() {
		$filter = $this->filter ? (array)$this->filter : array();
		if ($this->activeOnly) {
			$filter['ACTIVE'] = 'Y';
		}
		$filter['IBLOCK_CODE'] = $this->iblockCode;
		if ($this->selectId) {
			$filter = array('ID' => $this->selectId);
		} elseif ($this->sectionCodes) {
			$sections = DbResultIterator::from(
				Query::from($this->iblockCode)
					->select(array('ID'))
					->filter(array('CODE' => $this->sectionCodes))
					->getSections()
			);
			$filter['SECTION_ID'] = isset($filter['SECTION_ID']) ? (array)$filter['SECTION_ID'] : array();
			foreach ($sections as $section) {
				$filter['SECTION_ID'][] = $section['ID'];
			}
		} else {
			// fix selector bug
			if (is_array($filter['SECTION_ID']) && empty($filter['SECTION_ID'])) {
				unset($filter['SECTION_ID']);
			}
			if (is_array($filter['SECTION_CODE']) && empty($filter['SECTION_CODE'])) {
				unset($filter['SECTION_CODE']);
			}
		}
		return $filter;
	}

	/**
	 * @param boolean $activeOnly
	 */
	public function activeOnly($activeOnly) {
		$this->activeOnly = $activeOnly;
		return $this;
	}

	private function normalizeGrouping($forCountingQuery) {
		if ($forCountingQuery) {
			return array();
		} elseif (!$this->grouping) {
			return false;
		} else {
			return $this->grouping;
		}
	}

	private function normalizeNavParams() {
		$params = array();
		if (!is_null($this->limit)) {
			$params['nPageSize'] = $this->limit;
		}
		if (!is_null($this->pageNumber)) {
			$params['iNumPage'] = $this->pageNumber;
		}
		return $params ? $params : false;
	}

	private function normalizeSelect() {
		$select = (array)$this->selectFields;
		$select[] = 'IBLOCK_ID';
		//todo handle PROPERTY_* from metadata
		return array_unique($select);
	}
}
 