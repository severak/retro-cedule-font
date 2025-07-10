<?php
namespace dxfu;
class reader
{
    public $data = [];

    public function read($filename)
    {
        $obj = new \stdClass();

        $file = new \SplFileObject($filename);
        while ($groupCode = $file->fgets()) {
            $groupVal = trim($file->fgets());

            if ($groupCode == 0) {
                if (!$obj instanceof \stdClass) {
                    //echo 'push obj' . PHP_EOL;
                    $this->data[] = $obj;
                    $obj = new \stdClass();
                }

                if ($groupVal == 'LINE') {
                    $obj = new line();
                    //echo 'zakladem linku' . PHP_EOL;
                }
            }

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
    }
}
