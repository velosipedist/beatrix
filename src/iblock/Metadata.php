<?php
namespace beatrix\iblock;
\CModule::includeModule('iblock');
/**
 * Iblock metadata registry.
 * Keeps reusable info about iblocks, their codes, sections and their codes, enum properties etc.
 */
class Metadata
{
    private static $metadata = array();

    public static function getIblockIdByCode($code)
    {
        $map = self::getIblockMap();
        return (int)$map[$code]['ID'];
    }

    public static function getIblockCodeById($iblockId)
    {
        $map = self::getIblockMap();
        foreach ($map as $code => $iblock) {
            if($iblock['ID'] == $iblockId){
               return $iblock['CODE'];
            }
        }
        return null;
    }

    public static function getIblockPropertyId($iblockId, $propertyCode)
    {
        $properties = static::getIblockPropertiesMap($iblockId);
        return (int)$properties[$propertyCode]['ID'];
    }

    public static function getIblockEnumChoices($iblockId, $propertyCode)
    {
        if (!is_int($iblockId)) {
            $iblockId = static::getIblockIdByCode($iblockId);
        }
        $iblockChoices = static::resolveMapData('iblockPropertiesEnumMap', $iblockId, function () use ($iblockId) {
            $enums = \CIBlockPropertyEnum::GetList(array(), array(
                'IBLOCK_ID' => $iblockId
            ));
            $data = array();
            while ($choice = $enums->GetNext()) {
                isset($data[$choice['PROPERTY_ID']]) or $data[$choice['PROPERTY_ID']] = array();
                $data[$choice['PROPERTY_ID']][$choice['XML_ID']] = $choice['ID'];
            }
            return $data;
        });
        $val = $iblockChoices[static::getIblockPropertyId($iblockId, $propertyCode)];
        return $val;
    }

    public static function getIblockPropertiesMap($iblockId)
    {
        if (!is_int($iblockId)) {
            $iblockId = static::getIblockIdByCode($iblockId);
        }
        return static::resolveMapData('iblockPropertiesIdMap', $iblockId, function () use ($iblockId) {
            $results = \CIBlockProperty::GetList(array(), array(
                'IBLOCK_ID' => $iblockId
            ));
            $data = array();
            while ($pr = $results->GetNext()) {
                $data[$pr['CODE']] = $pr;
            }
            return $data;
        });
    }

    public static function getSectionCodeById($sectionId, $iblockCode)
    {
        $map = static::getIblockSectionsMap($iblockCode);
        foreach ($map as $code => $data) {
            if ($data['ID'] == $sectionId) {
                return $code;
            }
        }
        return null;
    }

    public static function getSectionIdByCode($sectionCode, $iblockCode)
    {
        $map = static::getIblockSectionsMap($iblockCode);
        return isset($map[$sectionCode]) ? (int)$map[$sectionCode]['ID'] : null;
    }

    public static function getIblockSectionsMap($iblockId)
    {
        if (!is_int($iblockId)) {
            $iblockId = static::getIblockIdByCode($iblockId);
        }
        return static::resolveMapData('iblockSectionIdMap', $iblockId, function () use ($iblockId) {
            $results = \CIBlockSection::GetList(array(), array(
                'IBLOCK_ID' => $iblockId
            ));
            $data = array();
            while ($pr = $results->GetNext()) {
                $data[$pr['CODE']] = $pr;
            }
            return $data;
        });
    }

    /**
     * Get some mapped data under passed key, fetches missing data at first read.
     * @param $group
     * @param $key
     * @param $resolver
     * @return mixed
     */
    private static function resolveMapData($group, $key, $resolver)
    {
        isset(static::$metadata[$group]) or static::$metadata[$group] = array();
        $val = array_get(static::$metadata[$group], $key, $resolver);
        return static::$metadata[$group][$key] = $val;
    }

    /**
     * @param $code
     * @return mixed
     */
    protected static function getIblockMap()
    {
        return static::resolveMapData('iblockMap', 0, function () {
            $result = array();
            $list = \CIBlock::GetList();
            while ($row = $list->GetNext()) {
                $result[$row['CODE']] = $row;
            }
            return $result;
        });
    }
}
