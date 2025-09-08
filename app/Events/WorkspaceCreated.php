<?php

namespace App\Events;

use App\Models\Workspace;
use Thunk\Verbs\Event;

class WorkspaceCreated extends Event
{
  public int $workspace_id;
  public string $workspace_name;
  public int $owner_id;

  public function handle(): void
  {
    // This method will be called after the event is committed
    // Any side effects like notifications can go here
  }
}
