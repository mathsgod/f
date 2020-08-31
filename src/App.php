<?php

namespace F;

use Composer\Autoload\ClassLoader;
use PHP\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;


class App extends \R\App
{
    public $language = [];
    public $language_db_map;
    public $language_local_map;
    public $country = [];

    public $current_language;
    public $current_country;

    public function __construct(string $root, ClassLoader $loader)
    {
        parent::__construct($root, $loader);
        $this->language = $this->config["language"]["value"];
        $this->language_db_map = $this->config["language_db_map"];
        $this->language_locale_map = $this->config["language_locale_map"];

        $uri = $this->request->getURI();
        $path = explode("/", $uri->getPath());
        $path = array_values(array_filter($path, "strlen"));
        if (in_array($path[0], $this->language)) {
            $this->current_language = array_shift($path);
            $uri = $uri->withPath("/" . implode("/", $path));
            $this->request = $this->request->withUri($uri);
        } else {
            $this->current_language = $this->language[0];
        }


        //get country
        foreach ($this->language as $lang) {
            $s = explode("-", $lang, 2);
            if ($country = $s[1]) {
                $this->country[] = $country;
            }
        }
        $this->country = array_unique($this->country);

        $this->current_country = explode("-", $this->current_language, 2)[1];

        setlocale(LC_ALL, $this->language_locale_map[$this->current_language]);

        $path = $this->request->getUri()->getPath();
        $path = substr($path, strlen($this->base_path));
        $uri = $this->request->getUri();
        $uri = $uri->withPath("/" . $path);
        $this->request = $this->request->withUri($uri);
    }

    public function alerts(): array
    {
        $data = [];
        if ($_SESSION["f"]["alert"]) {
            foreach ($_SESSION["f"]["alert"] as $a) {
                $data[] = $a;
            }
        }

        unset($_SESSION["f"]["alert"]);

        return $data;
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

            $twig["loader"] = new \Twig\Loader\FilesystemLoader($root);
            $twig["environment"] = new \Twig\Environment($twig["loader"], $config);
            $twig["environment"]->addExtension(new \Twig_Extensions_Extension_I18n());
            $twig["environment"]->addGlobal("lang", $this->current_language);

            $function = new \Twig\TwigFunction('_', function ($a, $b) {
                $name = $b . "_" . $this->v();
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

    public function get(string $uri)
    {
        return $this->request("GET", $uri);
    }

    public function serverRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function request(string $method, string $uri)
    {
        $url = parse_url($uri);
        $request = $this->serverRequest();

        $u = $request->getURI()->withPath($url["path"]);

        if ($url["query"]) {
            $u = $u->withQuery($url["query"]);
        } else {
            $u = $u->withQuery("");
        }

        $r = $request->withUri($u)->withMethod($method);

        $router = $this->router;

        $route = $router->getRoute($r, $this->loader);

        if ($class = $route->class) {
            $page = new $class($this);
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

    public function twig($file)
    {
        if ($file[0] != "/") {
            $pi = pathinfo($file);
            $file = $pi["dirname"] . "/" . $pi["filename"];
            $template_file = $file . ".twig";
        } else {
            if (file_exists($file)) {
                $template_file = substr($file, strlen($this->root) + 1);
            } elseif (file_exists($this->root . $file)) {
                $template_file = $file;
            }
        }
        $root = $this->root;

        if (is_readable($root . "/" . $template_file)) {

            if (!$config = $this->config["twig"]) {
                $config = [];
            }

            array_walk($config, function (&$o) use ($root) {
                $o = str_replace("{root}", $root, $o);
            });

            $twig["loader"] = new \Twig\Loader\FilesystemLoader(dirname($template_file));
            $twig["environment"] = new \Twig\Environment($twig["loader"], $config);
            $twig["environment"]->addExtension(new \Twig_Extensions_Extension_I18n());


            return $twig["environment"]->loadTemplate(basename($template_file));
        }
    }
}
