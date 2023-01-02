<?php

require 'vendor/autoload.php';

$logger = Logger::getLogger("main");
$logger->info("This is an informational message.", true);