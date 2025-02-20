<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterStatistic extends Model
{
    use HasFactory;

    protected $table = 'newsletter_customer_statistics';

    protected $fillable = [
        'user_id',
        'total_newsletters_sent',
        'total_recipients',
        'opened',
        'not_opened',
        'delivered',
        'total_failed_deliveries',
        'total_subscribers',
        'unsubscribed',
        'mail_list_id',
        'recipients_email',
        'subject'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function mailList()
    {
        return $this->belongsTo(MailList::class);
    }
}
