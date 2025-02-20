<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Contact;

class ContactPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Contact $item)
    {
        // RBAC check
        if ($user->hasPermission('account.read_only')) {
            return false;
        }

        return !isset($item->id) || $user->contact_id == $item->id;
    }
}
