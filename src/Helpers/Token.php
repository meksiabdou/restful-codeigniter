<?php

namespace CI4Restful\Helpers;

use Myth\Auth\Models\LoginModel;
use CI4Restful\Models\QueryModel;

class Token
{

    private $loginModel;
    public $rememberLength;
    public $response;

    public function __construct($rememberLength = 5)
    {
        $this->loginModel = new LoginModel();
        $this->rememberLength = $rememberLength * DAY;
        //$this->response = service('response');

    }

    function generateToken($user)
    {

        unset($user->password_hash);
        unset($user->reset_hash);
        unset($user->reset_at);
        unset($user->reset_expires);
        unset($user->activate_hash);

        
        $user->token = $this->generate_key($user);

        /*$cookie = [
            'name'   => 'token',
            'value'  => $user->token,
            'expire' => time() + $this->rememberLength,
            'domain' => 'cdr.loc',
            'path'   => '/',
            'prefix' => '',
            'secure' => FALSE,
            'httponly' => FALSE
        ];
    
        $this->response->setCookie($cookie);
        */
        return $user;
    }

    private function generate_key($user)
    {

        $this->loginModel->purgeOldRememberTokens();

        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(20));
        $expires   = date('Y-m-d H:i:s', time() + $this->rememberLength);
        $device = $user->device;

        $token = $selector . ':' . $validator;

        // Store it in the database
        $query = new QueryModel('auth_tokens');

        $query->insertToDb([
            'user_id' => $user->id,
            'selector' => $selector,
            'hashedValidator' => hash('sha256', $validator),
            'device' => $device,
            'expires' => $expires,
        ]);
        //$this->loginModel->rememberUser($user->id, $selector, hash('sha256', $validator), $expires);

        // Save it to the user's browser in a cookie.

        return $token;
    }
}
