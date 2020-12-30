<?php 

namespace Cracker;

use Intervention\Image\ImageManager;
use thiagoalessio\TesseractOCR\TesseractOCR;

class Crack 
{
    private $image;
    private $manager;
    private $captchas = [];

    private $iterations = 10;
    private $storage;
    private $model = 'eng';
    private $executable = false;

    public function __construct($image)
    {
        $this->manager = new ImageManager([
            'driver' => 'imagick',
        ]);

        $this->file = $image;
    }

    public function iterations($count)
    {
        $this->iterations = $count;
        return $this;
    }

    public function storage($path)
    {
        $this->storage = rtrim($path, '\/');
        return $this;
    }

    public function model($model = 'eng')
    {
        $this->model = $model;
        return $this;
    }

    public function ocr($executable = false)
    {
        $this->executable = $executable;
        return $this;
    }

    private function iteration() {
        $this->image = $this->manager->make($this->file);

        Image::convert($this->image, $this->manager)
            ->save("{$this->storage}/resolve.gif");

        $ocr = (new TesseractOCR("{$this->storage}/resolve.gif"))
            ->tessdataDir(__DIR__ . '/../')
            ->lang($this->model)
            ->allowlist(array_merge(range('A', 'Z'), range(0, 9), ['%', '@', '&']))
            ->psm(7);

        if ($this->executable) {
            $ocr->executable($this->executable); 
        }

        $text = $ocr->run(); 

        unlink("{$this->storage}/resolve.gif");

        return trim($text) !== '' ? $text : false;
    }

    public function resolve($returnArray = false)
    {
        if (!$this->storage || !file_exists($this->storage)) {
            throw new \Exception("Missed `storage` path, set `storage` before resolve.");
        }

        $start = microtime(true);

        for ($i=0; $i < $this->iterations; $i++) { 
            $result = $this->iteration();

            if (!$result) {
                continue;
            }

            $chars = array_filter(str_split(preg_replace('/\s+/', '', $result)));
          
            $this->captchas['count'][] = count($chars);

            foreach ($chars as $key => $char) {
                if (isset($this->captchas['chars'][$key]) && array_key_exists($char, $this->captchas['chars'][$key])) {
                    $this->captchas['chars'][$key][$char]++; 
                } else {
                    $this->captchas['chars'][$key][$char]= 1; 
                }
            }
        }

        $mostUsedChars = [];
        $sortedChars = [];

        if ($this->captchas === []) {
            return $returnArray ? [
                'sortedChars' => false,
                'mostUsedChars' => false,
                'lenght' => false,
                'captcha' => '',
                'time' => round(microtime(true) - $start, 4),
            ] : '';
        }

        foreach ($this->captchas['chars'] as $key => $chars) {
            arsort($chars);
            $sortedChars[$key] = $chars;
            $mostUsedChars[$key] = array_key_first($chars);
        }

        // $lenght = head(collect($this->captchas['count'])->mode());
        $lenght = 6;
        $captcha = implode(head(array_chunk($mostUsedChars, $lenght)));

        return $returnArray ? [
            'counts' => $this->captchas['count'],
            'sortedChars' => $sortedChars,
            'mostUsedChars' => $mostUsedChars,
            'lenght' => $lenght,
            'captcha' => $captcha,
            'time' => round(microtime(true) - $start, 4),
        ] : $captcha;
    }
}