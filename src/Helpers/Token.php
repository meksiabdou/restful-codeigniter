<?php

namespace CI4Restful\Helpers;

use Myth\Auth\Models\LoginModel;
use CI4Restful\Models\QueryModel;

class Token
{

    private $loginModel;
    public $rememberLength;
    protected $request;

    public function __construct($rememberLength = 5)
    {
        $this->loginModel = new LoginModel();
        $this->rememberLength = $rememberLength * DAY;
        $this->request = service('request');
    }

    protected function device() {
        $agent = $this->request->getUserAgent();
        return  $agent->getBrowser() . '.' . $agent->getVersion() . '.' . $agent->getPlatform();
    }

    function generateToken($user)
    {

        unset($user->password_hash);
        unset($user->reset_hash);
        unset($user->reset_at);
        unset($user->reset_expires);
        unset($user->activate_hash);


        $user->token = $this->generate_key($user);

        return $user;
    }

    private function generate_key($user)
    {

        $this->loginModel->purgeOldRememberTokens();
        $agent = $this->request->getUserAgent();

        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(20));
        $expires   = date('Y-m-d H:i:s', time() + $this->rememberLength);
        //$device = $user->device;
        $device = $this->request->getHeader('device') ? $this->request->getHeader('device')->getValue() : $this->device();

        $token = $selector . ':' . $validator;

        // Store it in the database
        $query = new QueryModel('auth_tokens');

        $response = $query->insertToDb([
            'user_id' => $user->id,
            'selector' => $selector,
            'hashedValidator' => hash('sha256', $validator),
            'device' => $device,
            'expires' => $expires,
        ]);
        
        if ($response) {
            return $token;
        }

        return null;
    }
}
