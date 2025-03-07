<?php

namespace Modules\Forms\Http\Controllers;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\LandingPage\Entities\LandingPage;
use Modules\Forms\Entities\FormData;
use Modules\LandingPage\Jobs\IntergrationLandingPage;
use Modules\LandingPage\Jobs\AutoresponderLandingPage;
use Browser;
use Response;
use DB;
use Modules\Popup\Entities\Popup;
use Yajra\Datatables\Datatables;
use Module;

class FormDataController extends Controller
{

	public function index(Request $request)
	{
		$user = $request->user()->customer;
		$popups = [];
		if(Module::find('Popup')){
			$popups = Popup::where('user_id', '=', $user->id)->get();
		}
		$landingpages = LandingPage::where('user_id', '=', $user->id)->get();

		return view('forms::leads.index', ['popups' => $popups, 'landingpages' => $landingpages]);
	}

	public function ajax(Request $request)
	{
		$user = $request->user()->customer;
		$data = FormData::where('user_id', '=', $user->id)->with(['landingpage']);
		
		if(Module::find('Popup')){
			$data = FormData::where('user_id', '=', $user->id)->with(['landingpage', 'popup']);
		}
		$search = $request->input('search');
		if (isset($search['value'])) {
			$data->where('field_values', 'like', '%' . $search['value'] . '%');
		}
		$data->orderBy('created_at', 'desc');

		return Datatables::of($data)
			->addColumn('lead_info', function ($item) {
				$html = '';
				foreach ($item->field_values as $key => $value) {
					$html .= '<div class="text-muted small">' . $key . ': ' . $value . '</div>';
				}
				return $html;
			})
			->addColumn('from', function ($item) {
				$html = '';
				if($item->type == 'landingpage' && isset($item->landingpage->code)) {
					$html .= '<a href="' . route('landingpages.setting', $item->landingpage->code) . '">' . ($item->name ?? $item->landingpage->name) . '</a>';
				} elseif (Module::find('Popup') && $item->type == 'popup' && isset($item->popup->code)) {
					$html .= '<a href="' . route('popups.edit', ['code' => $item->popup->code]) . '">' . ($item->name ?? $item->popup->name) . '</a>';
				}
				return $html;
			})
			->editColumn('browser', function ($item) {
				$html = '' . $item->browser;
				return $html;
			})
			->editColumn('os', function ($item) {
				$html = '' . $item->os;
				return $html;
			})
			->editColumn('device', function ($item) {
				$html = '' . $item->device;
				return $html;
			})
			->addColumn('dates', function ($item) {
				$html = '
					<div class="small text-muted">' . __('Created') . ': ' . $item->created_at->format('M j, Y') . '</div>
					<div class="small text-muted">' . __('Modified') . ': ' . $item->updated_at->format('M j, Y') . '</div>
				';
				return $html;
			})
			->addColumn('action', function ($item) {
				$html = '';
				$html .= '<a href="' . route('leads.edit',$item->id) . '" class="btn btn-sm btn-primary mr-1">' . __('Edit') . '</a>';
				$html .= '<button type="button" data-id="' . $item->id . '" class="btn btn-sm btn-danger btn-delete">' . __('Delete') . '</button>';
				return $html;
			})
			->filter(function ($instance) use ($request) {
				if ($request->filled('popup_id')) {
					$instance->where('source_id', '=', $request->input('popup_id'))->where('type', '=', 'popup');
				}
				if ($request->filled('landingpage_id')) {
					$instance->where('source_id', '=', $request->input('landingpage_id'))->where('type', '=', 'landingpage');
				}
			})
			->rawColumns(['lead_info', 'from', 'browser', 'os', 'device', 'location', 'dates', 'action'])
			->make(true);
	}

	public function destroy(Request $request)
	{
		$request->validate([
			'id' => 'required',
		]);
		$inputs = $request->all();

		$item = FormData::findorFail($inputs['id']);
		$item->delete();
		
		return response()->json([]);
	}
   
	public function edit($id)
	{
		$item = FormData::findorFail($id);

		return view('forms::leads.edit', compact(
			'item'
		));

	}

	public function update(Request $request, $id)
	{
		$item = FormData::findorFail($id);

		$arr_temp = [];

		if ($request->new_field && $request->new_field_value && count($request->new_field) == count($request->new_field_value)) {
		   
		   foreach ($request->new_field as $key_field => $value) {
				$arr_temp[$value] = $request->new_field_value[$key_field];
		   }

		}

		$array_except = ['new_field', 'new_field_value','_token'];

		$field_values_data = $arr_temp + $request->except($array_except);

		if(!array_filter($field_values_data)) {

			return redirect()->back()
			->with('error', __('Not found any fields submit. Please enter some fields'));

		}

		$item->field_values = $field_values_data;

		$item->save();
		
		return redirect()->back()
			->with('success', __('Updated successfully'));
  
	}
	
	public function exportcsv(Request $request)
	{
		$data = FormData::where('user_id', $request->user()->customer->id);

		if ($request->filled('landingpage_code')) {

			$page = LandingPage::where('code', $request->landingpage_code)->first();

			$data->where('source_id', $page->id)->where('type', '=', 'landingpage');
		}

		if(Module::find('Popup')){
			if ($request->filled('popup_code')) {
				$popup = Popup::where('code', $request->popup_code)->first();
				$data->where('source_id', $popup->id)->where('type', '=', 'popup');
			}
		}
	   
		$formdata = $data->get();

		if (count($formdata) > 0) {

			$filename = 'formdata-'.strtotime("now").'.csv';
			
			$handle = fopen($filename, 'w+');
			
			$columns = [];

			foreach($formdata as $item) {
				$columns = array_merge($columns,array_keys($item->field_values));
			}

			$columns = array_unique($columns);

			fputcsv($handle, $columns);

			foreach($formdata as $item) {
				
				$values = [];

				foreach ($columns as $key) {

					if (isset($item->field_values[$key]) && !empty($item->field_values[$key])) {
						  array_push($values,$item->field_values[$key]);
					}
					else{
						array_push($values,'');
					}
					
				}
				fputcsv($handle, $values);
			}
			

			fclose($handle);

			$headers = array(
				'Content-Type' => 'text/csv',
			);

			return Response::download($filename, $filename, $headers)->deleteFileAfterSend(true);

		}
		return redirect()->back()->with('error', __('Not found any data for export'));
	   
		   
	} 

	public function submission($code,Request $request){
		$page = LandingPage::where('code', $code)->first();

		if (!$page) {
			return response()->json(['error'=>__("Not found page id")]);
		}

		$tracking = Browser::detect();

		$fields_expect = ['_browser','_os','_lang','_timezone','_token'];

		$fields_request = array_keys($request->except($fields_expect));

		$fields_request = array_unique($fields_request);

		$field_values = array();
		
		if(count($fields_request) > 0){
			$type_keys_email = ['email','Email','EMAIL'];
			
			foreach ($fields_request as $key) {
				$key_new = $key;
				// replace all type key email to "email"
				if (in_array($key, $type_keys_email)) {
					$key_new = "email";
				}
				$field_values[$key_new] = $request->input($key);
			}

			if(!array_filter($field_values)) {
				return response()->json(['error'=>__("Not found any fields submit. Please enter some fields")]);
			}

			// Check email member exists on form data landing page
			if (isset($field_values['email'])) {
				$data = DB::connection('landingPageModules')->table('form_data')
				->where('source_id',$page->id)
				->where('type', '=', 'landingpage')
				->whereJsonContains('field_values', ['email' =>$field_values['email']])
				->get();
				
				if (count($data) > 0) {
					return response()->json(['error'=>__("Your Email existed!")]);
				}
			}

			$form_data = FormData::create([
				'name' => $page->name,
				'type' => 'landingpage',
				'source_id' => $page->id,
				'user_id' => $page->user_id,
				'field_values' => $field_values,
				'browser' => $tracking->browserFamily(),
				'os' => $tracking->platformFamily(),
				'device' => getDeviceTracking($tracking),
			]);
		   
			if(checkSettingsAutoresponder($page,$form_data)){
				AutoresponderLandingPage::dispatch($page,$form_data);
			}
			if(ruleIntergrationForAddContact($page,$form_data)){
				IntergrationLandingPage::dispatch($page,$form_data);
			}

			return response()->json([
				'type_form_submit' => $page->type_form_submit,
				'redirect_url' => $page->redirect_url,
			]);
		}
		else{
			return response()->json(['error'=>__("Not found any fields submit. You need config name for fields in builder")]);
		}
		
	  
	}
}
