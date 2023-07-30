<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\ImageProcessing\Action;

use Osynapsy\ImageProcessing\Image;
use Osynapsy\Action\AbstractAction;
use Osynapsy\Http\Response\JsonOsynapsy;

/**
 * Description of CropTrait
 *
 * @author Pietro Celeste <p.celeste@osynapsy.net>
 */
class Crop extends AbstractAction
{
    public function execute(JsonOsynapsy $Response, $imageUrl, $cropData, $resizeData, $fieldId, $fieldToRefreshId)
    {
        try {
            $filename = $this->getFilepathFromUrl($imageUrl);
            $documentRoot = $_SERVER['DOCUMENT_ROOT'];
            $Img = new Image($documentRoot.$filename);
            $this->crop($Img, $cropData);
            if (!empty($resizeData)) {
                $this->resize($Img, $resizeData);
            }
            $newFilename = $this->buildFilename($filename);
            $Img->save($documentRoot.$newFilename);
            $Response->js(sprintf("document.getElementById('%s').value = '%s'", $fieldId, $newFilename));
            $Response->refreshComponents([$fieldToRefreshId]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    protected function getFilepathFromUrl($imageUrl)
    {
        $urlPart = parse_url($imageUrl);
        return $urlPart['path'];
    }

    protected function crop($imageHandler, $cropData)
    {
        list($cropWidth, $cropHeight, $cropX, $cropY) = explode(',', base64_decode($cropData));
        $imageHandler->crop($cropX, $cropY, $cropWidth, $cropHeight);
    }

    protected function resize($imageHandler, $resizeData)
    {
        list($newWidth, $newHeight) = explode(',', base64_decode($resizeData));
        $imageHandler->resize($newWidth, $newHeight);
    }

    protected function buildFilename($original)
    {
        $pathinfo = pathinfo($original);
        $path = $pathinfo['dirname'];
        $filename = $pathinfo['filename'];
        $extension = $pathinfo['extension'];
        return $path . '/' . $filename . '.crop.' . $extension;
    }
}
