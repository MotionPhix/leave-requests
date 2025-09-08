<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait Tenantable
{
  protected static bool $tenantScopeEnabled = true;

  public static function bootTenantable(): void
  {
    static::creating(function (Model $model) {
      if (static::$tenantScopeEnabled && is_null($model->getAttribute('workspace_id'))) {
        $workspace = request()->attributes->get('workspace');
        if ($workspace) {
          $model->setAttribute('workspace_id', $workspace->getKey());
        }
      }
    });

    static::addGlobalScope('workspace', function (Builder $builder) {
      if (!static::$tenantScopeEnabled) {
        return;
      }
      $workspace = request()->attributes->get('workspace');
      if ($workspace) {
        $builder->where($builder->getModel()->getTable() . '.workspace_id', $workspace->getKey());
      }
    });
  }

  public static function disableTenantScope(callable $callback)
  {
    $prev = static::$tenantScopeEnabled;
    static::$tenantScopeEnabled = false;
    try {
      return $callback();
    } finally {
      static::$tenantScopeEnabled = $prev;
    }
  }
}
