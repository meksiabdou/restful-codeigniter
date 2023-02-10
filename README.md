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
    - [RESTful Create Function](#restful-create-function)
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

### RESTful Create Function

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

```
composer require meksiabdou/ci4-restful
```

CONFIGURATION
-------------

### Config CORS [agungsugiarto/codeigniter4-cors](https://github.com/agungsugiarto/codeigniter4-cors/blob/master/README.md)

To allow CORS for all your routes, first register CorsFilter.php filter at the top of the $aliases property of App/Config/Filter.php class:

```php
public $aliases = [
    'cors' => \Fluent\Cors\Filters\CorsFilter::class,
    // ...
];
```

Restrict routes based on their URI pattern by editing app/Config/Filters.php and adding them to the $filters array,

```php
public filters = [
    // ...
    'cors' => ['after' => ['api/*']],
];
```


### Config Public Token

To access public routes generate token and add `$token_app` to app\Config\App.php

```php
class App extends BaseConfig {

	public $token_app = ['2ve7Wq9P2QLnzQMlN2uVnBfb10xvOY0NQTuQ7Q'];

   //...
```

### Create a controller to extend `CI4Restful\Helpers\RestServer`, 

```php
class Store extends RestServer {}
```

### Restful Auth API

```
https://yourname.com/api/login (POST)
https://yourname.com/api/register (POST)
https://yourname.com/api/logout (POST)
https://yourname.com/api/forgot-password (POST)
https://yourname.com/api/reset-password (POST)
https://yourname.com/api/update-user (PUT)
https://yourname.com/api/resend-activate-account (PUT)
https://yourname.com/api/p/update (for update password) (POST)
```

### HttpRequest (public routes)

```ts
const formData = new FormData();
formData.append('identity', 'user@email.com')
formData.append('password', 'password123');
var requestOptions = {
  method: 'POST',
  headers: {
    "token" : "2ve7Wq9P2QLnzQMlN2uVnBfb10xvOY0NQTuQ7Q"
   },
  body: formData, 
  redirect: 'follow'
};

fetch("https://yourname.com/api/login", requestOptions)
  .then(response => response.json())
  .then(result => console.log(result))
  .catch(error => console.log('error', error));
```


### HttpRequest (private route)

```ts
const formData = new FormData();
formData.append('email', 'user@email.com')
formData.append('password', 'password123');
formData.append('newPassword', 'newPassword123');
formData.append('confirmPassword', 'newPassword123');

var requestOptions = {
  method: 'PUT',
  headers: {
    "token" : userToken,
   },
   body: formData, 
  redirect: 'follow'
};

fetch("https://yourname.com/p/update", requestOptions)
  .then(response => response.json())
  .then(result => console.log(result))
  .catch(error => console.log('error', error));
```
