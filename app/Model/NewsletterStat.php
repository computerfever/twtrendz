<?php
namespace Acelle\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterStat extends Model
{
    use HasFactory;

    protected $table = 'newsletter_stats';

    protected $fillable = [
        'newsletter_subject', 
        'sent_count', 
        'opened_count', 
        'unopened_count',
        'Recipients'
    ];
}
