<?php namespace CI4Restful\Models;

use Myth\Auth\Models\UserModel as MythModel;

class UserModel extends MythModel
{
    protected $allowedFields = [
        'email', 'username', 'password_hash', 'reset_hash', 'reset_at', 'reset_expires', 'activate_hash',
        'status', 'status_message', 'active', 'force_pass_reset', 'permissions', 'deleted_at',
        'fullname', 'phone', 'country', 'wilaya' ,'remoteImage', 'image', 'referral_code', 'register_type',
    ];

    protected $validationRules = [
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'phone'         => 'required|integer|is_unique[users.phone,id,{id}]',
        'username'      => 'required|alpha_numeric_punct|min_length[3]|is_unique[users.username,id,{id}]',
        'password_hash' => 'required',
        'fullname' => 'required',
    ];


    public function getUserbyId($id)
    {
        $user = $this->where('id', $id)->first();

        if($user)
        {
            unset($user->password_hash);
            unset($user->reset_hash);
            unset($user->reset_at);
            unset($user->reset_expires);
            unset($user->activate_hash);

            return $user;
        }

        return false;
    }
}