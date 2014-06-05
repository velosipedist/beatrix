<?php
namespace beatrix;
class Img {
	public static function thumbSrc($fileName){
		$ext = pathinfo($fileName, PATHINFO_EXTENSION);
		$name = pathinfo($fileName, PATHINFO_FILENAME);
		return dirname($fileName).'/'.$name. '.thumb.' . $ext;
	}
}
 