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
	usleep(150000);
    if(!$val[3]){
        $arr[$i][3] = "some $i";
    }elseif($val[3] === 'something'){
        $progress->stop('Erreur '. $i);
    }
    return 'mail send to '.$i;
});

$p->start();
$p->process();
$p->resolve($p->current(), function($el, $i){
	$el[3] = 'some '. $i;
	return $el;
}, 'Resolve');
$p->resume('Resume');
$p->process();