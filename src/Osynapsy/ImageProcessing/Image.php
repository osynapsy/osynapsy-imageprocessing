<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\ImageProcessing;

class Image
{
    private $image;
    private $path;
    private $info = [];
    const IMAGE_FACTORY = [
        1 => 'imageCreateFromGif',
        2 => 'imageCreateFromJpeg',
        3 => 'imageCreateFromPng',
        6 => 'imageCreateFromBmp'
    ];

    public function __construct($path = null)
    {
        if (!is_null($path)) {
            $this->initImage($path);
        }
    }

    protected function initImage($path)
    {
        $this->path = $path;
        list($this->image, $this->info) = $this->imageFromFileFactory($path);
    }

    protected function imageFromFileFactory($path)
    {
        $info = getimagesize($path);
        if (!array_key_exists($info[2], self::IMAGE_FACTORY)) {
            throw new \Exception("Image type not allowed");
        }
        $factoryFunction = self::IMAGE_TYPE_FACTORY[$info[2]];
        $image = $factoryFunction($path);
        return [$image, $info];
    }

    public function create($width, $height, $color =  array(), $type = 3)
    {
        $this->image = imagecreatetruecolor($width, $height);
        if (!empty($color)) {
            $col = imagecolorallocate($this->image, $color[0], $color[1], $color[2]);
            imagefill($this->image, 0, 0, $col);
        }
        $this->info = [$width, $height, $type];
    }

    public function crop($x, $y, $cropW, $cropH, $resizeW = null, $resizeH = null, $bgcolor = [255,255,255])
    {
        $imageW = imagesx($this->image);
        $imageH = imagesy($this->image);
        $cropI  = imagecreatetruecolor($cropW , $cropH);
        $color  = imagecolorallocate($cropI, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
        imagefill($cropI, 0, 0, $color);// fill with white
        $destX = $destY = 0;
        if ($x < 0) {
            $destX = abs($x);
            $x = 0;
        }
        if ($y < 0) {
            $destY = abs($y);
            $y = 0;
        }
        if (($imageW + $destX) < $cropW) {
            $cropW = $imageW;
        }
        if (($imageH + $destY) < $cropH) {
            $cropH = $imageH;
        }
        if (!imagecopy($cropI, $this->image, $destX, $destY, $x, $y, $cropW, $cropH)) {
            return false;
        }
        $this->image = $cropI;
        $this->info[0] = $cropW;
        $this->info[1] = $cropH;
        if (!empty($resizeW) && !empty($resizeH)) {
            $this->resize($resizeW, $resizeH, $bgcolor);
        }
        return true;
    }

    public function getDimension()
    {
        return [$this->info[0], $this->info[1]];
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getType()
    {
        return $this->info[2];
    }

    public function merge($path, $x, $y)
    {
        list($source, $sourceDim) = $this->imageFromFileFactory($path);
        imagecopy($this->image, $source, $x, $y, 0, 0, $sourceDim[0], $sourceDim[1]);
    }

    public function resize($newWidth, $newHeight, $bgcolor = array(255, 255, 255))
    {
        $resizedImage = imagecreatetruecolor(intval($newWidth), intval($newHeight));
        if (!empty($bgcolor)) {
            $col = imagecolorallocate($this->image, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
            imagefill($resizedImage, 0, 0, $col);
        }
        imagecopyresampled(
            $resizedImage,
            $this->image,
            0,
            0,
            0,
            0,
            $newWidth ,
            $newHeight,
            $this->info[0],
            $this->info[1]
         );
         $this->image = $resizedImage;
         $this->info[0] = $newWidth;
         $this->info[1] = $newHeight;
    }

    public function resizeAdaptive($newWidth, $newHeight)
    {
        $oldFormFactor = $this->info[0] / $this->info[1];
        $newFormFactor = $newWidth / $newHeight;
        if ($oldFormFactor == $newFormFactor) {
            $this->resize($newWidth, $newHeight);
            return;
        }
        if ($this->info[0] > $this->info[1]) {
            $newHeight = $newHeight / $oldFormFactor;
        } else {
            $newWidth = $newWidth * $oldFormFactor;
        }
        $this->resize($newWidth, $newHeight);
    }

    public function save($path)
    {
        $dirpath = dirname($path);
        if (!file_exists($dirpath)) {
            mkdir($dirpath, 0777, true);
        }
        imagepng($this->image, $path);
        $this->path = $path;
    }
}
