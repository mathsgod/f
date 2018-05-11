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
    