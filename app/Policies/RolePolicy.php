<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function create(User $user, $role)
    {
        switch ($role) {
            case 'admin':
                $can = true;
                break;
            case 'customer':
                $can = true;
                break;
        }

        return $can;
    }

    public function update(User $user, Role $item, $role)
    {
        switch ($role) {
            case 'admin':
                $can = !$item->readonly;
                break;
            case 'customer':
                $can = !$item->readonly && !$item->is_global;
                break;
        }

        return $can;
    }

    public function enable(User $user, Role $item, $role)
    {
        switch ($role) {
            case 'admin':
                $can = !$item->readonly;

                //
                $can = $can && !$item->isActive();
                break;
            case 'customer':
                $can = !$item->readonly && !$item->is_global;

                //
                $can = $can && !$item->isActive();
                break;
        }

        return $can;
    }

    public function disable(User $user, Role $item, $role)
    {
        switch ($role) {
            case 'admin':
                $can = !$item->readonly;

                //
                $can = $can && $item->isActive();
                break;
            case 'customer':
                $can = !$item->readonly && !$item->is_global;

                //
                $can = $can && $item->isActive();
                break;
        }

        return $can;
    }

    public function delete(User $user, Role $item, $role)
    {
        switch ($role) {
            case 'admin':
                $can = !$item->readonly;
                break;
            case 'customer':
                $can = !$item->readonly && !$item->is_global;
                break;
        }

        return $can;
    }
}
