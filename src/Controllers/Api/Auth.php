<?php


namespace CI4Restful\Controllers\Api;

use CI4Restful\Helpers\RestServer;
use CI4Restful\Helpers\Email;
use CI4Restful\Helpers\Token;
use CI4Restful\Models\UserModel;
use CI4Restful\Models\QueryModel;
use Myth\Auth\Models\LoginModel;
use Myth\Auth\Entities\User;

use Exception;

class Auth extends RestServer
{

	protected $auth;
	/**
	 * @var Auth
	 */
	protected $config;

	protected $users;

	protected $loginModel;

	protected $email;

	protected $isReferralsPrograme;

	public function __construct()
	{
		$this->email = new Email();
		$this->config = config('Auth');
		$this->auth = service('authentication');
		$this->users = new UserModel();
		$this->loginModel = new LoginModel();
		$this->config->defaultUserGroup = 'user';
		$this->config->activeResetter = true;
		$this->config->allowRemembering = true;
		$this->config->validFields = ["email"];
		$this->isReferralsPrograme = true;

		helper('text');
	}


	private function handleErrors($errors)
	{

		$_errors = [];
		if (is_array($errors)) {
			foreach ($errors as $key => $value) {
				if (intval($value) === 0) {
					if ($key === 'password' || $value === "Password must not be a common password.") {
						$value = 3009;
					} else {
						$value = 3002;
					}
				}
				array_push($_errors, $value);
			}
		}

		return $_errors;
	}

	public function logout()
	{
		if ($this->request->getPost('login')) {
			$uid = $this->request->getPost('login');

			$this->loginModel->purgeRememberTokens($uid);
		}


		return $this->response_json([], true);
	}

	public function login()
	{

		$rules = [
			'identity'	=> 'required',
			'password' => 'required',
			'device' => 'required',
		];

		if ($this->config->validFields == ['email']) {
			$rules['identity'] .= '|valid_email';
		}

		if (!$this->validate($rules)) {
			return $this->response_json(['code' => [3002], 'description' => $this->validator->getErrors()], false);
		}

		$identity = $this->request->getPost('identity');
		$password = $this->request->getPost('password');
		$device = $this->request->getPost('device');
		$remember = false;


		// Determine credential type
		$type = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

		$token = new Token(5);

		// Try to log them in...
		try {
			if (!$this->auth->attempt([$type => $identity, 'password' => $password], $remember)) {
				return $this->response_json(['code' => [3002], 'description' => $this->auth->error()], false);
			}

			$user = $this->auth->user();

			$user->device = $device;

			if ($user) {
				$user =  $token->generateToken($user);

				if ($this->isReferralsPrograme) {
					$referrals = new QueryModel('referrals');
					$usersRef = $referrals->getDataById(['referral_id' => $user->id]);
					$user->users_ref = count($usersRef);
				}
				return $this->response_json($user, true);
			}
		} catch (Exception $e) {

			return $this->response_json(['code' => [3002], 'description' => $e->getMessage()], false);
		}
	}


	public function register()
	{
		// Check if registration is allowed
		$this->config->allowRegistration = true;
		$this->config->requireActivation = true;


		// Validate here first, since some things,
		// like the password, can only be validated properly here.
		$rules = [
			/*'username' => [
				'label'  => 'username',
				'rules'  => "required|alpha_numeric_space|min_length[3]|is_unique[users.username]",
				'errors' => [
					'is_unique' => 3006,
					'valid_email' => 3006,
					'required' => 3008,
					'min_length' => 3008,
					'alpha_numeric_space' => 3008,
				]
			],*/
			'email' => [
				'label'  => 'email',
				'rules'  => "required|valid_email|is_unique[users.email]",
				'errors' => [
					'is_unique' => 3006,
					'valid_email' => 3006,
					'required' => 3008,
				]
			],
			'phone' => [
				'label'  => 'phone',
				'rules'  => "required|integer|is_unique[users.phone]|min_length[10]|max_length[10]",
				'errors' => [
					'is_unique' => 3007,
					'integer' => 3007,
					'max_length' => 3007,
					'min_length' => 3007,
					'required' => 3008,
				]
			],
			'fullname' => [
				'label'  => 'fullname',
				'rules'  => "required",
				'errors' => [
					'required' => 3008,
				]
			],
			'password' => [
				'label'  => 'password',
				'rules'  => "required|min_length[8]",
				'errors' => [
					//'strong_password' => 3009,
					'min_length' => 3009,
					'required' => 3008,
				]
			],
			'confirmPassword' => [
				'label'  => 'confirmPassword',
				'rules'  => "required|matches[password]",
				'errors' => [
					'matches' => 3009,
					'required' => 3008,
				]
			],
			'country' => [
				'label'  => 'country',
				'rules'  => "required|min_length[2]|max_length[3]",
				'errors' => [
					'required' => 3008,
					'max_length' => 3008,
					'min_length' => 3008,
				]
			],
			'city' => [
				'label'  => 'city',
				'rules'  => "required",
				'errors' => [
					'required' => 3008,
				]
			],
		];

		//return $this->response_json(['code' => $code, 'description' => 'Validation'], false);

		if (!$this->validate($rules)) {
			$code = $this->handleErrors($this->validator->getErrors());
			return $this->response_json(['code' => $code, 'description' => 'Validation'], false);
		}

		$this->config->personalFields = ['fullname', 'phone', 'country', 'city'];
		$this->config->validFields = ["email"];

		// Save the user
		$allowedPostFields = array_merge(['password'], $this->config->validFields, $this->config->personalFields);

		$userData = $this->request->getPost($allowedPostFields);

		$userData['username'] = explode("@", $this->request->getPost('email'))[0];

		//$userData['referral_code'] = random_string('alnum', 8);


		$user = new User($userData);

		$this->config->requireActivation !== false ? $user->generateActivateHash() : $user->activate();

		// Ensure default group gets assigned if set

		$this->config->defaultUserGroup = 'user';

		
		if (!empty($this->config->defaultUserGroup)) {
			$users = $this->users->withGroup($this->config->defaultUserGroup);
		}

		if (!$users->save($user)) {
			return $this->response_json(['code' => [3002], 'description' => $users->errors()], false);
		}

		// for referral programe

		/*if ($this->isReferralsPrograme && $this->request->getPost('referral_code')) {
			$queryRef = new QueryModel('referrals');
			$referral = $this->users->where('referral_code', $this->request->getPost('referral_code'))->first();
			$queryRef->insertToDb(["user_id" => $users->id, "referral_id" => $referral->id]);
		}*/

		if ($this->config->requireActivation !== false) {

			$sent = $this->email->sendActivation($user);
			// Success!
			if ($sent) {
				return $this->response_json(['code' => [2003], 'description' => "Account Successfully Created"], true);
			}
			return $this->response_json(['code' => [3004], 'description' => "mail not send"], false);
		}

		// Success!
		return $this->response_json(['code' => [2003], 'description' => "Account Successfully Created", $users], true);
	}

	public function reSendActivateAccount()
	{

		$throttler = service('throttler');

		if ($throttler->check($this->request->getIPAddress(), 2, MINUTE) === false) {
			return $this->response_json(['code' => [3029], 'description' => 'tooManyRequests'], false);
		}

		$login = urldecode($this->request->getPost('login'));
		$type = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';


		$user = $this->users->where($type, $login)
			->where('active', 0)
			->first();

		if (is_null($user)) {
			return $this->response_json(['code' => [3002], 'description' => 'activationNoUser'], false);
		}

		$activator = service('activator');
		$sent = $activator->send($user);
		$sent = $this->email->sendActivation($user);

		// Success!
		if ($sent) {
			return $this->response_json(['code' => [2003], 'description' => "Successfully Send"], true);
		}

		return $this->response_json(['code' => [3004], 'description' => "mail not send"], false);
	}


	public function forgot()
	{

		$rules = [
			'email'	=> 'required|valid_email',
		];

		if (!$this->validate($rules)) {
			return $this->response_json(['code' => [3002], 'description' => $this->validator->getErrors()], false);
		}

		$user = $this->users->where('email', $this->request->getPost('email'))->first();

		if (is_null($user)) {
			return $this->response_json(['code' => [3002], 'description' => "forgotNoUser"], false);
		}

		// Save the reset hash /
		$user->generateResetHash();
		$this->users->save($user);

		$sent = $this->email->forgotEmailSent($user);

		if ($sent === true) {
			return $this->response_json(['code' => [2001], 'description' => "forgotEmailSent"], true);
		}

		return $this->response_json(['code' => [3005], 'description' => "mail not send", 'error' => $sent], false);
	}

	/**
	 * Verifies the code with the email and saves the new password,
	 * if they all pass validation.
	 *
	 * @return mixed
	 */
	public function reset()
	{

		// First things first - log the reset attempt.
		$this->users->logResetAttempt(
			$this->request->getPost('email'),
			$this->request->getPost('token'),
			$this->request->getIPAddress(),
			(string)$this->request->getUserAgent()
		);

		$rules = [
			'token' => [
				'label'  => 'token',
				'rules'  => "required",
				'errors' => [
					'required' => 3008,
				]
			],
			'email' => [
				'label'  => 'email',
				'rules'  => "required|valid_email",
				'errors' => [
					'valid_email' => 3006,
					'required' => 3008,
				]
			],
			'password' => [
				'label'  => 'password',
				'rules'  => "required|min_length[8]",
				'errors' => [
					//'strong_password' => 3009,
					'min_length' => 3009,
					'required' => 3008,
				]
			],
			'confirmPassword' => [
				'label'  => 'confirmPassword',
				'rules'  => "required|matches[password]",
				'errors' => [
					'matches' => 3009,
					'required' => 3008,
				]
			],
		];

		/*if (!$this->validate($rules)) {
			return $this->response_json(['code' => [3002], 'description' => $this->validator->getErrors()], false);
		}*/

		if (!$this->validate($rules)) {
			$code = $this->handleErrors($this->validator->getErrors());
			return $this->response_json(['code' => $code, 'description' => 'Validation'], false);
		}

		$user = $this->users->where('email', $this->request->getPost('email'))
			->where('reset_hash', $this->request->getPost('token'))
			->first();

		if (is_null($user)) {
			return $this->response_json(['code' => [3002], 'description' => 'forgotNoUser'], false);
		}

		// Reset token still valid?
		if (!empty($user->reset_expires) && time() > $user->reset_expires->getTimestamp()) {
			return $this->response_json(['code' => [3002], 'description' => "resetTokenExpired"], false);
		}

		// Success! Save the new password, and cleanup the reset hash.
		$user->password 		= $this->request->getPost('password');
		$user->reset_hash 		= null;
		$user->reset_at 		= date('Y-m-d H:i:s');
		$user->reset_expires    = null;
		$user->force_pass_reset = false;

		$this->users->save($user);

		return $this->response_json(['code' => [2002], 'description' => 'resetSuccess'], true);
	}


	public function update_user()
	{

		if (!$this->request->getPost('id')) {
			return $this->response_json(['code' => [3002], 'description' => ""], false);
		}

		$uid = $this->request->getPost('id');

		$rules = [
			'fullname' => [
				'label'  => 'fullname',
				'rules'  => "required",
				'errors' => [
					'required' => 3008,
				]
			],
			'email' => [
				'label'  => 'email',
				'rules'  => "required|valid_email|is_unique[users.email,id,$uid]",
				'errors' => [
					'is_unique' => 3006,
					'valid_email' => 3006,
					'required' => 3008,
				]
			],
			'oldEmail' => [
				'label'  => 'oldEmail',
				'rules'  => "required|valid_email",
				'errors' => [
					'valid_email' => 3006,
					'required' => 3008,
				]
			],
			'password' => [
				'label'  => 'password',
				'rules'  => "required",
				'errors' => [
					'required' => 3008,
				]
			],
			'phone' => [
				'label'  => 'phone',
				'rules'  => "required|integer|is_unique[users.phone,id,$uid]",
				'errors' => [
					'is_unique' => 3007,
					'integer' => 3007,
					'required' => 3008,
				]
			],
			'city' => [
				'label'  => 'city',
				'rules'  => "required",
				'errors' => [
					'required' => 3008,
				]
			],
		];


		if (!$this->validate($rules)) {
			$code = $this->handleErrors($this->validator->getErrors());
			return $this->response_json(['code' => $code, 'description' => 'Validation'], false);
		}

		$email = $this->request->getPost('oldEmail');
		$password = $this->request->getPost('password');

		if (!$this->auth->attempt(['email' => $email, 'password' => $password], false)) {
			return $this->response_json(['code' => [3002], 'description' => $this->auth->error()], false);
		}

		$user = $this->auth->user();

		$user->fullname = $this->request->getPost('fullname');
		$user->phone = $this->request->getPost('phone');
		$user->email = $this->request->getPost('email');
		$user->city = $this->request->getPost('city');

		try {
			$update = $this->users->save($user);

			if (!$update) {
				return $this->response_json(['code' => [3012], 'description' => 'Update unsuccessfully'], false);
			}
			return $this->response_json(['code' => [2002], 'description' => 'Edited successfully'], true);
		} catch (Exception $e) {
			return $this->response_json(['code' => [2002], 'description' => $e->getMessage()], true);
		}
	}


	public function update_password()
	{

		$rules = [
			'email' => [
				'label'  => 'email',
				'rules'  => "required|valid_email",
				'errors' => [
					'valid_email' => 3006,
					'required' => 3008,
				]
			],
			'password' => [
				'label'  => 'password',
				'rules'  => "required",
				'errors' => [
					'required' => 3008,
				]
			],
			'newPassword' => [
				'label'  => 'newPassword',
				'rules'  => "required|min_length[8]",
				'errors' => [
					//'strong_password' => 3009,
					'min_length' => 3009,
					'required' => 3008,
				]
			],
			'confirmPassword' => [
				'label'  => 'confirmPassword',
				'rules'  => "required|matches[newPassword]",
				'errors' => [
					'matches' => 3009,
					'required' => 3008,
				]
			],
		];

		if (!$this->validate($rules)) {
			$code = $this->handleErrors($this->validator->getErrors());
			return $this->response_json(['code' => $code, 'description' => $this->validator->getErrors()], false);
		}

		$email = $this->request->getPost('email');
		$password = $this->request->getPost('password');

		try {
			if (!$this->auth->attempt(['email' => $email, 'password' => $password], false)) {
				return $this->response_json(['code' => [3002], 'description' => $this->auth->error()], false);
			}
		} catch (Exception $e) {
			if (!$this->auth->user()) {
				return $this->response_json(['code' => [3002], 'description' => $e->getMessage()], false);
			}
		}

		$user = $this->auth->user();

		$user->password = $this->request->getPost('newPassword');

		try {
			$update = $this->users->save($user);

			if (!$update) {
				return $this->response_json(['code' => [3012], 'description' => 'Update unsuccessfully'], false);
			}
			return $this->response_json(['code' => [2002], 'description' => 'Edited successfully'], true);
		} catch (Exception $e) {
			return $this->response_json(['code' => [3012], 'description' => $e->getMessage()], false);
		}
	}
}
