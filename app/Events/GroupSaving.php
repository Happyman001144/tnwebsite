<?php
namespace App\Events;

use App\Models\Group;

class GroupSaving
{
    public $group;

    public function __construct(Group $group)
    {
        $this->group = $group;
    }
}
