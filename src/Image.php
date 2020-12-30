<?php

namespace Cracker;

use Intervention\Image\ImageManager;

class Image
{
    private static $canvas;
    private static $image = '';

    private static $imageWidth;
    private static $imageHeight;

    /**
     * @var ImageManager
     */
    private static $manager;

    public static function convert($image, $manager)
    {
        self::$image = $image;

        self::$imageWidth = self::$image->width();
        self::$imageHeight = self::$image->height();

        self::$canvas = $manager->canvas(self::$imageWidth, self::$imageHeight, '#fff');

        /** преоброзовываем изображение */
        self::$image
            ->greyscale()
            ->colorize(0, 0, 0)
            ->contrast(5)
            ->gamma(0.6)
            ->invert();

        // self::$image->limitColors(20);

        self::treshold(100);
        self::$canvas->blur(1);
        self::treshold(100);

        self::treshold(90);
        self::$canvas->blur(1);

        return self::$canvas;
    }

    private static function treshold($treshold = 50) 
    {
        for ($y=0; $y < self::$imageHeight; $y++) { 
            for ($x=0; $x < self::$imageWidth; $x++) { 
                $color = self::$image->pickColor($x, $y);
                unset($color[3]);
                if ((array_sum($color) / 3) <= $treshold) {
                    self::$canvas->pixel('#000', $x, $y);
                }
            }
        }
    }
}
