<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\MailList;

class MailListPolicy
{
    use HandlesAuthorization;

    public function list(User $user)
    {
        // RBAC check
        if (!$user->hasPermission('list.full_access') && !$user->hasPermission('list.read_only')) {
            return false;
        }

        return true;
    }

    public function read(User $user, MailList $item)
    {
        // RBAC check
        if (!$user->hasPermission('list.full_access') && !$user->hasPermission('list.read_only')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id;
    }

    public function create(User $user)
    {
        // RBAC check
        if (!$user->hasPermission('list.full_access')) {
            return false;
        }

        // init
        $customer = $user->customer;
        $max = get_tmp_quota($customer, 'list_max');
        $isUnlimited = $max == -1; // -1 means unlimited
        $notReachMax = $max > $customer->local()->lists()->count();

        // check max lists: unlimited or is not reach max
        $can = $isUnlimited || $notReachMax;

        // config/limit.php
        $limit = app_profile('list.limit');
        if (!is_null($limit)) {
            $listsCount = $user->customer->local()->listsCount();
            $can = $can && ($listsCount < $limit);
        } else {
            // ignore limit because it is null
        }

        return $can;
    }

    public function update(User $user, MailList $item)
    {
        // RBAC check
        if (!$user->hasPermission('list.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id;
    }

    public function delete(User $user, MailList $item)
    {
        // RBAC check
        if (!$user->hasPermission('list.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id;
    }

    public function addMoreSubscribers(User $user, MailList $mailList, $numberOfSubscribers = 1)
    {
        // RBAC check
        if (!$user->hasPermission('list.full_access')) {
            return false;
        }

        $max = get_tmp_quota($user->customer, 'subscriber_max');
        $maxPerList = get_tmp_quota($user->customer, 'subscriber_per_list_max');
        return $user->customer->id == $mailList->customer_id &&
            ($max >= $user->customer->local()->subscribersCount() + $numberOfSubscribers || $max == -1) &&
            ($maxPerList >= $mailList->subscribersCount() + $numberOfSubscribers || $maxPerList == -1);
    }

    public function import(User $user, MailList $item)
    {
        // RBAC check
        if (!$user->hasPermission('list.full_access')) {
            return false;
        }

        $customer = $user->customer;
        $can = get_tmp_quota($customer, 'list_import');

        return ($can == 'yes' && $item->customer_id == $customer->id);
    }

    public function export(User $user, MailList $item)
    {
        // RBAC check
        if (!$user->hasPermission('list.full_access')) {
            return false;
        }

        $customer = $user->customer;
        $can = get_tmp_quota($customer, 'list_export');

        return ($can == 'yes' && $item->customer_id == $customer->id);
    }
}
