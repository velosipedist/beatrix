<?php
namespace beatrix\iblock;

/**
 * Helper that checks if iblock element with specified ID corresponds some conditions.
 */
class Matcher
{
    private $elementId;
    private $element;
    private $iblockCodesPositive = array();
    private $iblockCodesNegative = array();
    private $sectionCodesPositive = array();
    private $sectionCodesNegative = array();

    /**
     * @param int $elementId
     */
    function __construct($elementId)
    {
        \CModule::includeModule('iblock');
        $this->elementId = $elementId;
    }

    /**
     * @param array|... Codes of iblocks. Element must belong to one of them.
     * @return $this
     */
    public function inIblocks()
    {
        $this->iblockCodesPositive = array_unique(array_flatten(func_get_args()));
        return $this;
    }

    /**
     * @param array|... Codes of iblocks. Element must not belong to any of them.
     * @return $this
     */
    public function notInIblocks()
    {
        $this->iblockCodesNegative = array_unique(array_flatten(func_get_args()));
        return $this;
    }

    /**
     * @param array|... Codes of sections. Element must belong to one of them.
     * @return $this
     */
    public function inSections()
    {
        $this->sectionCodesPositive = array_unique(array_flatten(func_get_args()));
        return $this;
    }

    /**
     * @param array|... Codes of sections. Element must not belong to any of them.
     * @return $this
     */
    public function notInSections()
    {
        $this->sectionCodesNegative = array_unique(array_flatten(func_get_args()));
        return $this;
    }

    /**
     * @return bool
     * @throws \RuntimeException
     */
    public function match()
    {
        $element = \CIBlockElement::GetByID($this->elementId)->GetNext();
        if (!$element) {
            throw new \RuntimeException("No element #{$this->elementId} found");
        }
        $this->element = $element;
        $iblockMatched = true;
        if ($this->iblockCodesPositive || $this->iblockCodesNegative) {
            $iblockMatched = false;
            $iblock = \CIBlock::GetByID($element['IBLOCK_ID'])->GetNext();
            if ($this->iblockCodesPositive && in_array($iblock['CODE'], $this->iblockCodesPositive)) {
                $iblockMatched = true;
            }
            if ($this->iblockCodesNegative && in_array($iblock['CODE'], $this->iblockCodesPositive)) {
                return false;
            }
        }
        if ($iblockMatched && $this->sectionCodesPositive || $this->sectionCodesNegative) {
            $section = \CIBlockSection::GetByID($this->element['IBLOCK_SECTION_ID'])->GetNext();
            if ($this->sectionCodesPositive && in_array($section['CODE'], $this->sectionCodesPositive)) {
                return true;
            }
            if ($this->sectionCodesNegative && in_array($section['CODE'], $this->sectionCodesPositive)) {
                return false;
            }
        }
        return false;
    }

    /**
     * @return array Iblock element record data
     */
    public function getElement()
    {
        return $this->element;
    }

}
