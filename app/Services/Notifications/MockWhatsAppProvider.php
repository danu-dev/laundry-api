<?php

namespace App\Services\Notifications;

class MockWhatsAppProvider implements NotificationProvider
{
    public function send(string $to, string $message): bool
    {
        // Mock provider just pretends it works.
        // In a real app this would call Meta/Twilio HTTP endpoints.
        return true;
    }
}
