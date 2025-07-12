<?php
namespace dxfu;

class geojson
{
    public function import($fname, $zoom)
    {
        $geojson = file_get_contents($fname);
        $geojson = json_decode($geojson, true);

        $drawing = new drawing();

        foreach ($geojson['features'] as $feature) {
            $coords = [];
            if ($feature['geometry']['type']=='LineString') {
                $coords = $feature['geometry']['coordinates'];
            } elseif ($feature['geometry']['type']=='Polygon') {
                $coords = $feature['geometry']['coordinates'][0];
            } else {
                echo $feature['geometry']['type'] . ' not supported yet' . PHP_EOL;
            }

            if (count($coords) > 0) {
                $polyline = new polyline();
                foreach ($coords as $coord) {
                    //echo 'in ' . $coord[0] . ', ' . $coord[1] . PHP_EOL;
                    $coord = $this->wgs84toPx($coord[1], $coord[0], $zoom);
                    //echo 'out ' . $coord[0] . ', ' . $coord[1] . PHP_EOL;
                    $point = new point();
                    $point->x = $coord[0];
                    $point->y = $coord[1] * -1;
                    $polyline->points[] = $point;
                }

                if (function_exists('set_layer')) {
                    $polyline->layer = set_layer($feature['properties']);
                }

                $drawing->entities[] = $polyline;
            }
        }

        return $drawing;
    }

    // based on https://msdn.microsoft.com/en-us/library/bb259689.aspx

    public function wgs84toPx($lat, $lon, $zoom)
    {
        $sinLat = sin($lat * M_PI/180);
        $x = ( ($lon + 180) / 360) * 256 * (2 ** $zoom);
        $y = (0.5 - log( (1+$sinLat) / (1-$sinLat)) / (4*M_PI) ) * (256 * 2 ** $zoom);
        return [$x, $y];
    }

    public function export(drawing $drawing, $filename)
    {
        // TODO - this should be in own class
        $out = [];
        $out[] = '999';
        $out[] = 'Export by DXFu';

        // TODO - we should define at least layers, linetypes and colors make CadZinho happy

        /*
        foreach (['HEADER', 'TABLES', 'BLOCKS'] as $emptySectionName) {
            $out[] = '  0';
            $out[] = 'SECTION';
            $out[] = '  2';
            $out[] = $emptySectionName;
            $out[] = '  0';
            $out[] = 'ENDSEC';
        }
        */

        $out[] = '  0';
        $out[] = 'SECTION';
        $out[] = '  2';
        $out[] = 'ENTITIES';

        foreach ($drawing->entities as $entity) {
            if ($entity instanceof line) {
                $out[] = '  0';
                $out[] = 'LINE';
                $out[] = '  8';
                $out[] = $entity->layer;
                $out[] = ' 10';
                $out[] = $entity->ax;
                $out[] = ' 20';
                $out[] = $entity->ay;
                $out[] = ' 11';
                $out[] = $entity->bx;
                $out[] = ' 21';
                $out[] = $entity->by;
            }

            if ($entity instanceof polyline) {
                $out[] = '  0';
                $out[] = 'LWPOLYLINE';
                $out[] = '  8';
                $out[] = $entity->layer;
                foreach ($entity->points as $point) {
                    $out[] = ' 10';
                    $out[] = $point->x;
                    $out[] = ' 20';
                    $out[] = $point->y;
                }
            }
        }

        $out[] = '  0';
        $out[] = 'ENDSEC';
        $out[] = '  0';
        $out[] = 'EOF';

        file_put_contents($filename, implode(PHP_EOL, $out));
    }
}
