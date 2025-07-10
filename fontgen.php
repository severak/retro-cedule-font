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
     * Test parsing of glyph.
     *
     * @param string $filename Filename of glyph to parse.
     *
     * @return void
     */
    public function glyphtest($filename)
    {
        $reader = new dxfu\reader();
        $reader->read($filename);

        echo 'OK ' . count($reader->data) . ' entities' . PHP_EOL;
        echo sprintf('bbox (%f,%f; %f,%f)', $reader->minx, $reader->miny, $reader->maxy, $reader->maxy) . PHP_EOL;

        $gd = imagecreate(300, 700);
        $white = imagecolorallocate($gd, 255,255,255);
        $black = imagecolorallocate($gd, 0,0,0);
        foreach ($reader->data as $obj) {
            if ($obj instanceof \dxfu\line) {
                echo sprintf('line %f,%f -> %f,%f', $obj->ax, ($obj->ay), $obj->bx, ($obj->by)). PHP_EOL;
                imageline($gd,  $obj->ax, ($obj->ay), $obj->bx, ($obj->by), $black);
            }
        }
        imagepng($gd, 'glyphtest.png');
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
