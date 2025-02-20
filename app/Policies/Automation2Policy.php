<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Automation2;

class Automation2Policy
{
    use HandlesAuthorization;

    public function list(User $user)
    {
        if (app_profile('automation.disable') === true) {
            return false;
        }

        return true;
    }

    public function create(User $user, Automation2 $automation)
    {
        // RBAC check
        if (!$user->hasPermission('automation.full_access')) {
            return false;
        }

        $customer = $user->customer;
        $max = get_tmp_quota($customer, 'automation_max');

        $can = $max > $customer->local()->automationsCount() || $max == -1;

        return $can;
    }

    public function view(User $user, Automation2 $automation)
    {
        if (app_profile('automation.disable') === true) {
            return false;
        }

        // owner check
        $can = $automation->customer_id == $user->customer->id;

        return $can;
    }

    public function update(User $user, Automation2 $automation)
    {
        // RBAC check
        if (!$user->hasPermission('automation.full_access')) {
            return false;
        }

        if (app_profile('automation.disable') === true) {
            return false;
        }

        // owner check
        $can = $automation->customer_id == $user->customer->id && !$user->isReadOnly();

        return $can;
    }


    public function enable(User $user, Automation2 $automation)
    {
        // RBAC check
        if (!$user->hasPermission('automation.full_access')) {
            return false;
        }

        $can = $automation->customer_id == $user->customer->id &&
            in_array($automation->status, [
                Automation2::STATUS_INACTIVE
            ]) && !$user->isReadOnly();

        return $can;
    }

    public function disable(User $user, Automation2 $automation)
    {
        // RBAC check
        if (!$user->hasPermission('automation.full_access')) {
            return false;
        }

        if (app_profile('automation.disable') === true) {
            return false;
        }

        $can = $automation->customer_id == $user->customer->id &&
            in_array($automation->status, [
                Automation2::STATUS_ACTIVE
            ]) && !$user->isReadOnly();

        return $can;
    }

    public function delete(User $user, Automation2 $automation)
    {
        // RBAC check
        if (!$user->hasPermission('automation.full_access')) {
            return false;
        }

        if (app_profile('automation.disable') === true) {
            return false;
        }

        $can = $automation->customer_id == $user->customer->id &&
            in_array($automation->status, [
                Automation2::STATUS_ACTIVE,
                Automation2::STATUS_INACTIVE
            ]) && !$user->isReadOnly();

        return $can;
    }
}
