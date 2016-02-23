<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Новости");
?>
<?
Beatrix::app()->container->set('something-to-use-anywhere', function($set){
    // here we're setting up some data that can be lazy-loaded anywhere on that file context or in any template
    // ...
    return $something;
});

Beatrix::app()->get('/', function(){
    // load news sections
    $sections	= \beatrix\iblock\Query::from('news')
        ->select(['ID','NAME'])
        ->order(['SORT'=>'ASC'])
        ->getSections();

    // load paginated news
    $news = \beatrix\iblock\Query::from('news')
        ->select(['NAME', 'DETAIL_PAGE_URL','PROPERTY_*'])
        ->getElements();

    // render template
    view('news/list', ['news'=>$news, 'sections'=>$sections]);
});

Beatrix::app()->get('/:section', function($section){
    // load paginated news
    $news = \beatrix\iblock\Query::from('news')
        ->select(['NAME', 'DETAIL_PAGE_URL','PROPERTY_*'])
        ->inSections($section)
        ->getElements();
    view('news/list', ['news'=>$news]);
})->conditions(['section' => '\d+']); // filtering by regex

Beatrix::app()->get('/detail/:id', function($id){
    // shift only item by id
    $item = \beatrix\iblock\Query::from('news')
        ->byId($id)
        ->select(['ID','NAME', 'DETAIL_TEXT', 'IBLOCK_SECTION_ID','PROPERTY_*'])
        ->getElements()
        ->current();
    // change title
    $GLOBALS['APPLICATION']->SetTitle($item['NAME']);

    view('news/detail', ['item'=>$item]);
});

// start router
Beatrix::app()->run();
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>