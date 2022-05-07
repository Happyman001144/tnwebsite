<?php
namespace Forums\Listeners;

use App\Events\UserSaving as UserSavingEvent;
use Forums\Models\ForumUser;

class UserSaving
{
    public function handle(UserSavingEvent $event)
    {
        $forumUser = ForumUser::find($event->user->steamid);
        if (isset($forumUser)) {
            $forumUser->flushCache();
        }
    }
}
