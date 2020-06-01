<?php

namespace F;

use R\Psr7\Stream;
use Exception;
use Psr\Http\Message\ResponseInterface;
use R\Psr7\ServerRequest;

class Page extends \R\Page
{
    public $master;
    public $template;
    public $data = [];
    public $alt;

    public function __construct(App $app)
    {
        parent::__construct($app);

        if (!$_SESSION["f"]["alert"]) {
            $_SESSION["f"]["alert"] = new Alert();
        }
        $this->alert = $_SESSION["f"]["alert"];

        $this->app->alert = $this->alert;
        $this->alt = $this->app->alt;
    }

    public function redirect($uri = null, $params = null)
    {
        return $this->_redirect($uri, $params);
    }

    public function _redirect($uri = null, $params = null)
    {
        if ($uri) {
            if ($uri[0] == "/") {
                if ($this->app->current_language != $this->app->language[0]) {
                    $uri = "/" . $this->app->current_language . $uri;
                }
            }
            if ($params) {
                $uri .= "?" . http_build_query($params);
            }
            $this->response = $this->response->withHeader("Location", $uri);
            return;
        }
        $header = $this->request->getHeader("Referer");
        if ($h = $header[0]) {
            $this->response = $this->response->withHeader("Location", $h);
        }
    }

    private function findMasterClass()
    {
        $pi = pathinfo($this->file);
        $root = $this->root;

        if (file_exists($file = $root . "/" . $pi["filename"] . ".master.php")) {
            $this->app->loader->addClassMap(["_index_master" => $file]);
            return [
                "file" => $file,
                "class" => "_index_master"
            ];
        }

        if (file_exists($file = $root . "/pages/index.master.php")) {
            $this->app->loader->addClassMap(["_index_master" => $file]);
            return [
                "file" => $file,
                "class" => "_index_master"
            ];
        }


        if ($this->app->loader->loadClass("index.master")) {
            return [
                "file" => $this->app->loader->findFile("index.master"),
                "class" => "_index_master"
            ];
        }


        $route = $this->route;

        $path = substr($route->real_path, 0, -4);
        if (is_readable($path . ".master.php")) {
            return $path . ".master.php";
        }

        $o = explode("/", $path);
        array_pop($o);
        while (sizeof($o)) {
            $path = implode("/", $o) . "/index.master.php";
            if (is_readable($path)) {
                return $path;
            }
            array_pop($o);
        }
    }

    public function setMasterPage($mp)
    {
        $this->master = $mp;
    }

    public function master()
    {
        if ($this->master) {
            return $this->master;
        }

        // check to route
        $master = $this->findMasterClass();

        if ($master) {
            $master_class = $master["class"];
            $this->master = new $master_class($this->app);
            $this->master->file = $master["file"];
            return $this->master;
        }
    }

    public function template(string $file = null)
    {
        if (!$file) {
            if ($this->template) {
                return $this->template;
            }

            $file = realpath($this->app->loader->findFile(get_class($this)));
            $pi = pathinfo($file);
        }

        $this->template = $this->app->findTemplate($file);
        return $this->template;
    }

    public function assign($name, $value)
    {
        $this->data[$name] = $value;
    }

    protected function getTextDomain(): string
    {
        $file = substr($this->file, strlen($this->app->root . "/pages/"));
        $pi = pathinfo($file);

        $file = $pi["dirname"] . "/" . $pi["filename"];

        $lang =  $this->app->language_locale_map[$this->app->current_language];

        $mo = glob($this->app->root . "/locale/{$lang}/LC_MESSAGES/{$file}-*.mo")[0];

        if ($mo) {
            $mo_file = substr($mo, strlen($this->app->root . "/locale/{$lang}/LC_MESSAGES/"));
            $domain = preg_replace('/.[^.]*$/', '', $mo_file);
            return $domain;
        }
        return uniqid();
    }

    public function __invoke(ServerRequest $request, ResponseInterface $response): ResponseInterface
    {
        $this->request = $request;

        $domain = $this->getTextDomain();
        bindtextdomain($domain, $this->app->root . "/locale");
        textdomain($domain);

        $method = strtolower($this->request->getMethod());
        if ($method == "get" && ($request->isAccept("text/html") || $request->isAccept("*/*"))) {
            $this->master();
            $this->template();
        }

        ob_start();
        try {
            $response = parent::__invoke($request, $response);
        } catch (Exception $e) {
            if ($request->getHeader("Accept")[0] == "application/json") {
                $response = $response->withHeader("Content-Type", "application/json; charset=UTF-8");
                if ($e->getCode()) {
                    $ret = ["code" => $e->getCode(), "message" => $e->getMessage()];
                } else {
                    $ret = ["message" => $e->getMessage()];
                }
                return $response->withBody(new Stream(json_encode($ret)));
            } else {
                $response = $response->withHeader("Content-Type", "text/html; charset=UTF-8")
                    ->withBody(new Stream($e->getMessage()));
            }
        }
        $echo_content = ob_get_contents();
        ob_end_clean();

        //check template
        if ($template = $this->template) {
            $this->data["app"] = $this->app;

            $ret = $response->getBody()->getContents();
            if (is_array($ret)) {
                $this->data = array_merge($this->data, $ret);
            }
            try {
                $content .= $template->render($this->data);
            } catch (\Twig_Error_Runtime $e) {
                $content .= $e->getMessage();
            }

            $response->withHeader("Content-Type", "text/html; charset=UTF-8");
        } else {
            $content = (string) $response;
        }

        $content = $echo_content . $content;

        if ($request->getHeader("Accept")[0] == "application/json") {
            $stream = new Stream();
            $stream->write($content);
            $response = $response->withBody($stream);
        } else {
            if ($master = $this->master) {
                $response->withHeader("Content-Type", "text/html; charset=UTF-8");
                $master->data["content"] = $content;
                $response = $master->__invoke($request, $response);
            } else {
                $stream = new Stream();
                $stream->write($content);
                $response = $response->withBody($stream);
            }
        }

        return $response;
    }

    public function id()
    {
        $path = $this->request->getUri()->getPath();

        foreach (explode("/", $path) as $p) {
            if (is_numeric($p)) {
                return $p;
            }
        }
        return null;
    }

    public function ids()
    {
        $result = [];
        $path = $this->request->getUri()->getPath();

        foreach (explode("/", $path) as $p) {
            if (is_numeric($p)) {
                $result[] = $p;
            }
        }
        return $result;
    }
}
