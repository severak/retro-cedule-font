<?php

spl_autoload_register(function ($class){
    require __DIR__ . '/lib/' . str_replace('\\', '/', $class) . '.php';
});


/**
 * Generates font files from DXF.
 */
class fontgen
{
    /**
     * Renders DXF file to PNG to check if it's correctly loaded.
     *
     * @param string $dxf    Filename of glyph to parse.
     * @param string $out    Output filename.
     * @param int    $margin Size of image margin.
     * @param int    $grid   Size of grid to draw.
     * @param bool   $debug  Print debug while drawing?
     */
    public function topng($dxf, $out='out.png', $margin=0, $grid=0, $debug=false)
    {
        $reader0 = new dxfu\reader();
        $drawing = $reader0->read($dxf);

        echo 'OK ' . count($drawing->entities) . ' entities' . PHP_EOL;
        echo sprintf('bbox (%f,%f; %f,%f)', $drawing->minx, $drawing->miny, $drawing->maxx, $drawing->maxy) . PHP_EOL;
        echo sprintf('w=%f h=%f', $drawing->maxx - $drawing->minx, $drawing->maxy - $drawing->miny) . PHP_EOL;

        if ($margin>0) {
            echo sprintf('adding margin %d', $margin) . PHP_EOL;
            $drawing->minx -= $margin;
            $drawing->miny -= $margin;
            $drawing->maxx += $margin;
            $drawing->maxy += $margin;
            $drawing->width += ($margin * 2);
            $drawing->height += ($margin * 2);
        }

        $gd = imagecreate($drawing->width, $drawing->height);
        // TODO - have colors configurable
        $white = imagecolorallocate($gd, 255,255,255);
        $black = imagecolorallocate($gd, 0,0,0);
        $lightblue = imagecolorallocate($gd, 201, 228, 255);

        if ($grid > 0) {
            // grid rows
            for ($x = 0; $x <= $drawing->height; $x = $x + $grid) {
                imageline($gd, 0, $x, $drawing->width, $x, $lightblue);
            }
            // grid columns
            for ($y = 0; $y <= $drawing->width; $y = $y + $grid) {
                imageline($gd, $y, 0, $y, $drawing->height, $lightblue);
            }
        }

        foreach ($drawing->entities as $obj) {
            if ($obj instanceof \dxfu\line) {
                if ($debug) {
                    echo sprintf('line %f,%f -> %f,%f', $drawing->flipX($obj->ax), $drawing->flipY($obj->ay), $drawing->flipX($obj->bx), $drawing->flipY($obj->by)). PHP_EOL;
                }
                imageline($gd,  $drawing->flipX($obj->ax), $drawing->flipY($obj->ay), $drawing->flipX($obj->bx), $drawing->flipY($obj->by), $black);
            }
        }
        imagepng($gd, $out);
        echo 'OK complete' . PHP_EOL;
    }

    /**
     * Generates SVG font file.
     *
     * @return void
     */
    public function svgfont()
    {
        echo 'Not yet implemented!';
    }

    /**
     * Converts GEOJSON to DXF. Experimental and very hacky.
     *
     * @param string $geojson Input file.
     * @param string $out     Output file.
     * @param int    $zoom    Zoom for map (as in OSM).
     * @param bool   $hatches Use hatches? (Don't work in all CADs)
     * @param bool   $tables  Include dummy tables?
     */
    public function osm2dxf($geojson, $out='out.dxf', $zoom=14, $hatches=false, $tables=false)
    {
        $importer = new dxfu\geojson();
        $drawing = $importer->import($geojson, $zoom);

        if (function_exists('layer_order')) {
            $oldDrawing = $drawing;
            $drawing = new \dxfu\drawing();
            foreach (layer_order() as $layer) {
                foreach ($oldDrawing->entities as $entity) {
                    if ($entity->layer==$layer) {
                        $drawing->entities[] = $entity;
                    }
                }
            }
        }
        $importer->export($drawing, $out, $hatches, $tables);
    }
}

// TODO - this should be in it's own file
function set_layer($properties)
{
    if (isset($properties['building'])) {
        return 'building';
    }

    if (isset($properties['highway'])) {
        return 'highway';
    }

    if (isset($properties['railway'])) {
        return 'railway';
    }

    if (isset($properties['waterway'])) {
        return 'waterway';
    }

    if (isset($properties['landuse'])) {
        return 'landuse_' . $properties['landuse'];
    }

    if (isset($properties['natural'])) {
        return 'natural_' . $properties['natural'];
    }

    return 'other';
}

function layer_order()
{
$layers = <<<LAYERS
landuse_orchard
landuse_garages
landuse_farmyard
landuse_plant_nursery
landuse_brownfield
landuse_reservoir
landuse_railway
landuse_flowerbed
landuse_forest
landuse_farmland
landuse_meadow
landuse_residential
landuse_quarry
landuse_village_green
landuse_cemetery
landuse_grass
landuse_industrial
landuse_basin
landuse_allotments
landuse_recreation_ground
waterway
natural_water
building
highway
railway
LAYERS;
    return explode(PHP_EOL, $layers);

}

\severak\cligen\app::run(new fontgen());
