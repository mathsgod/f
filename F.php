<?php
class F
{ 
    public static $Lang;
    public static $CurrentLang;
    public static $CurrentCountry;

    public static function Request($uri)
    {
        $req = F\System::Request($uri);
        return $req;
    }

    private static $Redirect = false;
    public static function Redirect($url = null)
    {
        $f = F\System::$f;

        if (headers_sent()) {
            echo "Header sent";
            die();
        }
        if (F::Lang() == $f->language[0]) {
            header("location: /{$url}");
        } else {
            $lang = F::Lang();
            header("location: /{$lang}/{$url}");
        }

        exit();
    }

    public static function URI()
    {
        $f = F\System::$f;
        list($prefix, $lang, $path) = explode("/", $_SERVER["REQUEST_URI"], 3);
        if (in_array($lang, $f->language)) {
            if ($path[0] != "/") {
                $path = "/" . $path;
            }
            return new R\PURL($path);
        } else {
            $uri = $_SERVER["REQUEST_URI"];
            if ($uri == "") {
                $uri = "/";
            }
            return new R\PURL($uri);
        }
    }

    public static function ID()
    {
        $uri = $_SERVER["REQUEST_URI"];
        foreach (explode("/", $uri) as $i) {
            if (is_numeric($i)) {
                return $i;
            }
        }
        return null;
    }

    public static function V()
    {
        $f = F\System::$f;
        return $f->language_db_map[$f->current_language];
    }

    public static function Lang()
    {
        $f = F\System::$f;
        return $f->current_language;
    }

    public static function Locale()
    {
        return setlocale(LC_ALL, 0);
    }

    public static function Config($category = null, $name = null)
    {
        $config = F\System::$f->config;
        if (isset($name)) {
            return $config[$category][$name];
        } elseif (isset($category)) {
            return $config[$category];
        }
        return $config;
    }

    public static function Country()
    {
        return F\System::$f->current_country;
    }
}
