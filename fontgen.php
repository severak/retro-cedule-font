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
}

\severak\cligen\app::run(new fontgen());
