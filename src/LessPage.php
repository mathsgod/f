<?php
namespace F;

class LessPage extends \R\Page
{
    public function get($sourceMap)
    {
        $route = $this->request->getAttribute("route");

        $options = [
            "sourceMap" => $sourceMap
        ];
        $parser = new \Less_Parser($options);
        foreach (glob(dirname($route->file) . "/*.less") as $f) {
            $parser->parseFile($f);
        }

        $css = $parser->getCss();
        header('Content-Type:text/css');
        $this->write($css);

    }

}