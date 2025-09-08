<?php

namespace App\Events;

use App\Models\User;
use App\Models\Workspace;
use Thunk\Verbs\Event;

class WorkspaceMemberInvited extends Event
{
  public Workspace $workspace;
  public User $user;
  public string $role;

  public static function fire(Workspace $workspace, User $user, string $role): static
  {
    $event = new static();
    $event->workspace = $workspace;
    $event->user = $user;
    $event->role = $role;
    return $event;
  }
}
