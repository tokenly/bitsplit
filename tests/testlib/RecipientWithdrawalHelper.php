<?php

use App\Repositories\RecipientWithdrawalRepository;
use Models\Withdrawal;

/**
 *  RecipientWithdrawalHelper
 */
class RecipientWithdrawalHelper
{

    public function newWithdrawal(User $user = null, array $override_vars = [])
    {
        if ($user === null) {
            $user = app('UserHelper')->newRandomUser();
        }

        $vars = $override_vars + [
            'user_id' => $user['id'],
            'address' => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j',
            'asset' => 'FLDC',
        ];

        $recipient_withdrawal = app(RecipientWithdrawalRepository::class)->create($vars);
        return $recipient_withdrawal;
    }

}
