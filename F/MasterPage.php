<?php
namespace F;

use R\Psr7\Stream;

class MasterPage
{
    private $route;
    public $_twig;
    public $_template;
    public $master;
    public $config = [];
    public $template_file;
    public $template_base;

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
    
    public function template()
    {
        return $this->_template;
    }
    
    public function __invoke($request, $response)
    {
        $this->data["base"] = $request->getURI()->getBasePath();
        // read lang
        $lang = \F::Lang();
        $this->data["lang"] = $lang;

        //file template
        $this->_template=System::FindTemplate($this->file);

        if ($this->_template instanceof \Twig_Template) {
            if ($domain = $this->getTextDomain()) {
                bindtextdomain($domain, getcwd() . "/locale");
                textdomain($domain);
            }

            $content = $this->_template->render($this->data);
        }

        if ($this->master) {
            $this->master->assign("content", $content);
            
            return $this->master($request, $response);
        }
        
        return $response->withBody(new Stream($content));
    }
}
