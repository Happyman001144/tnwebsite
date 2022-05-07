<?php
namespace App\Events;

use App\Models\User;

class UserSaving
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
