## 2.3.0
alert function
```php
class _index class F\Page{
    public function get(){
        $this->alert->info("info");
        $this->alert->danger("danger");
        $this->alert->warning("warning");
        $this->alert->success("success");
    }
}
```

```twig
{% for alert in app.alerts %}
    {{alert.type}}
    {{alert.message}}
{% endfor %}
```
---

## 2.2.1
fix redirect

---

## 2.1.0

- request page internally
```php
$response=$this->app->get("Testing/a",["a"=>1,"b"=>2]);
```

 
---

## 1.5.1
- source map support

theme?sourceMap=1

---

## 1.4.0
- Less php support (sugguest by norris)
    
Install method: composer require oyejorge/less.php

```php
class _theme_index extends F\LessPage{

}
```
    