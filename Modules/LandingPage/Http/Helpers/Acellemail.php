<?php

namespace Modules\LandingPage\Http\Helpers;
use GuzzleHttp\Client;

class Acellemail
{
    protected $api_endpoint; 
    protected $api_token;            

    public function __construct($api_endpoint,$api_token)
    {
        $this->api_endpoint = $api_endpoint;
        $this->api_token = $api_token;
        // $api_endpoint = 'https://demo.acellemail.com/api/v1';
        // $api_token = 'OMVRVE986THjQZcqlNQXsogLKjtgEdTrEzsLtBfsRhyRrAhfWlu3aPhinQvK';
        // $acellemail = new Acellemail($api_endpoint,$api_token);
        // $getMailLists = $acellemail->getListMergeFields('5966508a90c30');
        // dd($getMailLists);
    }
    public function testConnect(){

        $data['api_token'] = $this->api_token;
        try {
            $result = $this->callApi($this->api_endpoint . '/lists', 'GET' , $data);

            return ['status'=> true, 'message' => $result];
        
        } catch (\Exception $e) {
            
            return ['status'=> false, 'message' => $e->getMessage()];
        }
    }

    protected function callApi($url, $method, $data = [])
    {
        $client = new Client();

        $response = $client->request($method, $url, [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'api_token' => $this->api_token,
            ],
            'json'    => $data,
            'verify' => false,
        ]);
        $result = json_decode($response->getBody(), true);
        
        return $result;
    }

    
    public function getMailLists(){

        $data['api_token'] = $this->api_token;
        
        try {
            $result = $this->callApi($this->api_endpoint . '/lists', 'GET' , $data);

            return ['status'=> true, 'message' => __('Connected success'), 'data' => $result];
        
        } catch (\Exception $e) {

            return ['status'=> false, 'message' => $e->getMessage()];
        }

    }
    
    public function getListMergeFields($list_id){

        try {

            $merge_fields = [];
            $data['api_token'] = $this->api_token;
            $response = $this->callApi($this->api_endpoint . '/lists/'.$list_id, 'GET' , $data);
            
            if(isset($response['list']['fields'])){
                $fields = $response['list']['fields'];

                foreach ($fields as $item) {
                    // only for type = "string"
                    if ($item['type'] == 'string') {
                        array_push($merge_fields, $item['key']);
                    }
                }
            }
            
            return ['status'=> true, 'message' => __('Connected success'), 'data' => $merge_fields];
        
        } catch (\Exception $e) {
            
            return ['status'=> false, 'message' => $e->getMessage()];
        }
    }
    

    public function addContact($settings, $field_values, $tags){
        
        try {
            
            $data_post = [
                'api_token' => $this->api_token,
                "EMAIL" => $field_values['email'],
                "tag" => $tags
            ];
            // custom_field
            if(isset($settings->merge_fields)){
                $merge_fields_setting = explode(",", $settings->merge_fields);
                foreach ($merge_fields_setting as $item) {

                    if (isset($field_values[$item]) &&  !empty($field_values[$item])) {
                        $data_post[$item] = $field_values[$item];
                    }
                }
            }

            $result = $this->callApi($this->api_endpoint . '/subscribers?list_uid='.$settings->mailing_list, 'POST' , $data_post);

            return ['status'=> true, 'message' => __('Acellemail add contact success')];
        
        } catch (\Exception $e) {
            return ['status'=> false, 'message' => $e->getMessage()];
        }

    }

}
