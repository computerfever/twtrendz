<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Traits\HasUid;

class EmailVerificationPlan extends Model
{
    use HasFactory;
    use HasUid;

    public static function newDefault()
    {
        $plan = new self();
        $plan->visibility = true;

        return $plan;
    }

    public function currency()
    {
        return $this->belongsTo('Acelle\Model\Currency');
    }

    public static function scopeSearch($query, $keyword)
    {
        $keyword = strtolower(trim($keyword));

        // search by keyword
        if ($keyword) {
            $query =  $query->whereRaw('LOWER(name) LIKE ? OR LOWER(description) LIKE ?', '%'.$keyword.'%', '%'.$keyword.'%');
        }
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function fillParams($params)
    {
        $this->name = $params['name'] ?? null;
        $this->description = $params['description'] ?? null;
        $this->credits = $params['credits'] ?? null;
        $this->price = $params['price'] ?? null;
        $this->currency_id = $params['currency_id'] ?? null;
    }

    public function saveFromParams($params)
    {
        // fill
        $this->fillParams($params);

        $rules = [
            'name'   => ['required'],
            'description'   => ['required'],
            'credits'   => ['required'],
            'currency_id' => ['required'],
            'price' => ['required', 'min:0'],
        ];

        // validation
        $validator = \Validator::make($params, $rules);

        // check if has errors
        if ($validator->fails()) {
            return $validator;
        }

        // save to db
        $this->save();

        // return false
        return $validator;
    }
    public function visibilityOff()
    {
        $this->visibility = false;
        $this->save();
    }

    public function visibilityOn()
    {
        $this->visibility = true;
        $this->save();
    }

    public static function scopeVisible($query)
    {
        $query->where('visibility', true);
    }
}
