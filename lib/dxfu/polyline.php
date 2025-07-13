<?php
namespace dxfu;

class polyline
{
    public $layer = 'default';
    public $points = [];


    public function isClosed()
    {
        $firstPoint = $this->points[0];
        $lastPoint = $this->points[count($this->points)-1];
        if ($firstPoint->x==$lastPoint->x && $lastPoint->y==$firstPoint->y) {
            return true;
        }
        return false;
    }
}
