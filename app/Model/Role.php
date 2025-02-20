<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Traits\HasUid;
use Acelle\Model\RolePermission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;
    use HasUid;

    protected $connection = 'mysql';

    // statuses
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';

    public function rolePermissions()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role');
    }

    public static function scopeSearch($query, $keyword)
    {
        // Keyword
        if (!empty(trim($keyword))) {
            foreach (explode(' ', trim($keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('roles.name', 'like', '%'.$keyword.'%');
                });
            }
        }
    }

    public static function newDefault()
    {
        $role = new self();
        $role->status = self::STATUS_ACTIVE;

        return $role;
    }

    public static function newGlobalRole()
    {
        $role = static::newDefault();
        $role->is_global = true;

        return $role;
    }

    public static function newAccountRole($customer)
    {
        $role = static::newDefault();
        $role->is_global = false;
        $role->customer_id = $customer->id;

        return $role;
    }

    public static function scopeGlobal($query)
    {
        $query->whereIsGlobal(true);
    }

    public static function scopeReadonly($query)
    {
        $query->whereReadonly(true);
    }

    public static function scopeAccountRoles($query)
    {
        $query->whereIsGlobal(false);
    }

    public function saveRole($name, $description, $permissions = [])
    {
        //
        $this->name = $name;
        $this->description = $description;

        // make validator
        $validator = \Validator::make([
            'name' => $name,
        ], [
            'name' => 'required',
        ]);

        // redirect if fails
        if ($validator->fails()) {
            return $validator->errors();
        }

        //
        $this->save();

        // update permissions from array
        $this->updatePermissions($permissions);

        return $validator->errors();
    }

    public function addPermission($permission)
    {
        // check if permission already exists
        if ($this->hasPermission($permission)) {
            return;
        }

        // add new role permission record
        RolePermission::createRolePermission($this, $permission);
    }

    public function updatePermissions($permissions)
    {
        // remove all current permissions
        $this->rolePermissions()->delete();

        // save all
        foreach ($permissions as $permission) {
            $rolePermission = new RolePermission();
            $rolePermission->role_id = $this->id;
            $rolePermission->permission = $permission;
            $rolePermission->save();
        }
    }

    public function disable()
    {
        $this->status = self::STATUS_INACTIVE;
        $this->save();
    }

    public function enable()
    {
        $this->status = self::STATUS_ACTIVE;
        $this->save();
    }

    public static function scopeDefault($query)
    {
        $query = $query->where('roles.default', true);
    }

    public static function scopeActive($query)
    {
        $query = $query->where('roles.status', '=', self::STATUS_ACTIVE);
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function hasPermission($permission)
    {
        return $this->rolePermissions()->where('permission', $permission)
            ->exists();
    }

    public static function getDefaultAdminRole()
    {
        return static::whereCode('organization_admin')->first();
    }

    public function assignDefaultAdminPermissions()
    {
        $permissions = config('roles.organization_admin');

        foreach ($permissions as $permission) {
            $this->addPermission($permission);
        }
    }

    public function assignDefaultReadonlyPermissions()
    {
        $permissions = config('roles.organization_readonly');

        foreach ($permissions as $permission) {
            $this->addPermission($permission);
        }
    }
}
