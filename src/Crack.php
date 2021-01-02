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
    private $tessdata = false;
    private $proxy = false;

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

    public function proxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    public function ocr($executable = false)
    {
        $this->executable = $executable;
        return $this;
    }

    public function data($tessdata = false)
    {
        $this->tessdata = $tessdata;
        return $this;
    }

    private function iteration() {
        if (is_file($this->file)) {
            $this->image = $this->manager->make($this->file);
        } else {
            if (!$this->proxy) {
                $this->image = $this->manager->make($this->file);
            } else{
                $client = new \GuzzleHttp\Client();

                try {
                    $res = $client->request("GET", $this->file, [
                        'proxy' => $this->proxy,
                        'timeout' => 5,
                    ]);
        
                    $this->image = $this->manager->make($res->getBody());
                } catch (\Throwable $th) {
                    return false;
                }
            }
        }

        Image::convert($this->image, $this->manager)
            ->save("{$this->storage}/resolve.gif");

        $ocr = (new TesseractOCR("{$this->storage}/resolve.gif"))
            ->lang($this->model)
            ->allowlist(array_merge(range('A', 'Z'), range(0, 9), ['%', '@', '&']))
            ->psm(7);

        if ($this->executable) {
            $ocr->executable($this->executable); 
        }

        if ($this->tessdata) {
            $ocr->tessdataDir($this->tessdata); 
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
                'captcha' => '',
                'time' => round(microtime(true) - $start, 4),
            ] : '';
        }

        foreach ($this->captchas['chars'] as $key => $chars) {
            arsort($chars);
            $sortedChars[$key] = $chars;
            $mostUsedChars[$key] = array_key_first($chars);
        }

        $lenght = 6;
        $captcha = implode(array_chunk($mostUsedChars, $lenght)[0]);

        return $returnArray ? [
            'sortedChars' => $sortedChars,
            'mostUsedChars' => $mostUsedChars,
            'captcha' => $captcha,
            'time' => round(microtime(true) - $start, 4),
        ] : $captcha;
    }
}