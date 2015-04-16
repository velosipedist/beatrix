<?php
namespace beatrix\iblock;

use beatrix\db\ResultIterator;
use CIBlockElement;
//todo separate to abstract query and element/section/etc subclasses
/**
 * Elements or sections selecting query, reurns decorated result after execution.
 */
class Query
{
    /**
     * @var string
     */
    private $iblockCode;
    /**
     * @var array
     */
    private $selectFields = array();
    /**
     * @var array
     */
    private $filter;
    /**
     * @var int
     */
    private $limit;
    /**
     * @var int Page offset specified by $this->page()
     */
    private $pageNumber;
    /**
     * @var array
     */
    private $sectionCodes = array();
    /**
     * @var array
     */
    private $order;
    /**
     * @var bool
     * todo make false by default
     */
    private $activeOnly = true;
    /**
     * @var array
     */
    private $grouping;
    /**
     * @var int
     */
    private $selectById;
    /**
     * @var bool
     */
    private $isPropertiesQueried = false;

    /**
     * @param string $iblockCode Which infoblock query for
     */
    function __construct($iblockCode)
    {
        \CModule::includeModule('iblock');
        $this->iblockCode = $iblockCode;
    }

    /**
     * Factory method returning new query from passed iblock
     * @param $iblockCode
     * @return static
     */
    public static function from($iblockCode)
    {
        return new static($iblockCode);
    }

    /**
     * Which field and/or properties should be selected.
     * Auto-fixes required fields when any properties specified (IBLOCK_ID auto-append).
     * @param array|string $fields
     * @param ... $fields Field names as separate params
     * @return $this
     */
    public function select($fields = array())
    {
        $fields = array_flatten(func_get_args());
        $addFields = array();
        $hasPropertiesWildcard = false;
        //todo move this normalization to element query call, when byId flag may be applied
        $fields = array_filter($fields, function ($f) use (&$addFields, &$hasPropertiesWildcard) {
            if (strpos($f, 'PROPERTY_') === 0) {
                $properties = Metadata::getIblockPropertiesMap($this->iblockCode);
                $this->isPropertiesQueried = true;
                if ($f == 'PROPERTY_*') {
                    $hasPropertiesWildcard = true;
                    foreach ($properties as $code => $data) {
                        if ($data['MULTIPLE'] == 'Y') {
                            //todo separate query for props?
                            continue;
                        }
                        $addFields[] = 'PROPERTY_' . $code;
                    }
                    return false;
                } else {
                    if ($hasPropertiesWildcard) {
                        return false;
                    }
                    preg_match('/^PROPERTY_([^_]+)/', $f, $match);
                    $code = $match[1];
                    return isset($properties[$code]) && $properties[$code]['MULTIPLE'] == 'N';
                }
            } else {
                return true;
            }
        });
        $this->selectFields = array_unique(array_merge($fields, $addFields));
        return $this;
    }

    /**
     * Set query filter by fields and/or properties
     * @param array $filter `["field" => val, "<field2" => val2, ...]`
     * @return $this
     */
    public function filter(array $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Shortcut for filtering by property names like `["propName" => val, ...]`
     * @param array $filter
     * @return $this
     */
    public function propertyFilter(array $filter)
    {
        $this->filter = (array)$this->filter;
        foreach ($filter as $propName => $val) {
            $this->filter['PROPERTY_' . $propName . '_VALUE'] = $val;
        }
        return $this;
    }

    /**
     * Add filter conditions for ENUM-type properties
     * @param array $filter
     * @return $this
     */
    public function enumFilter(array $filter)
    {
        //todo move into propertyFilter() with metadata checks
        foreach ($filter as $propertyCode => $values) {
            $enumChoices = Metadata::getIblockEnumChoices($this->iblockCode, $propertyCode);
            if (!is_array($values)) {
                $choiceIds = $enumChoices[$values];
            } else {
                $choiceIds = array();
                foreach ($values as $val) {
                    $choiceIds[] = $enumChoices[$val];
                }
            }
            //todo respect prefixing logic like !, <>, etc
            $this->filter['PROPERTY_' . $propertyCode] = $choiceIds;
        }
        return $this;
    }

    /**
     * Limit query results
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }

    /**
     * Set pagination offset, if limit is set
     * @param $offset
     * @return $this
     */
    public function page($offset)
    {
        $this->pageNumber = (int)$offset;
        return $this;
    }

    /**
     * @param string|array $order Field name or config like `array('SORT' => 'ASC', 'NAME'=>'DESC')`
     * @return $this
     */
    public function order($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     * Set random ordering
     * @return $this
     */
    public function random()
    {
        $this->order = array('rand' => 'asc');
        return $this;
    }

    /**
     * Group by fields
     * @param array $group
     * @param ... Group fields as separate params
     * @return $this
     */
    public function group($group)
    {
        $this->grouping = array_flatten(func_get_args());
        return $this;
    }

    /**
     * Search just by item ID
     * @param int $id
     * @return $this
     */
    public function byId($id)
    {
        $this->selectById = $id;
        return $this;
    }

    /**
     * Filter by section code(s)
     * @param string|string[] $codes
     * @param ... Codes as separate params
     * @return $this
     */
    public function inSections($codes)
    {
        $this->sectionCodes = array_flatten(func_get_args());
        return $this;
    }

    /**
     * Execute count query
     * @return int
     */
    public function countElements()
    {
        $filter = $this->normalizeFilter();
        //todo remove false param?
        $group = $this->normalizeGrouping(false);
        return CIBlockElement::GetList(
            array(),
            $filter,
            $group
        );
    }

    /**
     * Execute query and return elements db result
     * @param null $pageSize
     * @param null $pageNumber
     * @return ResultIterator
     */
    public function getElements($pageSize = null, $pageParam = null)
    {
        //todo cache shortcut
        if (!is_null($pageSize)) {
            $this->limit($pageSize);
            $pageParam = is_null($pageParam) ? 'nav_page' : $pageParam;
            $this->page(array_get($_GET, $pageParam, 1));
        }
        $order = $this->normalizeOrder();
        $filter = $this->normalizeFilter();
        $group = $this->normalizeGrouping(false);
        $navParams = $this->normalizeNavParams();
        $select = $this->normalizeSelect();
        $result = CIBlockElement::GetList(
            $order,
            $filter,
            $group,
            $navParams,
            $select
        );
        return ResultIterator::from(new ElementsResult($result, $this), $pageSize, $pageParam);
    }

    /**
     * Execute query and return sections db result
     * @param bool $includeCount
     * @return ResultIterator
     */
    public function getSections($includeCount = false)
    {
        $order = $this->normalizeOrder();
        $filter = $this->normalizeFilter();
        $navParams = $this->normalizeNavParams();
        $select = $this->normalizeSelect();
        $CDBResult = \CIBlockSection::GetList(
            $order,
            $filter,
            $includeCount,
            $select,
            $navParams
        );
        return ResultIterator::from(new SectionsResult($CDBResult));
    }

    /**
     * @return array
     */
    private function normalizeOrder()
    {
        $order = is_null($this->order) ? array('SORT' => 'ASC') : (array)$this->order;
        return $order;
    }

    /**
     * Clean up filter conditions, drop unneeded, add missing
     * @return array
     */
    private function normalizeFilter()
    {
        $filter = $this->filter ? (array)$this->filter : array();
        if ($this->activeOnly) {
            $filter['ACTIVE'] = 'Y';
        }
        $filter["IBLOCK_LID"] = SITE_ID;
        //todo flag methods: $filter["CHECK_PERMISSIONS"] = "Y"; $filter["ACTIVE_DATE"] = "Y";

        if (!is_null($this->iblockCode)) {
            $filter['IBLOCK_ID'] = Metadata::getIblockIdByCode($this->iblockCode);
        }
        if ($this->selectById) {
            $filter = array('ID' => $this->selectById);
        } elseif ($this->sectionCodes) {
            $filter['SECTION_ID'] = isset($filter['SECTION_ID']) ? (array)$filter['SECTION_ID'] : array();

            $sectionCodes = $this->sectionCodes;
            foreach ($sectionCodes as $c => $code) {
                if (is_numeric($code)) {
                    $filter['SECTION_ID'][] = (int)$code;
                    unset($sectionCodes[$c]);
                }
            }
            if ($sectionCodes) {
                $sections = Metadata::getIblockSectionsMap($this->iblockCode);
                foreach ($sectionCodes as $sectionCode) {
                    foreach ($sections as $code => $section) {
                        if ($sectionCode == $code) {
                            $filter['SECTION_ID'][] = $section['ID'];
                            break;
                        }
                    }
                }
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
     * Select only items in active date range (affects only elements)
     * @param boolean $activeOnly
     * @return $this
     */
    public function activeOnly($activeOnly = true)
    {
        $this->activeOnly = $activeOnly;
        return $this;
    }

    private function normalizeGrouping($forCountingQuery)
    {
        if ($forCountingQuery) {
            return array();
        } elseif (!$this->grouping) {
            return false;
        } else {
            return $this->grouping;
        }
    }

    private function normalizeNavParams()
    {
        $params = array();
        if (!is_null($this->limit)) {
            $params['nPageSize'] = $this->limit;
        }
        //todo set number only when has limit?
        if (!is_null($this->pageNumber)) {
            $params['iNumPage'] = $this->pageNumber;
        }
        return $params ? $params : false;
    }

    private function normalizeSelect()
    {
        $select = $this->selectFields;
        $select[] = 'ID';
        $select[] = 'IBLOCK_ID';
        return array_unique($select);
    }

    /**
     * @return array
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return boolean
     */
    public function getIsPropertiesQueried()
    {
        return $this->isPropertiesQueried;
    }
}
