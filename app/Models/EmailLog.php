<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class EmailLog extends Model
{
    protected $fillable = [
        'mailable_class', 'recipient_email', 'subject', 'body_html',
        'change_request_id', 'status', 'error_message',
        'message_id', 'smtp_debug',
    ];

    public function changeRequest()
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    /**
     * Send a mailable and log the result.
     */
    public static function dispatch(string $to, Mailable $mailable, ?ChangeRequest $changeRequest = null): self
    {
        // Render the email content before sending
        $html = $mailable->render();
        $subject = $mailable->envelope()->subject;
        $className = class_basename($mailable);

        $log = self::create([
            'mailable_class' => $className,
            'recipient_email' => $to,
            'subject' => $subject,
            'body_html' => $html,
            'change_request_id' => $changeRequest?->id,
            'status' => 'sent',
        ]);

        try {
            $sentMessage = Mail::to($to)->send($mailable);

            if ($sentMessage) {
                $symfonySent = $sentMessage->getSymfonySentMessage();
                $log->update(array_filter([
                    'message_id' => $symfonySent?->getMessageId(),
                    'smtp_debug' => $symfonySent?->getDebug(),
                ]));
            }
        } catch (\Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $log;
    }
}
