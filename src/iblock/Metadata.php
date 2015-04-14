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
        return static::resolveMapData('iblockIdMap', $code, function () use ($code) {
            $result = \CIBlock::GetList(array(), array('CODE' => $code))->GetNext();
            if (!$result) {
                throw new \RuntimeException("No IBlock with code [$code]");
            }
            return (int)$result['ID'];
        });
    }

    public static function getIblockPropertyId($iblockId, $propertyCode)
    {
        $properties = static::getIblockPropertiesMap($iblockId);
        return $properties[$propertyCode]['ID'];
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
            if($data['ID'] == $sectionId){
                return $code;
            }
        }
        return null;
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
     * @param $field
     * @param $key
     * @param $resolver
     * @return mixed
     */
    private static function resolveMapData($field, $key, $resolver)
    {
        $val = array_get(static::$metadata[$field], $key, $resolver);
        return static::$metadata[$field] = $val;
    }
}
