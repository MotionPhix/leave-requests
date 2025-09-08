<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Workspace extends Model
{
  /** @use HasFactory<\Database\Factories\WorkspaceFactory> */
  use HasFactory, HasUuid;

  protected $fillable = [
    'name',
    'slug',
    'owner_id',
  ];

  public function owner(): BelongsTo
  {
    return $this->belongsTo(User::class, 'owner_id');
  }

  public function users(): BelongsToMany
  {
    return $this->belongsToMany(User::class, 'workspace_user')
      ->withPivot('role')
      ->withTimestamps();
  }

  protected static function booted(): void
  {
    static::creating(function (Workspace $workspace) {
      if (empty($workspace->slug) && !empty($workspace->name)) {
        $base = Str::slug($workspace->name);
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->exists()) {
          $slug = $base . '-' . $i++;
        }
        $workspace->slug = $slug;
      }
    });
  }
}
