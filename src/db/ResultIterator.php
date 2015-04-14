<?php
namespace beatrix\db;

use beatrix\iblock\ElementsResult;
use beatrix\iblock\NavigationHelper;
use beatrix\iblock\SectionsResult;
use Countable;
use Iterator;

\CModule::IncludeModule('iblock');

/**
 * Iterates Database query result, also usable with search, iblock elements & sections result etc.
 *
 */
class ResultIterator implements Iterator, Countable
{
    /** @var \CDBResult | \CSearch | \CIBlockResult | ElementsResult | SectionsResult */
    private $result;
    /** @var array */
    private $current;
    /** @var bool */
    private $textHtmlAuto = true;
    /** @var bool */
    private $useTilda = true;
    private $key;
    /** @var int */
    private $pageSize;
    /** @var string */
    private $pageUrlParam;
    private $items = array();

    /**
     * @param \CDBResult | \CSearch | \CIBlockResult | ElementsResult | SectionsResult $result
     * @param int $pageSize Page size for autimatic pagination
     * @param string $pageUrlParam Which $_GET param to use for current page number indication
     * @param bool $textHtmlAuto
     * @param bool $useTilda
     */
    public function __construct($result, $pageSize = null, $pageUrlParam = 'nav_page')
    {
        \CPageOption::SetOptionString("main", "nav_page_in_session", "N");
        $this->result = $result;
        if ($pageSize && !$this->result->bNavStart) {
            $this->result->NavStart(
                $pageSize,
                true,
                isset($_GET[$pageUrlParam]) ? (int)$_GET[$pageUrlParam] : false
            );
        }

        $this->pageSize = $pageSize;
        $this->pageUrlParam = $pageUrlParam;
        $this->key = 0;
        while ($next = $this->fetchCurrent()) {
            $this->items[] = $next;
        }
    }

    /**
     * Factory method, see {@link __construct} docs
     * @return static
     */
    public static function from($result, $pageSize = null, $pageUrlParam = 'nav_page')
    {
        return new static($result, $pageSize, $pageUrlParam);
    }

    public function current()
    {
        return current($this->items);
    }

    public function next()
    {
        next($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function valid()
    {
        return current($this->items) !== false;
    }

    public function rewind()
    {
        reset($this->items);
    }

    public function count()
    {
        return count($this->items);
    }

    /**
     * Renders pagination string
     * @param array $variables See `templates/bootstrap/pagination.php` for available vars doc
     * @param string $template Template file name for pager. Bootstrap 3 implementation is used by default
     * @return string
     */
    public function pagination(array $variables = array(), $template = 'beatrix::bootstrap/pagination')
    {
        if ($this->result->NavPageCount < 2) {
            return '';
        }
        //todo parametrize placeholder ?
        $params = array($this->pageUrlParam => '__PAGENUMBER__');
        $currentUrl = NavigationHelper::currentUrl();
        $currentUrl->getQuery()->modify($params);
        $urlTemplate = $currentUrl->__toString();
        $query = $currentUrl->getQuery();
        unset($query[$this->pageUrlParam]);
        $urlStartTemplate = $currentUrl->__toString();
        $result = $this->result;

        return \Beatrix::view()->render(
            $template,
            array_merge($variables, array(
                'urlTemplate' => $urlTemplate,
                'urlStartTemplate' => $urlStartTemplate,
                'pageCount' => $result->NavPageCount,
                'pageNumber' => $result->NavPageNomer,
                'isPrevArrowActive' => ($result->NavPageNomer > 1),
                'isNextArrowActive' => ($result->NavPageNomer < $result->NavPageCount),
                'isStartArrowActive' => ($result->NavPageNomer != 1),
                'isEndArrowActive' => ($result->NavPageNomer != $result->NavPageCount),
            ))
        );
    }

    /**
     * Advances to next db result. Fixes multi-value properties bug at iblock element query result fetching.
     * @return array
     */
    private function fetchCurrent()
    {
        // special, street magic for extract properties
        if ($this->result instanceof ElementsResult) {
            $elem = $this->result->GetNextElement($this->textHtmlAuto, $this->useTilda);
            if (!$elem) {
                return false;
            }
            $elemData = $elem->GetFields();
            //todo skip properties auto-fetching if no props was queried
            // if $this->result->getQuery()->getIsPropertiesQueried() ...
            if ($elem instanceof \_CIBElement) {
                $elemData['PROPERTIES'] = $elem->GetProperties();
                foreach ($elemData['PROPERTIES'] as $code => &$prop) {
                    $elemData['PROPERTY_' . strtoupper($code) . '_VALUE'] = $prop['VALUE'];
                    if ($prop['PROPERTY_TYPE'] == 'F') {
                        if (is_array($prop['VALUE'])) {
                            foreach ($prop['VALUE'] as $v => $val) {
                                $prop['~VALUE'][$v] = \CFile::GetPath($val);
                            }
                        } elseif ($prop['VALUE']) {
                            $prop['~VALUE'] = \CFile::GetPath($prop['VALUE']);
                        }
                    }
                }
            }
            return $elemData;
        } else {
            return $this->result->GetNext($this->textHtmlAuto, $this->useTilda);
        }
    }

    /**
     * @return array
     */
    public function toArray($keyField = null)
    {
        return $this->items;
    }

    /**
     * @return int
     */
    public function getPageSize()
    {
        return $this->pageSize;
    }

    /**
     * @return string
     */
    public function getPageUrlParam()
    {
        return $this->pageUrlParam;
    }
}
