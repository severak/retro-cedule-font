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
            if ($feature['geometry']['type']=='LineString') {
                //echo '---' . PHP_EOL;
                $polyline = new polyline();
                foreach ($feature['geometry']['coordinates'] as $coord) {
                    //echo 'in ' . $coord[0] . ', ' . $coord[1] . PHP_EOL;
                    $coord = $this->wgs84toPx($coord[1], $coord[0], $zoom);
                    //echo 'out ' . $coord[0] . ', ' . $coord[1] . PHP_EOL;
                    $point = new point();
                    $point->x = $coord[0];
                    $point->y = $coord[1] * -1;
                    $polyline->points[] = $point;
                }

                $drawing->entities[] = $polyline;
            }

            if ($feature['geometry']['type']=='Polygon') {
                $polyline = new polyline();
                // we are outputting just exterior polygon ring, sorry
                foreach ($feature['geometry']['coordinates'][0] as $coord) {
                    $coord = $this->wgs84toPx($coord[1], $coord[0], $zoom);
                    //echo 'out ' . $coord[0] . ', ' . $coord[1] . PHP_EOL;
                    $point = new point();
                    $point->x = $coord[0];
                    $point->y = $coord[1] * -1;
                    $polyline->points[] = $point;
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

        foreach (['HEADER', 'TABLES', 'BLOCKS'] as $emptySectionName) {
            $out[] = '  0';
            $out[] = 'SECTION';
            $out[] = '  2';
            $out[] = $emptySectionName;
            $out[] = '  0';
            $out[] = 'ENDSEC';
        }

        $out[] = '  0';
        $out[] = 'SECTION';
        $out[] = '  2';
        $out[] = 'ENTITIES';

        foreach ($drawing->entities as $entity) {
            if ($entity instanceof line) {
                $out[] = '  0';
                $out[] = 'LINE';
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
