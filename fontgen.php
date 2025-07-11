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
        $reader = new dxfu\reader();
        $reader->read($dxf);

        echo 'OK ' . count($reader->data) . ' entities' . PHP_EOL;
        echo sprintf('bbox (%f,%f; %f,%f)', $reader->minx, $reader->miny, $reader->maxx, $reader->maxy) . PHP_EOL;
        echo sprintf('w=%f h=%f', $reader->maxx - $reader->minx, $reader->maxy - $reader->miny) . PHP_EOL;

        if ($margin>0) {
            echo sprintf('adding margin %d', $margin) . PHP_EOL;
            $reader->minx -= $margin;
            $reader->miny -= $margin;
            $reader->maxx += $margin;
            $reader->maxy += $margin;
        }

        $gd = imagecreate($reader->maxx - $reader->minx, $reader->maxy - $reader->miny);
        // TODO - have colors configurable
        $white = imagecolorallocate($gd, 255,255,255);
        $black = imagecolorallocate($gd, 0,0,0);
        $lightblue = imagecolorallocate($gd, 201, 228, 255);

        if ($grid > 0) {
            // grid rows
            for ($x = 0; $x <= $reader->maxy - $reader->miny; $x = $x + $grid) {
                imageline($gd, 0, $x, $reader->maxx - $reader->minx, $x, $lightblue);
            }
            // grid columns
            for ($y = 0; $y <= $reader->maxx - $reader->minx; $y = $y + $grid) {
                imageline($gd, $y, 0, $y, $reader->maxy - $reader->miny, $lightblue);
            }
        }

        foreach ($reader->data as $obj) {
            if ($obj instanceof \dxfu\line) {
                if ($debug) {
                    echo sprintf('line %f,%f -> %f,%f', $reader->flipx($obj->ax), $reader->flipy($obj->ay), $reader->flipx($obj->bx), $reader->flipy($obj->by)). PHP_EOL;
                }
                imageline($gd,  $reader->flipx($obj->ax), $reader->flipy($obj->ay), $reader->flipx($obj->bx), $reader->flipy($obj->by), $black);
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
