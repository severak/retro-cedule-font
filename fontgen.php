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
        echo 'Not yet implemented!';
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
