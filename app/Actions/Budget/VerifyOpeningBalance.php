<?php

namespace App\Actions\Budget;

use App\Models\OpeningBalance;
use App\Models\User;

class VerifyOpeningBalance
{
    public function __invoke(OpeningBalance $balance, User $verifier): OpeningBalance
    {
        $balance->update(['verified_by' => $verifier->id, 'verified_at' => now()]);

        return $balance;
    }
}
