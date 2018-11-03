<?php

include '../vendor/autoload.php';

use btesanovic\mconv\VConv;

$videoConv = new VConv();
$formatsInput = ['avi','mpg','mov'];

$videoConv->setInputFormats($formatsInput);
$videoConv->setOutputFormat('mp4');
$path = '/Users/bojan/Movies';
//$videoConv->convertRecursive($path , $glob='*t0*');
$videoConv->convertRecursive($path , $glob='*');