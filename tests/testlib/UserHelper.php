<?php

use App\Repositories\UserRepository;

/**
*  UserHelper
*/
class UserHelper
{

    function __construct() {
    }

    public function getSampleUser($email='sample@tokenly.co', $token=null, $username=null) {
        $user = User::where('email', $email);
        if (!$user) {
            if ($token === null) { $token = $this->testingTokenFromEmail($email); }
            if ($username === null) { $username = $this->usernameFromEmail($email); }
            $user = $this->newSampleUser(['email' => $email, 'apitoken' => $token]);
        }
        return $user;
    }

    public function newRandomUser($override_vars=[]) {
        return $this->newSampleUser($override_vars, true);
    }

    public function createSampleUser($override_vars=[]) { return $this->newSampleUser($override_vars); }

    public function newSampleUser($override_vars=[], $randomize=false) {
        $create_vars = array_merge($this->sampleVars(), $override_vars);

        if ($randomize) {
            $create_vars['email'] = $this->randomEmail();
            $create_vars['username'] = $this->usernameFromEmail($create_vars['email']);
            $create_vars['name'] = $this->usernameFromEmail($create_vars['email']);
            $create_vars['apitoken'] = $this->testingTokenFromEmail($create_vars['email']);
        }

        $api_vars = [
            'apitoken'     => $create_vars['apitoken'],
            'apisecretkey' => $create_vars['apisecretkey'],
        ];
        unset($create_vars['apitoken']);
        unset($create_vars['apisecretkey']);

        // extract meta vars
        $meta_vars = [];
        $meta_var_names = ['webhook_url'];
        foreach($meta_var_names as $meta_var_name) {
            if (isset($create_vars[$meta_var_name])) {
                $meta_vars[$meta_var_name] = $create_vars[$meta_var_name];
                unset($create_vars[$meta_var_name]);
            }
        }



        // create user
        User::unguard();
        $new_user = User::create($create_vars);
        User::reguard();

        // Create the associated API Key
        APIKey::unguard();
        APIKey::create([
            'user_id'       => $new_user['id'],
            'client_key'    => $api_vars['apitoken'],
            'client_secret' => $api_vars['apisecretkey'],
            'active'        => 1,
        ]);
        APIKey::reguard();

        // set meta vars
        foreach($meta_vars as $meta_key => $meta_val) {
            UserMeta::setMeta($new_user['id'], $meta_key, $meta_val);
        }

        return $new_user;
    }

    public function sampleVars($override_vars=[]) {
        return array_merge([
            'name'                   => 'Sample User',
            'email'                  => 'sample@tokenly.co',
            'username'               => 'leroyjenkins',
            'password'               => 'foopass',

            'apitoken'               => 'TESTAPITOKEN',
            'apisecretkey'           => 'TESTAPISECRET',

            'webhook_url'            => 'http://user.app/webhook'

            // 'privileges'          => null,
            // 'oauth_token'         => null,
            // 'remember_token'      => null,
            // 'oauth_refresh_token' => null,

        ], $override_vars);
    }

    public function sampleDBVars($override_vars=[]) {
        return $this->sampleVars($override_vars);
    }

    public function testingTokenFromEmail($email) {
        switch ($email) {
            case 'sample@tokenly.co': return 'TESTAPITOKEN';
            default:
                // user2@tokenly.co => TESTUSER2TOKENLYCO
                return substr('TEST'.strtoupper(preg_replace('!^[^a-z0-9]$!i', '', $email)), 0, 16);
        }
        // code
    }

    public function usernameFromEmail($email) {
        return substr('t_'.strtoupper(preg_replace('!^[^a-z0-9]$!i', '', $email)), 0, 16);
    }

    public function randomEmail() {
        return 'u'.substr(md5(uniqid('', true)), 0, 6).'@tokenly.co';
    }


}
