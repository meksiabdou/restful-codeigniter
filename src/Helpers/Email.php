<?php

namespace CI4Restful\Helpers;

use Config\Email as CEmail;
use CodeIgniter\Config\Services;


class Email
{

    protected $email;

    protected $config;

    protected $user;

    protected $subject;

    protected $message;

    public function __construct()
    {

        $this->config = new CEmail();

        $this->email = Services::email();

        $this->email->initialize($this->config);
    }

    public function send()
    {

        $this->email->clear(true);
        
        $sent = $this->email->setFrom($this->config->fromEmail, $this->config->fromName)
            ->setTo($this->user->email)
            ->setSubject($this->subject)
            ->setMessage($this->message)
            ->setMailType('html')
            ->send();

        if (!$sent) {
            return false;
        }

        return true;
    }


    /**
     * Sends an activation email
     *
     * @param User $user
     *
     * @return mixed
     */

    public function sendActivation($user = null): bool
    {

        if (!$user) {
            return false;
        }

        $this->user = $user;
        $this->subject = "Activate your account";
        $this->message = view('emails/activation', ['hash' => $this->user->activate_hash, 'name' => $user->name]);

        return $this->send();
    }

    public function forgotEmailSent($user = null): bool
    {
        if (!$user) {
            return false;
        }

        $this->user = $user;
        $this->subject = "Password Reset Instructions";
        $this->message = view('emails/forgot', ['hash' => $this->user->reset_hash, 'name' => $user->name]);

        return $this->send();
    }


    public function setConfig($configs)
    {

        foreach ($configs as $key => $value) {
            if (property_exists($this->config, $key)) 
            {
                $this->config->$key = $value;
            }
        }

        $this->user = (object)['email' => $configs->email];
        $this->subject = $configs->subject;
        $this->message = $configs->message; 

    }
}
