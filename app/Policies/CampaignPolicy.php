<?php

namespace Acelle\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Acelle\Model\User;
use Acelle\Model\Campaign;

class CampaignPolicy
{
    use HandlesAuthorization;

    public function list(User $user)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access') && !$user->hasPermission('campaign.read_only')) {
            return false;
        }

        return true;
    }

    public function read(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access') && !$item->hasPermission('campaign.read_only')) {
            return false;
        }

        $can = $item->customer_id == $user->customer->id;

        return $can;
    }

    public function create(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $max = get_tmp_quota($user->customer, 'campaign_max');

        $can = $max > $user->customer->local()->campaigns()->count() || $max == -1;

        // config/limit.php
        $limit = app_profile('campaign.limit');
        if (!is_null($limit)) {
            $campaignsCount = $user->customer->local()->campaignsCount();
            $can = $can && ($campaignsCount < $limit);
        } else {
            // ignore limit because it is null
        }

        return $can;
    }

    public function overview(User $user, Campaign $item)
    {
        $customer = $user->customer;
        return $item->customer_id == $customer->id;
    }

    public function update(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id
            && (in_array($item->status, [
                Campaign::STATUS_NEW,
                Campaign::STATUS_ERROR,
                Campaign::STATUS_PAUSED,
                Campaign::STATUS_SCHEDULED,
                Campaign::STATUS_DONE,
            ]));
    }

    public function delete(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id && in_array($item->status, [
            Campaign::STATUS_NEW,
            Campaign::STATUS_QUEUED,
            Campaign::STATUS_ERROR,
            Campaign::STATUS_PAUSED,
            Campaign::STATUS_DONE,
            Campaign::STATUS_SENDING,
            Campaign::STATUS_SCHEDULED,
        ]);
    }

    public function pause(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id && in_array($item->status, [
            Campaign::STATUS_QUEUED,
            Campaign::STATUS_SENDING,
            Campaign::STATUS_SCHEDULED,
        ]);
    }

    public function run(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id && in_array($item->status, [
            Campaign::STATUS_NEW,
        ]);
    }

    public function resume(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id && in_array($item->status, [
            Campaign::STATUS_PAUSED,
            Campaign::STATUS_ERROR,
            Campaign::STATUS_SCHEDULED,
        ]);
    }

    public function sort(User $user, Campaign $item)
    {
        $customer = $user->customer;
        return $item->customer_id == $customer->id;
    }

    public function copy(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id;
    }

    public function preview(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access') && !$user->hasPermission('campaign.read_only')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id;
    }

    public function image(User $user, Campaign $item)
    {
        $customer = $user->customer;
        return $item->customer_id == $customer->id;
    }

    public function resend(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $customer = $user->customer;
        return $item->customer_id == $customer->id && ($item->isDone() || $item->isPaused());
    }

    public function send_test_email(User $user, Campaign $item)
    {
        $customer = $user->customer;
        return $item->customer_id == $customer->id && in_array($item->status, [
            Campaign::STATUS_QUEUED,
            Campaign::STATUS_SENDING,
            Campaign::STATUS_ERROR,
            Campaign::STATUS_PAUSED,
            Campaign::STATUS_DONE,
            Campaign::STATUS_SCHEDULED,
        ]);
    }

    public function resendHighPriority(User $user, Campaign $item)
    {
        // RBAC check
        if (!$user->hasPermission('campaign.full_access')) {
            return false;
        }

        $customer = $user->customer;

        // is admin or log as customer from admin
        $can = ((null !== \Session::get('orig_user_uid') && \Auth::user()->customer) || $user->admin);

        // same as resume policy
        $can = $can && $item->customer_id == $customer->id && in_array($item->status, [
            Campaign::STATUS_PAUSED,
            Campaign::STATUS_ERROR,
            Campaign::STATUS_SCHEDULED,
        ]);

        return $can;
    }
}
