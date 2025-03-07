<?php

namespace Modules\Forms\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class FormData extends Model
{

    protected $connection = 'landingPageModules';

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'name',
        'type',
        'source_id',
        'user_id',
        'field_values',
        'browser',
        'os',
        'device',
        'created_at',
        'updated_at',
        'url',
        'location',
    ];

    protected $casts = [
        'field_values' => 'array',
        'location' => 'array',
    ];

    public function landingpage()
    {
        return $this->belongsTo('Modules\LandingPage\Entities\LandingPage','source_id', 'id');
    }

    public function popup()
    {
        return $this->belongsTo('Modules\Popup\Entities\Popup','source_id', 'id');
    }

 
}
