<?php

namespace App\Services\Notifications;

interface NotificationProvider
{
    public function send(string $to, string $message): bool;
}
