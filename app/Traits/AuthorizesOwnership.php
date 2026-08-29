<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait AuthorizesOwnership
{
    protected function authorizeOwnership($model, string $key = 'user_id'): void
    {
        if ($model->{$key} !== Auth::id()) {
            abort(403);
        }
    }
}
