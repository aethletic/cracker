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

    /**
     * Create Crack instance.
     *
     * @param string $image Can be File or Url
     */
    public function __construct($image)
    {
        $this->manager = new ImageManager([
            'driver' => 'imagick',
        ]);

        $this->file = $image;
    }

    /**
     * Set count of iteration. 
     *
     * @param string|int $count
     * @return Crack
     */
    public function iterations($count)
    {
        $this->iterations = (int) $count;
        return $this;
    }

    /**
     * Set writable folder for temp files.
     *
     * @param string $path
     * @return Crack
     */
    public function temp($path)
    {
        $this->storage = rtrim($path, '\/');
        return $this;
    }

    /**
     * Set Tesseract lang (model).
     *
     * @param string $model
     * @return Crack
     */
    public function model($model = 'eng')
    {
        $this->model = $model;
        return $this;
    }

    /**
     * Set proxy.
     * socks5://user:password@ip:port
     *
     * @param string $proxy
     * @return Crack
     */
    public function proxy($proxy)
    {
        $this->proxy = $proxy;
        return $this;
    }

    /**
     * Set executable Tesseract OCR.
     *
     * @param boolean $executable
     * @return Crack
     */
    public function executable($executable = false)
    {
        $this->executable = $executable;
        return $this;
    }
    
    /**
     * Set tessdata folder.
     *
     * @param string|boolean $tessdata
     * @return Crack
     */
    public function tessdata($tessdata = false)
    {
        $this->tessdata = $tessdata;
        return $this;
    }

    /**
     * Convert image and OCR resolve.
     *
     * @return string|bool
     */
    private function iteration()
    {
        if (is_file($this->file)) {
            $this->image = $this->manager->make($this->file);
        } else {
            if (!$this->proxy) {
                $this->image = $this->manager->make($this->file);
            } else {
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

    /**
     * Resolve captcha.
     *
     * @param boolean $returnArray Return array with detail info or only string of resolved captcha text.
     * @return array|string 
     */
    public function resolve($returnArray = false)
    {
        if (!$this->storage || !file_exists($this->storage)) {
            throw new \Exception("Missed `storage` path, set `storage` before resolve.");
        }

        $start = microtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
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
                    $this->captchas['chars'][$key][$char] = 1;
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
