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

        $out[] = '  0';
        $out[] = 'ENDSEC';

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
                // TODO - HATCH
                // https://github.com/dotoritos-kim/dxf-json/blob/main/src/parser/entities/hatch/index.ts
                // https://help.autodesk.com/view/OARX/2024/ENU/?guid=GUID-C6C71CED-CE0F-4184-82A5-07AD6241F15B

                /**
                0 - HATCH
                5 - 2E // handle?
                100 - AcDbEntity
                67 -0 // ?
                8 - 0 // layer
                6 - ByLayer // line type
                62 - 256 // color
                370 - -1 // Lineweight enum value. Stored and moved around as a 16-bit integer.
                100 - AcDbHatch
                10 - 0.000000 //Elevation point (in OCS)
                20 - 0.000000
                30 - 0.000000
                210 - 0.000000 // extrusion
                220 - 0.000000
                230 - 1.000000
                2 - USER_DEF
                70 - 0 // Solid fill flag (0 = pattern fill; 1 = solid fill); for MPolygon, the version of MPolygon
                71 - 0 // Associativity flag (0 = non-associative; 1 = associative); for MPolygon, solid-fill flag (0 = lacks solid fill; 1 = has solid fill)
                91 - 1 // Number of boundary paths (loops)
                92 - 0 //
                93 - 3
                72 - 1

                ; layers
                10 - 10.000000
                20 - 20.000000
                11 -0.000000
                21 - 10.000000
                72 - 1
                10 - 0.000000
                20 - 10.000000
                11 - 10.000000
                21 - 10.000000
                72 - 1
                10
                10.000000
                20
                10.000000
                11
                10.000000
                21
                20.000000
                97
                0
                75
                0
                76
                0
                52
                0.000000
                41
                1.000000
                77
                0
                78
                1
                53
                45.000000
                43
                0.000000
                44
                0.000000
                45
                -0.707107
                46
                0.707107
                79
                0
                98
                0
                 */

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
