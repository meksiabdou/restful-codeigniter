<?php

namespace CI4Restful\Helpers;

use CodeIgniter\RESTful\ResourceController;
use CI4Restful\Models\QueryModel;
use Config\App as Configs;
use CodeIgniter\I18n\Time;
use Exception;

class RestServer extends ResourceController
{

    protected $request;

    protected $token;

    protected $token_app;

    protected $config;

    protected $array_methods = [
        'login',
        'register',
        'logout',
        'reSendActivateAccount',
        'forgot',
        'reset',
    ];

    protected $user = null;

    public function __construct()
    {
        $this->request = service('request');
    }

    public function _remap($method, ...$params)
    {
        try {
            $auth = $this->auth($method, $params);
            if ($auth === true) {
                return $this->$method(...$params);
            }
            return $this->respond(["status" => false, "code" => [3001], "error" => 'token !!', 'description' => ''], 403);
        } catch (Exception $e) {
            return $this->respond(["status" => false, "code" => [3001], "error" => 'Error 500',  'description' => CI_DEBUG ? $e->getMessage() : ''], 500);
        }
    }

    protected function device() {
        $agent = $this->request->getUserAgent();
        return  $agent->getBrowser() . '.' . $agent->getVersion() . '.' . $agent->getPlatform();
    }

    private function auth($method, $params)
    {

        try {

            $this->config = new Configs();

            $post = [];
            $get = $this->request->getGet();
            $agent = $this->request->getUserAgent();

            $data = [
                'token' => null,
                'uri' => $this->request->getServer('REQUEST_URI'),
                'ip_address' => $this->request->getIPAddress(),
                'method_request' => $this->request->getMethod(TRUE),
                'method' => $method,
                'params' => json_encode(['params' => $params, 'post' => $post, 'get' => $get], true),
                'agent' => json_encode([
                    'robot' => $agent->isRobot() ? $this->agent->robot() : false,
                    'browser' => $agent->isBrowser() ?  $agent->getBrowser() . '.' . $agent->getVersion() : false,
                    'mobile' => $agent->isMobile() ? $agent->getMobile() : false,
                    'platform' => $agent->getPlatform(),
                    'referrer' => $agent->isReferral() ? $agent->getReferrer() : false,
                ], true),
                'date' => Time::createFromTimestamp(time(), $this->config->appTimezone),
                'authorized' => 0,
            ];

            $status = false;

            if ($this->request->getHeader('token') && !$agent->isRobot()) {

                $this->token = $this->request->getHeader('token')->getValue();
                $this->token_app = isset($this->config->token_app) && is_array($this->config->token_app) ? $this->config->token_app : [];

                $device = $this->request->getHeader('device') ? $this->request->getHeader('device')->getValue() : $this->device();

                $data['token'] = $this->token;

                if (in_array($method, $this->array_methods)) {

                    if (!in_array($this->token, $this->token_app)) {
                        $data['authorized'] = 0;
                        $status = false;
                    } else {
                        $data['authorized'] = 1;
                        $status = true;
                    }
                } else {

                    $query = new QueryModel('auth_tokens');

                    $_token = explode(':', $this->token);

                    if (is_array($_token)) {

                        $selector = $_token[0];
                        $validator = hash('sha256', $_token[1]);

                        $getToken =  $query->where('selector', $selector)
                            ->where('hashedValidator', $validator)
                            ->where('expires >=', date('Y-m-d H:i:s'))
                            ->get()
                            ->getRow();

                        if ($getToken) {

                            if (strtolower($device) === strtolower($getToken->device)) {
                                $data['authorized'] = 1;
                                $status = true;
                                $this->user = $getToken->user_id;
                            } else {
                                $data['authorized'] = 0;
                                $status = false;
                                $query->deleteDataWhere(['id' => $getToken->id]);
                            }
                        } else {
                            $data['authorized'] = 0;
                            $status = false;
                        }
                    } else {
                        $data['authorized'] = 0;
                        $status = false;
                    }
                }
            } else {
                $data['authorized'] = 0;
                $status = false;
            }

            $this->logs($data);
            return $status;
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }


    private function logs($data)
    {

        $isLogs = isset($this->config->logs) ? $this->config->logs  : false;

        if ($isLogs) {
            $query = new QueryModel('logs');
            $query->insertToDb($data);
        }
    }

    public function response_json($data = [], $status = true, $statusCode = 200)
    {
        $this->format = 'json';
        return $this->respond(["status" => $status, "results" => $data], $statusCode);
    }

    public function getUserId()
    {
        return $this->user;
    }

    public function setMethod($methods = [])
    {
        if (is_array($methods)) {
            $this->array_methods = [...$this->array_methods, ...$methods];
        } else {

            if (!empty($methods)) {
                $this->array_methods = [...$this->array_methods, $methods];
            }
        }
    }
}
