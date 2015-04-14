<?php
namespace beatrix\iblock;

use beatrix\db\ResultIterator;
use beatrix\helpers\TreeBuilder;

class IblockSectionsMenu
{
	private $itemsPlain = array();
	private $iblockId;

	public function __construct($iblockCode, $sectionId = null) {
		$this->setupIblock($iblockCode);
		$result = $this->loadSections($sectionId);
		$this->itemsPlain = $result->toArray();
	}

	public function getTree($items = null) {
		if (is_null($items)) {
			$items = $this->itemsPlain;
		}
		$builder = new TreeBuilder($items, 'DEPTH_LEVEL', '#children');
		return $builder;
	}

	/**
	 * @param $iblockCode
	 */
	protected function setupIblock($iblockCode) {
		\CModule::IncludeModule('iblock');
		$ibData = \CIBlock::GetList(array(), array('CODE' => $iblockCode))->GetNext();
		$this->iblockId = $ibData['ID'];
	}

	/**
	 * @param $sectionId
	 * @return ResultIterator
	 */
	protected function loadSections($sectionId) {
		$result = new ResultIterator(
			//todo only use certain parent SECTION_ID, if root — skip it, buggy >:|
			\CIBlockSection::GetTreeList(
				array('IBLOCK_ID' => $this->iblockId/*, 'SECTION_ID' => $sectionId*/)
			)
		);
		return $result;
	}
}
