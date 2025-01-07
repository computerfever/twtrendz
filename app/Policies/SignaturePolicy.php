<?php

namespace Acelle\Policies;

use Acelle\Model\User;
use Acelle\Model\Signature;
use Illuminate\Auth\Access\HandlesAuthorization;

class SignaturePolicy
{
    use HandlesAuthorization;

    public function list(User $user)
    {
        return true;
    }

    public function read(User $user, Signature $signature)
    {
        return $user->customer->id == $signature->customer_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Signature $signature)
    {
        return $user->customer->id == $signature->customer_id;
    }

    public function setDefault(User $user, Signature $signature)
    {
        return $user->customer->id == $signature->customer_id && !$signature->is_default;
    }

    public function enable(User $user, Signature $signature)
    {
        return $user->customer->id == $signature->customer_id && $signature->isInactive();
    }

    public function disable(User $user, Signature $signature)
    {
        return $user->customer->id == $signature->customer_id && $signature->isActive();
    }

    public function delete(User $user, Signature $signature)
    {
        return $user->customer->id == $signature->customer_id;
    }

    public function verify(User $user, Signature $signature)
    {
        return $user->customer->id == $signature->customer_id;
    }
}
