<?php
// #QUERYING
$all = \beatrix\iblock\Query::from('news')
    ->select(['NAME'])
    ->getElements();

$activeOnly = \beatrix\iblock\Query::from('news')
    ->select(['NAME'])
    ->activeOnly(true) // only ACTIVE = Y
    ->getElements();

$activeOnly = \beatrix\iblock\Query::from('news')
    ->select(['NAME'])
    ->filter(['NAME'=>'foo', '!DETAIL_TEXT'=>'']) // foo articles wih non-empty text
    ->getElements();

// # PROPERTIES FILTERING
$activeOnly = \beatrix\iblock\Query::from('news')
    ->select(['NAME'])
    ->propertyFilter(['SUBTITLE'=>'foo', '!CITE'=>'']) // foo-titled articles wih non-empty cite text
    ->getElements();

// complex property filter, details here http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockelement/getlist.php
$activeOnly = \beatrix\iblock\Query::from('news')
    ->select(['NAME'])
    ->filter(['%PROPERTY_AUTHOR.NAME'=>'foo', '!CITE'=>'']) // foo's articles wih non-empty cite text
    ->getElements();

// enumerated values
$frontPage = \beatrix\iblock\Query::from('news')
    ->select(['NAME'])
    ->activeOnly(true)
    ->enumFilter(['FRONTPAGE'=>'Y']) // assumes PROPERTY_FRONTPAGE is list-type with choice having XML_ID = Y
    ->getElements();


// # READING
foreach($all as $item){
    // common fields
    $item['ID'];
    $item['NAME'];

    $item['PROPERTIES']['CITE']['VALUE']; // array when property is multiple
    $item['PROPERTIES']['PHOTO']['VALUE']; // file id
    $item['PROPERTIES']['AUTHOR']['VALUE']; // linked record id
}