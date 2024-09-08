<?php

namespace App\Services;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Package;

class TransactionService
{
    /**
     * Process the transaction when a new user registers.
     *
     * @param User $newUser
     * @return User
     */
    public function processTransaction($newUser)

    {
        dd($newUser);
        // Activate sponsor if not already active
        if ($newUser->sponsor) {
            $sponsor = $newUser->sponsor;
            $sponsor->receiveMoney();
        }

        // Set new user's initial package to 'welcome'
        $welcomePackage = Package::firstOrCreate(['name' => 'welcome']);
        $newUser->update(['package_id' => $welcomePackage->id]);

        // Process sponsor's status upgrade
        if ($newUser->sponsor) {
            $newUser->sponsor->checkForPackageUpgrade();
        }

        return $newUser;
    }
}
