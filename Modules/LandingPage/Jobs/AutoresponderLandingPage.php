<?php

namespace Modules\LandingPage\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Forms\Entities\FormData;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AutoresponderLandingPage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $item; // popup or landing page
    protected $form_data;
    
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($item, FormData $form_data)
    {
        $this->item = $item;
        $this->form_data = $form_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $form_data = $this->form_data;
        $autoresponder = $this->item->settings->autoresponder;
        //parse email %field_name_attribute% 
        $message_text = $autoresponder->message_text;
        $field_values = $form_data->field_values;
        
        foreach ($field_values as $key => $value) {
            if($value){
                $field_name = "%".$key."%";
                $message_text = str_replace($field_name, $value, $message_text);
            }
        }
        $autoresponder->message_text = $message_text;

        $autoresponder->to_email = $form_data->field_values['email'];
        Mail::send([],['autoresponder' => $autoresponder] , function ($message) use ($autoresponder){
            $message
            ->from($autoresponder->sender_email, $autoresponder->sender_name)
            ->to($autoresponder->to_email)
            ->subject($autoresponder->message_title)
            ->setBody($autoresponder->message_text, 'text/html');
        });

    }
}
