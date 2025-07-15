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
                //echo $feature['geometry']['type'] . ' not supported yet' . PHP_EOL;
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

    public function export(drawing $drawing, $filename, $useHatches, $useTables)
    {
        // TODO - this should be in own class
        $out = [];
        $out[] = '999';
        $out[] = 'Export by DXFu';

        if ($useTables) {

            $out[] = '  0';
            $out[] = 'SECTION';
            $out[] = '  2';
            $out[] = 'HEADER';
            $out[] = '  0';
            $out[] = 'ENDSEC';

            // tables section
            $out[] = '  0';
            $out[] = 'SECTION';
            $out[] = '  2';
            $out[] = 'TABLES';

            // LTYPE table (static one to keep CadZinho happy)
            array_push($out, 0, 'TABLE');
            array_push($out, 2, 'LTYPE');

            // continuous line type (static)
            array_push($out, 0, 'LTYPE');
            array_push($out, 100, 'AcDbSymbolTableRecord');
            array_push($out, 100, 'AcDbLinetypeTableRecord');
            array_push($out, 2, 'Continuous'); // name
            array_push($out, 70, 0); // flag
            array_push($out, 3, '___________________________________'); // description
            array_push($out, 72, 65); // alingment A
            array_push($out, 73, 0); // number of dashes - none
            array_push($out, 40, 0); // pattern length - none
            array_push($out, 0, 'ENDTAB'); // end of LTYPE table

            $layernames = [];
            foreach ($drawing->entities as $entity) {
                if (!in_array($entity->layer, $layernames)) {
                    echo 'layer ' . $entity->layer . PHP_EOL;
                    $layernames[] = $entity->layer;
                }
            }

            // LAYER TABLE
            array_push($out, 0, 'TABLE');
            array_push($out, 2, 'LAYER');

            foreach ($layernames as $name) {
                array_push($out, 0, 'LAYER');
                // array_push($out, 5, 11); // handle
                array_push($out, 100, 'AcDbSymbolTableRecord');
                array_push($out, 100, 'AcDbLayerTableRecord');
                array_push($out, 2, $name);
                array_push($out, 70, 0); // flag
                // array_push($out, 62, 0); // color number
                array_push($out, 6, 'Continuous'); // line type

            }

            array_push($out, 0, 'ENDTAB'); // end of LAYER table

            // STYLE (FONT) TABLE
            array_push($out, 0, 'TABLE');
            array_push($out, 2, 'STYLE');

            // dummy text style
            array_push($out, 0, 'STYLE');
            array_push($out, 2, 'dummy');
            array_push($out, 40, 0); // not fixed height
            array_push($out, 41, 1); // width
            array_push($out, 50, 0); // Oblique angle
            array_push($out, 71, 0); // Text generation flags:
            array_push($out, 42, 10); // Last height used
            array_push($out, 3, 'Arial'); // Primary font file name
            array_push($out, 4, 0); // Bigfont file name; blank if none
            // array_push($out, 42, 0); //


            array_push($out, 0, 'ENDTAB'); // end of STYLE table

            $out[] = '  0';
            $out[] = 'ENDSEC';

        }

        // entities section
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
                // HATCH is not working (in CadZinho) yet, IDKY why
                // https://help.autodesk.com/view/OARX/2024/ENU/?guid=GUID-C6C71CED-CE0F-4184-82A5-07AD6241F15B

                if ($entity->isClosed() && $useHatches) {
                    array_push($out, 0, 'HATCH');
                    array_push($out, 8, $entity->layer);
                    //array_push($out, 76, 1); // predefined hatch
                    //array_push($out, 2, 'ANSI31'); // Hatch pattern name

                    array_push($out, 70, 1); // solid fill flag
                    array_push($out, 91, 1); // Number of boundary paths (loops)
                    array_push($out, 92, 2); // boundary = polyline
                    array_push($out, 93, count($entity->points)); // number of edges in this path

                    foreach ($entity->points as $point) {
                        array_push($out, 10, $point->x);
                        array_push($out, 20, $point->y);
                    }
                    array_push($out, 73, 1); // is closed
                } else {
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
        }

        $out[] = '  0';
        $out[] = 'ENDSEC';
        $out[] = '  0';
        $out[] = 'EOF';

        file_put_contents($filename, implode(PHP_EOL, $out));
    }
}
