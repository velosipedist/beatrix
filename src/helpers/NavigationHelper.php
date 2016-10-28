<?php
namespace beatrix\helpers;
use beatrix\db\ResultIterator;
use beatrix\iblock\Query;
use CIBlockSection;
use League\Url\Url;

class NavigationHelper {
	private static $extractedItemChains = array();
	private static $extractedItemSections = array();

	public static function item($id, $root = '/', $withSections = true) {
		if ($withSections) {
			$sectionId = self::extractSectionId($id);
			$sectionUrl = self::sectionUrl($sectionId, $root);
			return $sectionUrl . $id .'/';
		}else{
			return rtrim($root, '/') . '/' . $id .'/';
		}
	}

	public static function section($sectionId, $root = '/') {
		$sUrl = self::sectionUrl($sectionId, $root);
		return $sUrl;
	}

	public static function breadcrumbs($sectionId, $root = '/') {
		$sections = self::extractSectionChain($sectionId);
		$breadcrumbs = array();
		while(count($sections)) {
			$urlParts = array();
			foreach ($sections as $chainPart) {
				$urlParts[] = $chainPart['CODE'];
			}
			$current = current($sections);
			$breadcrumbs[]= array(
				'NAME'=> $current['NAME'],
				'URL'=> rtrim($root, '/') . '/'. implode('/',$urlParts) .'/',
			);
			array_shift($sections);
		}
		return $breadcrumbs;
	}

	public static function extractSectionCodes($sectionId) {
		$chain = self::extractSectionChain($sectionId);
		return array_pluck($chain, 'CODE');
	}

	public static function extractSectionChain($sectionId) {
		if(!isset(self::$extractedItemChains[$sectionId])){
            $result = \CIBlockSection::GetNavChain(0, $sectionId);
			$chain = ResultIterator::from($result)->toArray();
			self::$extractedItemChains[$sectionId] = $chain;
		}
		return self::$extractedItemChains[$sectionId];
	}

	/**
	 * @param $itemId
	 * @return mixed
	 */
	public static function extractSectionId($itemId) {
		if(!isset(self::$extractedItemSections[$itemId])) {
			$item = Query::from(null)->byId($itemId)->select(array('IBLOCK_SECTION_ID'))
                ->getElements()
                ->current();
			$sectionId = $item['IBLOCK_SECTION_ID'];
			self::$extractedItemSections[$itemId] = $sectionId;
		}
		return self::$extractedItemSections[$itemId];
	}

	/**
	 * @param $root
	 * @param $sectionId
	 * @return string
	 */
	public static function sectionUrl($sectionId, $root = '/') {
		$sections = self::extractSectionChain($sectionId);
		$pathParts = array_pluck($sections, 'CODE');
		$sectionUrl = rtrim($root, '/') . '/' . implode('/', $pathParts) . '/';
		return $sectionUrl;
	}

	public static function extendQueryParams(array $queryParams, $url = null) {
		if(is_null($url)){
			$url = $_SERVER['REQUEST_URI'];
		}
		$queryParamsStr = parse_url($url, PHP_URL_QUERY);
		$queryParamsAdd = array();
		if ($queryParamsStr) {
			parse_str($queryParamsStr, $queryParamsAdd);
		}
		$queryParams = array_merge($queryParamsAdd, $queryParams);

		return http_build_query($queryParams);
	}

    /**
     * @return Url
     */
    public static function currentUrl()
    {
        return Url::createFromServer($_SERVER);
    }
}
