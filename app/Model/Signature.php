<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Traits\HasUid;

class Signature extends Model
{
    use HasFactory;
    use HasUid;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'is_default'
    ];

    public static function newDefault()
    {
        $signature = new static();
        $signature->status = static::STATUS_ACTIVE;
        $signature->is_default = false;

        $signature->content = '<div>
    <p builder-element="TextElement"  style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333;">
        <strong builder-element="TextElement" style="font-size: 16px;">John Doe</strong><br>
        Software Engineer<br>
        <a builder-element href="mailto:johndoe@example.com" style="color: #007BFF; text-decoration: none;">
            johndoe@example.com
        </a><br>
        <a builder-element href="tel:+1234567890" style="color: #007BFF; text-decoration: none;">
            +1 (234) 567-890
        </a><br>
        <a builder-element href="https://www.linkedin.com/in/johndoe" style="color: #007BFF; text-decoration: none;">
            LinkedIn
        </a>
    </p>
</div>
        ';

        return $signature;
    }

    public static function scopeSearch($query, $keyword)
    {
        // Keyword
        if (!empty(trim($keyword))) {
            foreach (explode(' ', trim($keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('signatures.name', 'like', '%'.$keyword.'%')
                        ->orWhere('signatures.content', 'like', '%'.$keyword.'%');
                });
            }
        }
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function isInactive()
    {
        return $this->status == self::STATUS_INACTIVE;
    }

    public function disable()
    {
        $this->status = self::STATUS_INACTIVE;

        return $this->save();
    }

    public function enable()
    {
        $this->status = self::STATUS_ACTIVE;

        return $this->save();
    }

    public function setDefault()
    {
        //
        static::query()->update(['is_default' => false]);

        $this->is_default = true;

        return $this->save();
    }

    public static function scopeActive($query)
    {
        $query->where('status', static::STATUS_ACTIVE);
    }

    public static function scopeIsDefault($query)
    {
        $query->where('is_default', true);
    }

    public function saveSignature($name, $content, $is_default = false)
    {
        // fill
        $this->name = $name;
        $this->content = $content;
        $this->is_default = $is_default;

        $validator = \Validator::make($this->getAttributes(), [
            'name' => 'required',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return [false, $validator->errors()];
        }

        $this->save();

        //
        if ($this->is_default) {
            $this->setDefault();
        }

        return [true, $validator->errors()];

    }
}
