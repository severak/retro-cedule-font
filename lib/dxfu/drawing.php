<?php
namespace dxfu;

class drawing
{
    public $headerVariables = [];
    public $entities = [];
    public $minx = 0;
    public $maxx = 0;
    public $miny = 0;
    public $maxy = 0;

    public $width = 0;
    public $height = 0;

    public function updateBbox()
    {
        foreach ($this->entities as $obj) {
            if ($obj->ax < $this->minx) $this->minx = $obj->ax;
            if ($obj->ay < $this->miny) $this->miny = $obj->ay;
            if ($obj->ax > $this->maxx) $this->maxx = $obj->ax;
            if ($obj->ay > $this->maxy) $this->maxy = $obj->ay;

            if (isset($obj->bx) && isset($obj->by)) {
                if ($obj->bx < $this->minx) $this->minx = $obj->bx;
                if ($obj->by < $this->miny) $this->miny = $obj->by;
                if ($obj->bx > $this->maxx) $this->maxx = $obj->bx;
                if ($obj->by > $this->maxy) $this->maxy = $obj->by;
            }
        }

        $this->width = $this->maxx - $this->minx;
        $this->height = $this->maxy - $this->miny;
    }

    public function flipY($y)
    {
        return $this->maxy - $y;
    }

    public function flipX($x)
    {
        return $x - $this->minx;
    }
}
