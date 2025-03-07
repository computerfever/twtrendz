<?php

namespace Modules\LandingPage\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Forms\Entities\FormData;
use Modules\LandingPage\Http\Helpers\MailChimp;
use Modules\LandingPage\Http\Helpers\Acellemail;

use Illuminate\Support\Facades\Log;

class IntergrationLandingPage implements ShouldQueue{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	protected $item; // item or landing page
	protected $form_data;
	
	/**
	 * Create a new job instance.
	 *
	 * @return void
	 */
	public function __construct($item, FormData $form_data){
		$this->item = $item;
		$this->form_data = $form_data;
	}

	/**
	 * Execute the job.
	 *
	 * @return void
	 */

	public function handle(){
		$form_data = $this->form_data;
		$intergration = $this->item->settings->intergration;

		switch ($intergration->type) {
			
			case 'mailchimp':
				
				$mailChimpSettings = $intergration->settings;
				$api_key = $mailChimpSettings->api_key;
				
				$tags = [config('app.name'),$this->item->name];

				$mailchimp = new MailChimp($api_key);
				$response = $mailchimp->addContact($mailChimpSettings, $form_data->field_values,$tags);

				if ($response['status'] == true) {
					
					Log::info('Success: MailChimp api add member list: '. $form_data->field_values['email']);
				}
				else
					Log::error('Error: MailChimp api add member list' . $response['message']); 

				break;
			
			case 'acellemail':
				
				$settings = $intergration->settings;
				$api_endpoint = $settings->api_endpoint;
				$api_token = $settings->api_token;

				$tags = config('app.name').",".$this->item->name;

				$acellemail = new Acellemail($api_endpoint, $api_token);
				$response = $acellemail->addContact($settings, $form_data->field_values,$tags);

				if ($response['status'] == true) {
					
					Log::info('Success: Acellemail api add member list: '. $form_data->field_values['email']);
				}
				else
					Log::error('Error: Acellemail api add member list' . $response['message']); 

				break;
			
			default:
				break;
		}
		
	}
}
