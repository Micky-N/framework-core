<?php

require 'vendor/autoload.php';
use MkyCommand\Input;
use MkyCommand\Output;
use MkyCommand\ProgressBar;

$out = new Output();
$arr = array(
	array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
    array("key1", 4, 0, "description 1"),
	array("key2", 0, 33, ""),
	array("", 0, 10000, "something"),
);
$p = $out->progressBar($arr, function($val, $i, ProgressBar $progress) use (&$arr){
    if(!$val[3]){
        $arr[$i][3] = "some $i";
    }elseif($val[3] === 'something'){
        $progress->stop();
    }
    return $arr[$i][3];
});

$p->process();
$p->resume();
$p->process();

// $p->start('Start');

// foreach($arr as $i => $val){
//     if(!$val[3]){
//         $arr[$i][3] = "some $i";
//     }else{
//         $p->stop('Stop');
//     }
//     $p->progress($arr[$i][3]);
// }

// $p->completed('Completed');
