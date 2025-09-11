<?php

namespace App\Models;

use App\Traits\HasUuid;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuid;

    protected $fillable = [
        'name',
        'guard_name',
        'workspace_id',
        'uuid',
    ];

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Boot the model and ensure UUID is generated
     */
    protected static function booted(): void
    {
        parent::booted();
        
        static::creating(function (Role $role) {
            if (empty($role->uuid)) {
                $role->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}