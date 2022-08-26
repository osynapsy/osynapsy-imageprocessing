<?php

/*
 * This file is part of the Osynapsy package.
 *
 * (c) Pietro Celeste <p.celeste@osynapsy.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Osynapsy\ImageProcessing\Action;

use Osynapsy\ImageProcessing\Image;

/**
 * Description of CropTrait
 *
 * @author Pietro Celeste <p.celeste@spinit.it>
 */
class Crop
{
    private $db;
    private $table;
    private $field;
    private $where;
    private $targetFile;
    private $pathinfo = [];

    public function __construct($db, $table, $field, array $where)
    {
        $this->db = $db;
        $this->table = $table;
        $this->field = $field;
        $this->where = $where;
        $this->targetFile = $this->db->selectOne($table, $where, [$field], \PDO::FETCH_NUM);
        if (empty($this->targetFile)) {
            return;
        }
        $this->pathinfo = pathinfo($this->targetFile);
    }

    public function cropAction($cropWidth, $cropHeight, $cropX, $cropY, $filename, $newWidth = null, $newHeight = null)
    {
        $img = new Image('.'.$this->targetFile);
        $img->crop($cropX, $cropY, $cropWidth, $cropHeight);
        if (!empty($filename) && $filename[0] !== '/') {
            $filename = $this->pathinfo['dirname'].'/'.$filename.'.'.$this->pathinfo['extension'];
        }
        if (!empty($newWidth) && !empty($newHeight)) {
            $img->resize($newWidth, $newHeight);
        }
        $img->save('.'.$filename);
        $this->updateRecord($filename);
    }

    public function deleteImageAction()
    {
        if (empty($this->targetFile)) {
            return;
        }
        unlink('.'.$this->targetFile);
        $this->updateRecord(null);
    }

    public function updateRecord($filename)
    {
        $this->db->update($this->table, [$this->field => $filename], $this->where);
    }

    public function getTarget()
    {
        return $this->targetFile;
    }

    public function getInfo($key)
    {
        if (array_key_exists($key, $this->pathinfo)) {
            return $this->pathinfo[$key];
        }
    }
}
