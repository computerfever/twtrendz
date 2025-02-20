<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Traits\HasUid;

class TranslationPhrase extends Model
{
    use HasFactory;
    use HasUid;

    protected $fillable = [
        'file', 'key', 'ja'
    ];

    public static function findByKey($key)
    {
        return self::where('key', $key)->first();
    }

    public static function scopeSearch($query, $keyword)
    {
        if (!empty($keyword)) {
            $query->where('translation_phrases.key', 'like', '%'.$keyword.'%');
        }

        return $query;
    }
}
