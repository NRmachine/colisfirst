<?php

namespace App\Models;

class SupportReplyObserver
{
    public function created(SupportReply $reply): void
    {
        $ticket = SupportTicket::findOrFail($reply->support_ticket_id);
        $ids = $reply->user_id === $ticket->user_id ? User::where('role', 'admin')->where('account_status', 'approved')->pluck('id')->all() : [$ticket->user_id];
        foreach ($ids as $id) {
            if ($id !== $reply->user_id) {
                AccountAlert::create(['user_id' => $id, 'title' => 'Nouveau message dans la demande #'.$ticket->id, 'url' => route('support.show', $ticket, false)]);
            }
        }
    }
}
