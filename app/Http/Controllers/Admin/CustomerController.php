<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Acelle\Model\PlanGeneral;
use Acelle\Library\Facades\Hook;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // authorize
        if (\Gate::denies('read', new \Acelle\Model\Customer())) {
            return $this->notAuthorized();
        }

        // If admin can view all customer
        if (!$request->user()->admin->can("readAll", new \Acelle\Model\Customer())) {
            $request->merge(array("admin_id" => $request->user()->admin->id));
        }

        $customers = \Acelle\Model\Customer::search($request)
            ->filter($request);

        return view('admin.customers.index', [
            'customers' => $customers,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        // authorize
        if (\Gate::denies('read', new \Acelle\Model\Customer())) {
            return $this->notAuthorized();
        }

        // If admin can view all customer
        if (!$request->user()->admin->can("readAll", new \Acelle\Model\Customer())) {
            $request->merge(array("admin_id" => $request->user()->admin->id));
        }

        $customers = \Acelle\Model\Customer::search($request->keyword)
            ->filter($request)
            ->orderBy($request->sort_order, $request->sort_direction ? $request->sort_direction : 'asc')
            ->paginate($request->per_page);

        return view('admin.customers._list', [
            'customers' => $customers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $customer = \Acelle\Model\Customer::newCustomer();
        $user = $customer->newUser();

        // authorize
        if (\Gate::denies('create', $customer)) {
            return $this->notAuthorized();
        }

        return view('admin.customers.create', [
            'customer' => $customer,
            'user' => $user,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // validation
        list($validator, $customer, $user) = \Acelle\Model\Customer::createCustomerWithDefaultUser(
            // customer information
            $admin = $request->user()->admin,
            $name = $request->name,
            $timezone = $request->timezone,
            $language_id = $request->language_id,
            // user information
            $email = $request->email,
            $password = $request->password,
            $passwordConfirmation = $request->password_confirmation,
            $first_name = $request->first_name,
            $last_name = $request->last_name,
            $image = $request->image,
            $role_uid = $request->role_uid
        );

        //  errors
        if (!$validator->errors()->isEmpty()) {
            return view('admin.customers.create', [
                'customer' => $customer,
                'user' => $user,
                'errors' => $validator->errors(),
            ]);
        }

        // Generate info
        $list = $customer->newMailList();

        // Save contact
        $contact = \Acelle\Model\Contact::create(['company' => '', 'phone' => '', 'email' => $request->email, 'zip' => '', '' => '', 'city' => '', 'address_1' => '', 'address_2' => NULL, 'country_id' => NULL, 'url' => '']);
        $list->fill(['name' => 'Newsletter','from_name' => $request->name,'from_email' => $request->email]);
        $list->customer_id = $customer->id;
        $list->contact_id = $contact->id;
        $list->save();

        return redirect()->action('Admin\CustomerController@index')
            ->with('alert-success', trans('messages.customer.created'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $customer = \Acelle\Model\Customer::findByUid($id);
        event(new \Acelle\Events\UserUpdated($customer));

        // authorize
        if (\Gate::denies('update', $customer)) {
            return $this->notAuthorized();
        }

        if (!empty($request->old())) {
            $customer->fill($request->old());
        }

        return view('admin.customers.edit', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Prenvent save from demo mod
        if (config('app.demo')) {
            return view('somethingWentWrong', ['message' => trans('messages.operation_not_allowed_in_demo')]);
        }

        //
        $customer = \Acelle\Model\Customer::findByUid($id);

        // validation
        list($validator, $customer) = $customer->updateCustomer(
            $name = $request->name,
            $timezone = $request->timezone,
            $language_id = $request->language_id
        );

        //  errors
        if (!$validator->errors()->isEmpty()) {
            return view('admin.customers.edit', [
                'customer' => $customer,
                'errors' => $validator->errors(),
            ]);
        }

        return redirect()->action('Admin\CustomerController@index')
            ->with('alert-success', trans('messages.customer.updated'));
    }

    /**
     * Enable item.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function enable(Request $request)
    {
        $items = \Acelle\Model\Customer::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if (\Gate::allows('update', $item)) {
                $item->enable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.customers.disabled');
    }

    /**
     * Disable item.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function disable(Request $request)
    {
        $items = \Acelle\Model\Customer::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if (\Gate::allows('update', $item)) {
                $item->disable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.customers.disabled');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        if (isSiteDemo()) {
            return response()->json(["message" => trans('messages.operation_not_allowed_in_demo')], 404);
        }

        $customers = \Acelle\Model\Customer::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($customers->get() as $customer) {
            // authorize
            if (\Gate::denies('delete', $customer)) {
                return;
            }
        }

        foreach ($customers->get() as $customer) {
            // Delete Customer account but KEEP user account if it is associated with an Admin
            $customer->deleteAccount();
        }

        // Redirect to my lists page
        echo trans('messages.customers.deleted');
    }

    /**
     * Select2 customer.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function select2(Request $request)
    {
        echo \Acelle\Model\Customer::select2($request);
    }

    /**
     * User's subscriptions.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function subscriptions(Request $request, $uid)
    {
        $customer = \Acelle\Model\Customer::findByUid($uid);

        // authorize
        if (\Gate::denies('read', $customer)) {
            return $this->notAuthorized();
        }

        return view('admin.customers.subscriptions', [
            'customer' => $customer
        ]);
    }

    /**
     * Customers growth chart content.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function growthChart(Request $request)
    {
        // authorize
        if (\Gate::denies('read', new \Acelle\Model\Customer())) {
            return $this->notAuthorized();
        }

        $result = [
            'columns' => [],
            'data' => [],
        ];

        // columns
        for ($i = 4; $i >= 0; --$i) {
            $result['columns'][] = \Carbon\Carbon::now()->subMonthsNoOverflow($i)->format('m/Y');
            $result['data'][] = \Acelle\Model\Customer::customersCountByTime(
                \Carbon\Carbon::now()->subMonthsNoOverflow($i)->startOfMonth(),
                \Carbon\Carbon::now()->subMonthsNoOverflow($i)->endOfMonth(),
                $request->user()->admin
            );
        }

        return response()->json($result);
    }

    /**
     * Update customer contact information.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function contact(Request $request, $uid)
    {
        // Get current user
        $customer = \Acelle\Model\Customer::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $customer)) {
            return $this->notAuthorized();
        }

        if ($customer->contact) {
            $contact = $customer->contact;
        } else {
            $contact = new \Acelle\Model\Contact();
        }

        // Create new company if null
        if (!$contact) {
            $contact = new \Acelle\Model\Contact();
        }

        // save posted data
        if ($request->isMethod('post')) {
            $this->validate($request, \Acelle\Model\Contact::$rules);

            $contact->fill($request->all());

            // Save current user info
            if ($contact->save()) {
                $customer->contact_id = $contact->id;
                $customer->save();
                $request->session()->flash('alert-success', trans('messages.customer_contact.updated'));
            }
        }

        return view('admin.customers.contact', [
            'customer' => $customer,
            'contact' => $contact->fill($request->old()),
        ]);
    }

    /**
     * Assign plan to customer.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function assignPlan(Request $request, $uid)
    {
        $customer = \Acelle\Model\Customer::findByUid($uid);
        $plans = PlanGeneral::active()->get();

        // authorize
        if (\Gate::denies('assignPlan', $customer)) {
            return $this->notAuthorized();
        }

        // save posted data
        if ($request->isMethod('post')) {
            $plan = PlanGeneral::findByUid($request->plan_uid);

            try {
                $customer->assignGeneralPlan($plan);
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.customer.plan.assigned', [
                    'plan' => $plan->name,
                    'customer' => $customer->displayName(),
                ]),
            ], 201);
        }

        return view('admin.customers.assign_plan', [
            'customer' => $customer,
            'plans' => $plans,
        ]);
    }
}
