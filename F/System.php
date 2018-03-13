<?php
namespace F;

use R\Psr7\ServerRequest;
use R\Psr7\Response;

class System extends \R\System
{
    public static $base;
    public static $route;
    public static $f;

    public $language_db_map;
    public $language;
    public $current_language;
    public static $CurrentLang;
    public $current_country;
    public $country = [];

    public function __construct($root, $loader)
    {
        parent::__construct($root, $loader);

        $request = ServerRequest::FromEnv();

        $path = explode("/", $request->getURI()->getPath());

        $this->language = $this->config["language"]["value"];
        $this->language_db_map = $this->config["language_db_map"];
        $this->language_locale_map = $this->config["language_locale_map"];


        //get country
        foreach ($this->language as $lang) {
            $s = explode("-", $lang, 2);
            if ($country = $s[1]) {
                $this->country[] = $country;
            }
        }
        $this->country = array_unique($this->country);

        if (in_array($path[0], $this->language)) {
            $this->current_language = $path[0];
        } else {
            $this->current_language = $this->language[0];
        }

        $this->current_country=explode("-",$this->current_language,2)[1];

        self::$f = $this;
        setlocale(LC_ALL, $this->language_locale_map[$this->current_language]);

    }

    public static function ServerRequest()
    {
        $request = ServerRequest::FromEnv();
        $uri = $request->getURI();

        $language = self::$r->config("language", "value");

        $path = explode("/", $uri->getPath());
        $path = array_values(array_filter($path, strlen));
        if (in_array($path[0], self::$r->config("language", "value"))) {
            self::$CurrentLang = array_shift($path);
            $uri = $uri->withPath(implode("/", $path));
            return $request->withUri($uri);
        }

        return $request;
    }

    public static function Request($uri, $method = "GET")
    {
        $url = parse_url($uri);
        $request = static::ServerRequest();

        $u = $request->getURI()->withPath($url["path"]);

        if ($url["query"]) {
            $u = $u->withQuery($url["query"]);
        } else {
            $u = $u->withQuery("");
        }

        $r = $request->withUri($u)->withMethod($method);

        $router = static::Router();

        $route = $router->getRoute($r, System::Loader());

        if ($class = $route->class) {
            $page = new $class;
            return $page($request, new Response(200));
        }

        return null;
    }

    public static function Router()
    {
        return new Router();
    }


    public static function FindTemplate($file)
    {
        $pi = pathinfo($file);

        $file = $pi["dirname"] . "/" . $pi["filename"];
        if (is_readable($template_file = $file . ".twig")) {

            if (!$config = \F::Config("twig")) {
                $config = [];
            }
            $root = self::Root();

            array_walk($config, function (&$o) use ($root) {
                $o = str_replace("{root}", $root, $o);
            });

            $twig["loader"] = new \Twig_Loader_Filesystem(self::Root());
            $twig["environment"] = new \Twig_Environment($twig["loader"], $config);
            $twig["environment"]->addExtension(new \Twig_Extensions_Extension_I18n());
            $twig["environment"]->addGlobal("lang", \F::Lang());

            $function = new \Twig_SimpleFunction('_', function ($a, $b) {
                $name = $b . "_" . \F::V();
                if (is_object($a)) {
                    return $a->$name;
                } elseif (is_array($a)) {
                    return $a[$name];
                }
                return $b;
            });
            $twig["environment"]->addFunction($function);

            $uri = substr($template_file, strlen(self::Root()) + 1);
            return $twig["environment"]->loadTemplate($uri);
        }
    }
}
