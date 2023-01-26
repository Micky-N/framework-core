<?php


require_once('vendor/autoload.php');


$out = new \MkyCommand\Output();
$p = $out->progressBar(50);
$p->start();
while(!$p->isCompleted()){
    usleep(50000);
    $p->progress('fkdlkdf');
}

