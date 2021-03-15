<p align="center">
    <a href="https://codeigniter.com/" target="_blank">
        <img src="https://codeigniter.com/assets/images/ci-logo-big.png" height="100px">
    </a>
    <h1 align="center">CodeIgniter 4 RESTful & Auth Api</h1>
    <br>
</p>

CodeIgniter 4 RESTful & Auth Api Resource Base Controller


[![License](https://poser.pugx.org/yidas/codeigniter-rest/license?format=flat-square)](https://packagist.org/packages/yidas/codeigniter-rest)

This RESTful API extension is collected into [composer require meksiabdou/ci4-restful](https://github.com/meksiabdou/restful-codeigniter) which is a complete solution for Codeigniter framework.

Features
--------

- ***Auth** use package myth/auth*

- ***RESTful API** implementation*

- ***Logs** Requests*

---

OUTLINE
-------

- [Demonstration](#demonstration)
    - [RESTful Create Callback](#restful-create-callback)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)

---

DEMONSTRATION
-------------

```php

use CI4Restful\Helpers\RestServer;

class ApiController extends RestServer
{
    public function index()
    {
        return $this->response_json(["id" => 1, "bar" => "foo" ]], true);
    }
}
```

Output with status `200 OK`:

```json
{
    "results" : {
        "id": 1,
        "bar" : "foo",
    },
    "status" : true,
}
```

### RESTful Create Callback

```php
public function store($requestData=null) {

    $this->db->insert('mytable', $requestData);
    $id = $this->db->insert_id();
    
    return $this->response->json(['id'=>$id], true , 201);
}
```

Output with status `201`:

```json
{
    "results" : {
        "id": 1,
    },
    "status" : true,
}
```

Output with error

```php

public function store($requestData=null) {

    if(!$requestData) {
        return $this->response->json([], false );
    }
    
}

```

```json
{
    "results" : {},
    "status" : false,
}
```

Output with status `403 Forbidden`:

```json
{
    "code": 403,
    "error":"API forbidden", 
    "status" : false
}
```

---

REQUIREMENTS
------------
This library requires the following:

- PHP 7.4+
- CodeIgniter 4+
- [agungsugiarto/codeigniter4-cors](https://github.com/agungsugiarto/codeigniter4-cors)
- [myth/auth](https://github.com/lonnieezell/myth-auth)
---

INSTALLATION
------------

Run Composer in your Codeigniter project:

    composer require meksiabdou/ci4-restful
    

CONFIGURATION
-------------

1. Create a controller to extend `CI4Restful\Helpers\RestServer`, 

```php
class Store extends RestServer {}
```

2. Restful auth 

```
https://yourname.com/api/login
https://yourname.com/api/register
https://yourname.com/api/logout
https://yourname.com/api/forgot-password
https://yourname.com/api/reset-password
https://yourname.com/api/update-user
https://yourname.com/api/resend-activate-account
https://yourname.com/api/update (for update password)
```

