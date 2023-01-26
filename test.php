<?php

require 'vendor/autoload.php';
use MkyCommand\Input;

$input = new Input();

dd($input->confirm('test', true));