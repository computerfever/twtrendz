<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Traits\HasUid;

class TriggerSession extends Model
{
    use HasUid;
    use HasFactory;

    public function automation()
    {
        return $this->belongsTo('Acelle\Model\Automation2');
    }

    public function autoTriggers()
    {
        return $this->hasMany('Acelle\Model\AutoTrigger');
    }
}
