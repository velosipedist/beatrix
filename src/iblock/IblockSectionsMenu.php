<?php
namespace beatrix\iblock;

use beatrix\db\ResultIterator;
use beatrix\helpers\TreeBuilder;

class IblockSectionsMenu
{
    private $itemsPlain = array();
    private $iblockId;

    public function __construct($iblockCode, $sectionCode = null)
    {
        $this->setupIblock($iblockCode);
        $result = $this->loadSections($sectionCode);
        $this->itemsPlain = $result->toArray();
    }

    public function getTree($items = null)
    {
        if (is_null($items)) {
            $items = $this->itemsPlain;
        }
        $builder = new TreeBuilder($items, 'DEPTH_LEVEL');
        return $builder;
    }

    /**
     * @param $iblockCode
     */
    protected function setupIblock($iblockCode)
    {
        $this->iblockId = Metadata::getIblockIdByCode($iblockCode);
    }

    /**
     * @param int $sectionCode
     * @return ResultIterator
     */
    protected function loadSections($sectionCode = null)
    {
        $iblockCode = Metadata::getIblockCodeById($this->iblockId);
        $filter = array('IBLOCK_ID' => $this->iblockId);
        $listQuery = Query::from($iblockCode)
            ->select(array('ID', 'NAME', 'CODE'))
            ->order(array('LEFT_MARGIN' => 'ASC'));
        if ($sectionCode) {
            $sId = Metadata::getSectionIdByCode($sectionCode, $this->iblockId);
            $parent = Query::from($iblockCode)
                ->select(array('ID', 'LEFT_MARGIN', 'RIGHT_MARGIN'))
                ->filter(array('ID' => $sId))
                ->getSections()
                ->current();
            $filter['LEFT_MARGIN'] = $parent['LEFT_MARGIN'];
            $filter['RIGHT_MARGIN'] = $parent['RIGHT_MARGIN'];
        }
        return $listQuery->filter($filter)->getSections();
    }
}
