<?
namespace F;

class App extends \R\App
{
    public $language = [];
    public $language_db_map;
    public $language_local_map;
    public $country = [];

    public $current_language;
    public $current_country;

    public function __construct($root)
    {
        parent::__construct($root);

        $this->language = $this->config["language"]["value"];
        $this->language_db_map = $this->config["language_db_map"];
        $this->language_locale_map = $this->config["language_locale_map"];

        $uri = $this->request->getURI();
        $path = explode("/", $uri->getPath());
        $path = array_values(array_filter($path, strlen));
        if (in_array($path[0], $this->language)) {
            $this->current_language = array_shift($path);
            $uri = $uri->withPath(implode("/", $path));
            $this->request = $this->request->withUri($uri);
        }

        $path = explode("/", $this->request->getURI()->getPath());

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

        $this->current_country = explode("-", $this->current_language, 2)[1];

        setlocale(LC_ALL, $this->language_locale_map[$this->current_language]);
    }

    public function findTemplate($file)
    {
        $pi = pathinfo($file);

        $file = $pi["dirname"] . "/" . $pi["filename"];
        if (is_readable($template_file = $file . ".twig")) {

            if (!$config = $this->config["twig"]) {
                $config = [];
            }
            $root = $this->root;

            array_walk($config, function (&$o) use ($root) {
                $o = str_replace("{root}", $root, $o);
            });

            $twig["loader"] = new \Twig_Loader_Filesystem($root);
            $twig["environment"] = new \Twig_Environment($twig["loader"], $config);
            $twig["environment"]->addExtension(new \Twig_Extensions_Extension_I18n());
            $twig["environment"]->addGlobal("lang", $this->current_language);

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

            $uri = substr($template_file, strlen($root) + 1);
            return $twig["environment"]->loadTemplate($uri);
        }
    }


    public function get($uri)
    {
        return $this->request("GET", $uri);
    }

    public function request($method, $uri)
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

    public function country()
    {
        return $this->current_country;
    }

    public function language()
    {
        return $this->current_language;
    }

    public function v()
    {
        return $this->language_db_map[$this->current_language];
    }

    public function redirect($url = null)
    {
        if (headers_sent()) {
            echo "Header sent";
            die();
        }
        if ($this->language() == $this->language[0]) {
            header("location: /{$url}");
        } else {
            $lang = $this->language();
            header("location: /{$lang}/{$url}");
        }

        exit();
    }

  

/*    public static function URI()
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
    }*/


}