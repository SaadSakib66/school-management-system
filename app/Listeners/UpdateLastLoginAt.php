<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class UpdateLastLoginAt
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user) {
            // Correct method name: forceFill() with capital F
            $user->forceFill([
                'last_login_at' => now(),
            ])->saveQuietly();
        }
    }
}
