<?php
namespace F;

use R\Psr7\Response;
use R\Psr7\Stream;

class Page extends \R\Page
{

    public $master;
    public $template;
    public $data = [];

    public function _redirect($uri, $params)
    {
        if ($uri) {
            if ($uri[0]=="/") {
                if ($this->app->current_language!=$f->language[0]) {
                    $uri="/".$this->app->current_language.$uri;
                }
            }
            if ($params) {
                $uri.="?".http_build_query($params);
            }
            $this->response=$this->response->withHeader("Location", $uri);
            return;
        }
        $header=$this->request->getHeader("Referer");
        if ($h=$header[0]) {
            $this->response=$this->response->withHeader("Location", $h);
        }
    }

    private function findMasterClass()
    {
        $pi=pathinfo($this->file);
        $root=$this->root;
        
        if (file_exists($file = $root."/".$pi["filename"].".master.php")) {
            require_once($file);
            $this->app->loader->addClassMap(["index_master"=>$file]);
            return [
            "file"=>$file,
            "class"=>"index_master"
            ];
        }
        
        if (file_exists($file = $this->root."/pages/index.master.php")) {
            require_once($file);
            $this->app->loader->addClassMap(["index_master"=>$file]);
            return [
            "file"=>$file,
            "class"=>"index_master"];
        }
        

        if ($this->app->loader->loadClass("index.master")) {
            return [
                "file"=>$this->app->loader->findFile("index.master"),
                "class"=>"index_master"];
        }
        

        $route = $this->route;
        
        $path = substr($route->real_path, 0, - 4);
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
            $master_class=$master["class"];
            $this->master=new $master_class($this->app);
            $this->master->file=$master["file"];
            return $this->master;
        }
    }

    public function template($file)
    {
        if (!$file) {
            if ($this->template) {
                return $this->template;
            }
            
            $file=realpath($this->app->loader->findFile(get_class($this)));
            $pi=pathinfo($file);
        }

        $this->template=$this->app->findTemplate($file);
        return $this->template;
    }

    public function assign($name, $value)
    {
        $this->data[$name] = $value;
    }

    protected function getTextDomain()
    {
        $_lang=setlocale(LC_ALL, 0);
        $fi = preg_replace('/.[^.]*$/', '', basename($this->file));
        $mo = glob(getcwd() . "/locale/{$_lang}/LC_MESSAGES/{$fi}-*.mo")[0];

        if ($mo) {
            $mo_file = substr($mo, strlen(getcwd() . "/locale/{$_lang}/LC_MESSAGES/"));
            $domain = preg_replace('/.[^.]*$/', '', $mo_file);
            return $domain;
        }
        return uniqid();
    }

    public function __invoke($request, $response)
    {
        $this->request=$request;
        
        ob_start();
        $response=parent::__invoke($request, $response);
        $echo_content = ob_get_contents();
        ob_end_clean();

        $method=strtolower($this->request->getMethod());
        if ($method=="get" && ($request->isAccept("text/html")||$request->isAccept("*/*"))) {
            $this->master();
            $this->template();
        }

        //check template
        if ($template=$this->template) {
            if ($domain = $this->getTextDomain()) {
                bindtextdomain($domain, getcwd() . "/locale");
                textdomain($domain);
            }
        
            $this->data["app"]["request"]=$request;
                        
            $ret=$response->getBody()->getContents();
            if (is_array($ret)) {
                $this->data=array_merge($this->data, $ret);
            }
            try {
                $content .= $template->render($this->data);
            } catch (\Twig_Error_Runtime $e) {
                $content .= $e->getMessage();
            }
            $response->setHeader("Content-Type", "text/html; charset=UTF-8");
        } else {
            $content=(string)$response;
        }
        
        $content = $echo_content . $content;
        
        if ($master=$this->master) {
            $response->setHeader("Content-Type", "text/html; charset=UTF-8");
            $master->assign("content", $content);
            $response=$master->__invoke($request, $response);
        } else {
            $stream=new Stream();
            $stream->write($content);
            $response=$response->withBody($stream);
        }
        
                
        return $response;
    }

    public function id(){
        $path= $this->request->getUri()->getPath();

        foreach(explode("/",$path) as $p){
            if(is_numeric($p)){
                return $p;
            }
        }
        return null;
    }

    public function ids(){
        $result=[];
        $path= $this->request->getUri()->getPath();

        foreach(explode("/",$path) as $p){
            if(is_numeric($p)){
                $result[]= $p;
            }
        }
        return $result;
    }
}
