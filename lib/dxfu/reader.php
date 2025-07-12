<?php
namespace dxfu;
class reader
{
    public function read($filename)
    {
        $drawing = new drawing();

        $obj = new \stdClass();

        $file = new \SplFileObject($filename);
        while ($groupCode = $file->fgets()) {
            // TODO - track sections
            // and in HEADER fill $drawing->headerVariables

            // Apparently - DXF files are just pairs of keys and values.
            // Keys are so called group codes and these are numbered.

            // Values are whatever fits the need.
            $groupVal = trim($file->fgets());

            // Group code 0 is name/kind of new object/entity/section/whatever.
            if ($groupCode == 0) {
                if (!$obj instanceof \stdClass) {
                    // if previous object was something diferent than stdClass, push it to data
                    $drawing->entities[] = $obj;
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

        $drawing->updateBbox();

        return $drawing;
    }
}
