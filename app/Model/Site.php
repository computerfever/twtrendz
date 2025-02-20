<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Traits\HasUid;

class Site extends Model
{
    use HasFactory;
    use HasUid;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public static function newDefault()
    {
        $site = new static();
        $site->status = static::STATUS_ACTIVE;

        return $site;
    }
}
