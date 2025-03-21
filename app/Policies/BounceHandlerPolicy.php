<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\BounceHandler;

class BounceHandlerPolicy
{
    use HandlesAuthorization;

    public function read(User $user, BounceHandler $item)
    {
        // config/limit.php
        if (app_profile('bounce_handler.disable') === true) {
            return false;
        }

        $ability = $user->admin->getPermission('bounce_handler_read');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function readAll(User $user, BounceHandler $item)
    {
        // config/limit.php
        if (app_profile('bounce_handler.disable') === true) {
            return false;
        }

        $can = $user->admin->getPermission('bounce_handler_read') == 'all';

        return $can;
    }

    public function create(User $user, BounceHandler $item)
    {
        // RBAC check
        if ($user->hasPermission('account.read_only')) {
            return false;
        }

        // config/limit.php
        if (app_profile('bounce_handler.disable') === true) {
            return false;
        }

        $can = $user->admin->getPermission('bounce_handler_create') == 'yes';

        return $can;
    }

    public function update(User $user, BounceHandler $item)
    {
        // RBAC check
        if ($user->hasPermission('account.read_only')) {
            return false;
        }

        // config/limit.php
        if (app_profile('bounce_handler.disable') === true) {
            return false;
        }

        $ability = $user->admin->getPermission('bounce_handler_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function delete(User $user, BounceHandler $item)
    {
        // RBAC check
        if ($user->hasPermission('account.read_only')) {
            return false;
        }

        // config/limit.php
        if (app_profile('bounce_handler.disable') === true) {
            return false;
        }

        $ability = $user->admin->getPermission('bounce_handler_delete');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function test(User $user, BounceHandler $item)
    {
        // config/limit.php
        if (app_profile('bounce_handler.disable') === true) {
            return false;
        }

        $can = $this->update($user, $item) || !isset($item->id);

        return $can;
    }
}
