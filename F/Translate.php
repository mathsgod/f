<?php
namespace F;
class Translate {
    public static function _($filename, $lang) {
        $content = file_get_contents($filename);
        return self::Replace($content, $lang);
    }

    public static function Template($filename, $lang) {
        $t = new \TemplatePower(Translate::_($filename, $lang), T_BYVAR);
        $t->prepare();
        return $t;
    }

    public static function TemplateContent($content, $lang) {
        $content = self::Replace($content, $lang);
        $t = new \TemplatePower($content, T_BYVAR);
        $t->prepare();
        return $t;
    }

    private static function Replace($content, $language) {
        $ll = $language;
        foreach(System::$Lang as $la) {
            if ($la == $ll) {
                $content = preg_replace("/<:{$la}>([\s\S]*?)<\/:{$la}>/", "$1", $content);
                $content = preg_replace("/<(\w+):{$la}([^>]*)\/>/", "<$1 $2/>", $content);
                $content = preg_replace("/<(\w+):{$la}([^>]*)>([\s\S]*?)<\/(\w+):{$la}>/", "<$1 $2>$3</$4>", $content);
            } else {
                $content = preg_replace("/<:{$la}>[\s\S]*?<\/:{$la}>/", "", $content);
                $content = preg_replace("/<(\w+):{$la}([^>]*)\/>/", "", $content);
                $content = preg_replace("/<(\w+):{$la}([^>]*)>([\s\S]*?)<\/(\w+):{$la}>/", "", $content);
            }
        }
        return $content;
    }
}

?>