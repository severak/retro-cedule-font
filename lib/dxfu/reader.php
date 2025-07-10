<?php
namespace dxfu;
class reader
{
    public $data = [];
    public $minx = 0;
    public $maxx = 0;
    public $miny = 0;
    public $maxy = 0;

    public function read($filename)
    {
        $obj = new \stdClass();

        $file = new \SplFileObject($filename);
        while ($groupCode = $file->fgets()) {
            // Apparently - DXF files are just pairs of keys and values.
            // Keys are so called group codes and these are numbered.

            // Values are whatever fits the need.
            $groupVal = trim($file->fgets());

            // Group code 0 is name/kind of new object/entity/section/whatever.
            if ($groupCode == 0) {
                if (!$obj instanceof \stdClass) {
                    // if previous object was something diferent than stdClass, push it to data
                    $this->data[] = $obj;
                    $obj = new \stdClass();
                }

                if ($groupVal == 'LINE') {
                    $obj = new line();
                }
            }

            // this attributes can be written in more clever way but hey this works for now
            if ($groupCode == 8) {
                $obj->layer = $groupVal;
            }
            if ($groupCode == 10) {
                $obj->ax = floatval($groupVal);
            }
            if ($groupCode == 20) {
                $obj->ay = floatval($groupVal);
            }
            if ($groupCode == 11) {
                $obj->bx = floatval($groupVal);
            }
            if ($groupCode == 21) {
                $obj->by = floatval($groupVal);
            }
        }

        // get bbox of whole file
        foreach ($this->data as $obj) {
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

    }

    public function flipy($y)
    {
        return $this->maxy - $y;
    }

    public function flipx($x)
    {
        return $x - $this->minx;
    }
}
