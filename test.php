<?php

use Cracker\Crack;

require 'vendor/autoload.php';

// 1 iteration:
// steam2 100 images - Success: 59, fails: 43
// steam2 1375 images - Success: 812, fails: 563
// steam3 1375 images - 1001/374
// steam3 2375 images - Success: 1726, fails: 649
// steam4 1375 images - 996/379
// steam4 2375 images - 1734/641 

$iterations     = 1;
$model          = 'steam';
$testAllImages  = true;
$imagesCount    = 100;
$dirWithImages  = __DIR__ . '/storage/raw/*.png';

$resolved = [
    'success' => 0,
    'fail' => 0,
];

$images = glob($dirWithImages);

if (!$testAllImages) {
    $images = array_chunk($images, $imagesCount);
    $images = array_shift($images);
}

$countOfAll = count($images);

foreach ($images as $key => $file) {
    $current = $key + 1;

    $arr = explode('.', basename($file));
    $filename = array_shift($arr);
    $dir = dirname($file);

    $cracked = (new Crack($file))
        ->storage(__DIR__ . '/storage')
        ->data(__DIR__)
        ->model($model)
        ->iterations($iterations)
        ->resolve(true);

    $target = file_get_contents("{$dir}/{$filename}.gt.txt");

    $matches = similar_text($target, $cracked['captcha'], $percent);

    $percent = (float) round($percent, 2);

    if ($percent == 100) {
        $resolved['success']++;
    } else {
        $resolved['fail']++;
    }

    echo "[{$current}/{$countOfAll}] [{$resolved['success']}/{$resolved['fail']}] [{$percent}%] [{$matches}/6] [{$target}|{$cracked['captcha']}] filename: {$filename} time: {$cracked['time']} sec" . PHP_EOL;
}

echo "-----------------------" . PHP_EOL;
echo "Success: {$resolved['success']}, fails: {$resolved['fail']}" . PHP_EOL;
