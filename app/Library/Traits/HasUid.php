<?php

namespace Acelle\Library\Traits;

trait HasUid
{
    public static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (is_null($item->uid)) {
                $item->generateUid();
            }
        });

        static::saving(function ($item) {
            //
            if (static::class == \Acelle\Model\Subscription::class) {
                // Nếu trạng thái của subscription là ACTIVE mà customer đang có 1 subscription khách new|active thì throw exception
                if ($item->status == static::STATUS_ACTIVE &&
                    $item->customer->getNewOrActiveGeneralSubscription() &&
                    $item->customer->getNewOrActiveGeneralSubscription()->id != $item->id
                ) {
                    throw new \Exception(
                        'Subscription before save check failed: Customer đang có 1 subscription đang ' . $item->customer->getNewOrActiveGeneralSubscription()->status .
                        '. Saving ID ' . $item->id .
                        '. Current ID: ' . $item->customer->getNewOrActiveGeneralSubscription()->id
                    );
                }
            }
        });

    }

    public static function findByUid($uid)
    {
        return static::where('uid', '=', $uid)->first();
    }

    public function generateUid()
    {
        $this->uid = uniqid();
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }
}
