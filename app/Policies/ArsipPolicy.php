<?php

namespace App\Policies;

use App\Models\Arsip;
use App\Models\User;

class ArsipPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Arsip $arsip): bool
    {
        return $user->isAdmin() || $arsip->user_id === $user->id;
    }

    public function download(User $user, Arsip $arsip): bool
    {
        return $this->view($user, $arsip);
    }

    public function create(User $user): bool
    {
        return ! $user->isAdmin() || (bool) config('arsip.admin_can_input');
    }

    public function update(User $user, Arsip $arsip): bool
    {
        if ($user->isAdmin()) {
            return (bool) config('arsip.admin_can_input');
        }

        return $arsip->user_id === $user->id;
    }

    public function delete(User $user, Arsip $arsip): bool
    {
        return $user->isAdmin() || $arsip->user_id === $user->id;
    }
}
