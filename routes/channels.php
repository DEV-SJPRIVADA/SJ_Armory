<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('dashboard.updates', fn ($user) => true);
Broadcast::channel('weapons.updates', fn ($user) => true);
Broadcast::channel('assignments.updates', fn ($user) => true);
Broadcast::channel('clients.updates', fn ($user) => true);
Broadcast::channel('transfers.updates', fn ($user) => true);
Broadcast::channel('alerts.updates', fn ($user) => true);
Broadcast::channel('incidents.updates', fn ($user) => true);
Broadcast::channel('import-batches.updates', fn ($user) => true);
Broadcast::channel('users.updates', fn ($user) => true);
Broadcast::channel('workers.updates', fn ($user) => true);
Broadcast::channel('maps.updates', fn ($user) => true);
Broadcast::channel('posts.updates', fn ($user) => true);
