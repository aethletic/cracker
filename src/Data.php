<?php

namespace Cracker;

use Intervention\Image\ImageManager;

class Data
{
    /**
     * @var ImageManager
     */
    private static $manager;


    public static function prepare($from, $to) 
    {
        $images = glob($from);

        $to = rtrim($to, '\/');

        self::$manager = new ImageManager([
            'driver' => 'imagick',
        ]);

        $countOfAll = count($images);

        foreach ($images as $key => $file) {
            $current = $key + 1;

            $filename = self::getFilename($file);
      
            Image::convert(self::$manager->make($file), self::$manager)
                ->save("{$to}/{$filename}.png", null, 'png');

            $dir = dirname($file);
            copy("{$dir}/{$filename}.gt.txt", "{$to}/{$filename}.gt.txt");

            echo "[{$current}/{$countOfAll}] converted: {$filename}" . PHP_EOL;
        }    
    }

    public static function normalize($imagesDir, $trashDir) 
    {
        $files = glob($imagesDir);

        $trashDir = rtrim($trashDir, '\/');

        foreach ($files as $key => $file) {
            $text = trim(file_get_contents($file));

            if (mb_strlen($text) == 6) {
                continue;
            }

            $filename = self::getFilename($file);
            $dir = dirname($file);

            rename("{$dir}/{$filename}.gt.txt", "{$trashDir}/{$filename}.gt.txt");
            rename("{$dir}/{$filename}.png", "{$trashDir}/{$filename}.png");

            echo "Moved to trash: {$filename}" . PHP_EOL;
        }
    }

    private static function getFilename($file) 
    {
        $arr = explode('.', basename($file));
        return array_shift($arr);
    }
}
