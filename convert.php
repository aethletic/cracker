<?php 

use Cracker\Data;

require 'vendor/autoload.php';

echo "--- start normalize ---" . PHP_EOL;
Data::normalize(__DIR__ . '/storage/raw/*.txt', __DIR__ . '/storage/trash');
echo PHP_EOL . "--- normalize end ---" . PHP_EOL;

echo PHP_EOL . "--- start convert ---" . PHP_EOL;
Data::prepare(__DIR__ . '/storage/raw/*.png', __DIR__ . '/storage/converted');
echo PHP_EOL . "--- convert end ---" . PHP_EOL;