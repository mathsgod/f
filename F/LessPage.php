<?php
namespace F;

class LessPage extends \R\Page
{
    public function get()
    {
        $route = $this->request->getAttribute("route");

        $parser = new \Less_Parser();
        foreach (glob(dirname($route->file) . "/*.less") as $f) {
            $parser->parseFile($f);
        }

        $css = $parser->getCss();
        header('Content-Type:text/css');
        $this->write($css);

    }

}