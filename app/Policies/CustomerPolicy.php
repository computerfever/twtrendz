<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Customer;

class CustomerPolicy
{
    use HandlesAuthorization;

    public function read(User $user, Customer $item)
    {
        $can = $user->admin->getPermission('customer_read') != 'no';

        return $can;
    }

    public function readAll(User $user, Customer $item)
    {
        $can = $user->admin->getPermission('customer_read') == 'all';

        return $can;
    }

    public function create(User $user, Customer $item, $role = 'admin')
    {
        $can = $user->admin->getPermission('customer_create') == 'yes';

        return $can;
    }

    public function updateProfile(User $user, Customer $customer)
    {
        $can = $user->customer_id == $customer->id;

        // RBAC check
        if ($user->hasPermission('account.full_access')) {
            $permission = true;
        } else {
            $permission = false;
        }

        return $can && $permission;
    }

    public function update(User $user, Customer $item)
    {
        $ability = $user->admin->getPermission('customer_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function delete(User $user, Customer $item)
    {
        $ability = $user->admin->getPermission('customer_delete');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function disable(User $user, Customer $item)
    {
        $ability = $user->admin->getPermission('customer_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can && $item->status != 'inactive';
    }

    public function enable(User $user, Customer $item)
    {
        $ability = $user->admin->getPermission('customer_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can && $item->status != 'active';
    }

    public function register(User $user, Customer $item)
    {
        $ability = \Acelle\Model\Setting::get('enable_user_registration') == 'yes';
        $can = $ability;

        return true;
    }

    public function assignPlan(User $user, Customer $item)
    {
        $ability = $user->admin->getPermission('customer_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function changePlan(User $user, Customer $item)
    {
        $ability = $user->admin->getPermission('customer_update');
        $can = $ability == 'all'
                || ($ability == 'own' && $user->admin->id == $item->admin_id);

        return $can;
    }

    public function profile(User $user, Customer $customer)
    {
        return $user->customer_id == $customer->id;
    }
}
