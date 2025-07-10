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

        $gd = imagecreate(300, 700);
        $white = imagecolorallocate($gd, 255,255,255);
        $black = imagecolorallocate($gd, 0,0,0);
        foreach ($reader->data as $obj) {
            if ($obj instanceof \dxfu\line) {
                imageline($gd, $obj->ax, $obj->ay, $obj->bx, $obj->by, $black);
            }
        }
        imagepng($gd, 'glyphtext.png');
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
