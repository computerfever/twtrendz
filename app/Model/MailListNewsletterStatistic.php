<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailListNewsletterStatistic extends Model
{
    use HasFactory;
    protected $table = 'mail_list_newsletter_statistics';
    protected $fillable = [
        'user_id',
        'newsletter_statistics_id',
        'mail_list_id',
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function newsletterStatistic() {
        return $this->belongsTo(NewsletterStatistic::class, 'newsletter_statistic_id');
    }

    public function mailList() {
        return $this->belongsTo(MailList::class, 'mail_list_id');
    }
}
