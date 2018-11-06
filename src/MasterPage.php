<?php
namespace F;

use R\Psr7\Stream;
use Exception;

class MasterPage
{
    public $app;
    public $file;
    public $_twig;
    public $_template;
    public $master;
    public $alt;

    public function __construct(App $app)
    {
        if (!$app) new Exception('App not found');
        $this->app = $app;

        $this->file = $this->app->loader->findFile(get_called_class());

        if ($this->app->language[0] == $this->app->current_language) {
            $this->data["base"] = "//" . $_SERVER["SERVER_NAME"] . "/";
        } else {
            $this->data["base"] = "//" . $_SERVER["SERVER_NAME"] . "/" . $this->app->current_language . "/";
        }

        $this->alt = $this->app->alt;
    }

    public function assign($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function setMasterPage(MasterPage $mp)
    {
        $this->master = $mp;
    }

    public function getMasterPage()
    {
        return $this->master;
    }

    public function getTextDomain()
    {
        $_lang = setlocale(LC_ALL, 0);
        $fi = preg_replace('/.[^.]*$/', '', basename($this->file));
        $mo = glob(getcwd() . "/locale/{$_lang}/LC_MESSAGES/{$fi}-*.mo")[0];

        if ($mo) {
            $mo_file = substr($mo, strlen(getcwd() . "/locale/{$_lang}/LC_MESSAGES/"));
            $domain = preg_replace('/.[^.]*$/', '', $mo_file);
            return $domain;
        }
        return uniqid();
    }

    public function template()
    {
        return $this->_template;
    }

    public function __invoke($request, $response)
    {
//        $this->data["base"] = $request->getURI()->getBasePath();

        // read lang
        $lang = $this->app->current_language;
        $this->data["lang"] = $lang; 

        //file template
        $this->_template = $this->app->findTemplate($this->file);

        if ($this->_template) {
            if ($domain = $this->getTextDomain()) {
                bindtextdomain($domain, getcwd() . "/locale");
                textdomain($domain);
            }

            $content = $this->_template->render($this->data);
        }

        if ($this->master) {
            $this->master->data["content"] = $content;

            return $this->master->__invoke($request, $response);
        }

        return $response->withBody(new Stream($content));
    }
}
