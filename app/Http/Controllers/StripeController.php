<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Acelle\Library\Facades\Billing;
use Acelle\Library\AutoBillingData;

/**
 * Controls ALL Payment actions of Stripe
*/
class StripeController extends Controller{

	function verifyIncomingJson(Request $request){

		$secret = "whsec_96b579bd35181f46becce6d6193c523913af3f34a74f87df4289293c585d90e2";
		if (Str::startsWith($secret, 'whsec') == true) {
			$endpoint_secret = $secret;

			if ($request->hasHeader('stripe-signature') == true) {
				$sig_header = $request->header('stripe-signature');
			} else {
				Log::error('(Webhooks) StripeController::verifyIncomingJson() -> Invalid header');
				return null;
			}

			$payload = $request->getContent();
			$event = null;

			try {
				$event = \Stripe\Webhook::constructEvent(
					$payload,
					$sig_header,
					$endpoint_secret
				);
				return json_encode($event);
			} catch (\UnexpectedValueException $e) {
				// Invalid payload
				Log::error('(Webhooks) StripeController::verifyIncomingJson() -> Invalid payload : ' . $payload);
				return null;
			} catch (\Stripe\Exception\SignatureVerificationException $e) {
				// Invalid signature
				Log::error('(Webhooks) StripeController::verifyIncomingJson() -> Invalid signature : ' . $payload);
				return null;
			}
		}
		

		return null;
	}

	public function stripeEvent($event){

		try{

			$gateway = Billing::getGateway('stripe');
			$stripe = new \Stripe\StripeClient($gateway->getSecretKey());

			$incomingJson = json_decode($event);

			// Incoming data is verified at StripeController handleWebhook function, which fires this event.

			$event_type = $incomingJson->type;
			
			// save incoming data

			if($event_type == 'customer.updated'){

				$data = $incomingJson->data->object;

				$customer = \Acelle\Model\Customer::findByUid($data->metadata->local_user_id);

				if($customer != null){
					$autoBillingData = new AutoBillingData($gateway, [
	                    'payment_method_id' => $data->invoice_settings->default_payment_method,
	                    'customer_id' => $data->id,
	                ]);
	                $customer->setAutoBillingData($autoBillingData);

					Log::error('Stripe Event success.'.json_encode($data));
				}

			}

			// save new order if required
			// on cancel we do not delete anything. just check if subs cancelled

		}catch(\Exception $ex){
			Log::error("StripeWebhookListener::handle()\n".$ex->getMessage()." Event: $event_type Line: ".$ex->getLine()." File: ".$ex->getFile());
			error_log("StripeWebhookListener::handle()\n".$ex->getMessage()." Event: $event_type Line: ".$ex->getLine()." File: ".$ex->getFile());
		}

	}

	public function handleWebhook(Request $request)
	{

		// Log::info($request->getContent());
		// $verified = $request->getContent();

		$verified = self::verifyIncomingJson($request);

		if ($verified != null) {

			// Retrieve the JSON payload
			$payload = $verified;

			// Fire the event with the payload
			self::stripeEvent($payload);
			// event(new stripeEvent($payload));

			return response()->json(['success' => true]);
		} else {
			// Incoming json is NOT verified
			abort(404);
		}
	}


}
