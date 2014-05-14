<?php
/**
 * @var string $urlTemplate http://current/url/?param=1&page=__PAGENUMBER__
 * @var string $urlStartTemplate http://current/url/?param=1
 * @var $prevTitle
 * @var $nextTitle
 * @var $startTitle
 * @var $endTitle
 * @var $showArrows
 * @var $showStartEndArrows
 * @var $showArrowsWhenInactive
 * @var $size null | 'sm' | 'lg'
 * @var CAllDBResult $result
 *
 * auto-computed vars
 * @var bool $isPrevArrowActive
 * @var bool $isNextArrowActive
 * @var bool $isStartArrowActive
 * @var bool $isEndArrowActive
 */
if(!isset($prevTitle)){
	$prevTitle = '&lsaquo;';
}
if(!isset($nextTitle)){
	$nextTitle = '&rsaquo;';
}
if(!isset($startTitle)){
	$startTitle= '&laquo;';
}
if(!isset($endTitle)){
	$endTitle = '&raquo;';
}
if(!isset($showArrows)){
	$showArrows = true;
}
if(!isset($showArrowsWhenInactive)) {
	$showArrowsWhenInactive = true;
}

//todo refactor to pageUrl helper
$startUrl = str_replace('__PAGENUMBER__', 1, $urlStartTemplate);
$prevUrl = str_replace(
	'__PAGENUMBER__',
	$result->NavPageNomer - 1,
	($result->NavPageNomer == 2 ? $urlStartTemplate : $urlTemplate)
);
$nextUrl = str_replace('__PAGENUMBER__', $result->NavPageNomer + 1, $urlTemplate);
$endUrl = str_replace('__PAGENUMBER__', $result->NavPageCount, $urlTemplate);
?>
<ul class="pagination <?=($size ? "pagination-$size" : '')?>">
	<?if($showArrows):?>
<!--	start-->
	<?if($isStartArrowActive){?>
		<li><a href="<?= $startUrl ?>"><?=$startTitle?></a></li>
	<?} elseif ($showArrowsWhenInactive){?>
		<li class="disabled">
			<a><?=$startTitle?></a>
		</li>
	<?}?>
<!--	prev-->
	<?if($isPrevArrowActive){?>
		<li><a href="<?=$prevUrl?>"><?=$prevTitle?></a></li>
	<?} elseif ($showArrowsWhenInactive) {?>
		<li class="disabled">
			<a><?=$prevTitle?></a>
		</li>
	<?}?>
	<?endif?>
<!--	numbers-->
	<?
	foreach (range(1, $result->NavPageCount) as $pageNum) {
		$isActive = $pageNum == $result->NavPageNomer;
		$url = str_replace('__PAGENUMBER__', $pageNum, ($pageNum == 1 ? $urlStartTemplate : $urlTemplate));
	?>
		<li <?=$isActive ? 'class="active"' : ''?>><a href="<?=$url?>"><?=$pageNum?></a></li>
	<?
	}
	?>
	<?if($showArrows):?>
<!--	next-->
	<?if($isNextArrowActive){?>
		<li><a href="<?=$nextUrl ?>"><?=$nextTitle?></a></li>
	<?} elseif ($showArrowsWhenInactive){?>
		<li class="disabled">
			<a href="javascript:;"><?=$nextTitle?></a>
		</li>
	<?}?>
<!--	end-->
		<?if($isEndArrowActive){?>
			<li><a href="<?=$endUrl ?>"><?=$endTitle?></a></li>
		<?} elseif ($showArrowsWhenInactive){?>
			<li class="disabled">
				<a href="javascript:;"><?=$endTitle?></a>
			</li>
		<?}?>
	<?endif?>
</ul>
