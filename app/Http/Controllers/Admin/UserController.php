<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Acelle\Model\User;
use Acelle\Model\Customer;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $customer_uid)
    {
        $customer = Customer::findByUid($customer_uid);

        return view('admin.users.index', [
            'customer' => $customer,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request, $customer_uid)
    {
        $customer = Customer::findByUid($customer_uid);

        $users =  $customer->users()
            ->search($request->keyword)
            ->orderBy($request->sort_order ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page);

        return view('admin.users._list', [
            'customer' => $customer,
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $customer_uid)
    {
        $customer = Customer::findByUid($customer_uid);
        $user = $customer->newUser();

        return view('admin.users.create', [
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
    public function store(Request $request, $customer_uid)
    {
        $customer = Customer::findByUid($customer_uid);
        $user = $customer->newUser();

        //
        [$result, $errors] = $user->saveUser(
            $email = $request->email,
            $password = $request->password,
            $passwordConfirmation = $request->password_confirmation,
            $first_name = $request->first_name,
            $last_name = $request->last_name,
            $image = $request->image,
            $role_uid = $request->role_uid,
            $phone = $request->phone
        );

        // redirect if fails
        if (!$result) {
            return response()->view('admin.users.create', [
                'customer' => $customer,
                'user' => $user,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('Admin\UserController@index', [
            'customer_uid' => $customer_uid,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $customer_uid, $user_uid)
    {
        $customer = Customer::findByUid($customer_uid);
        $user = \Acelle\Model\User::findByUid($user_uid);

        return view('admin.users.edit', [
            'customer' => $customer,
            'user' => $user,
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
    public function update(Request $request, $customer_uid, $user_uid)
    {
        $customer = Customer::findByUid($customer_uid);
        $user = \Acelle\Model\User::findByUid($user_uid);

        //
        [$result, $errors] = $user->saveUser(
            $email = $request->email,
            $password = $request->password,
            $passwordConfirmation = $request->password_confirmation,
            $first_name = $request->first_name,
            $last_name = $request->last_name,
            $image = $request->image,
            $role_uid = $request->role_uid,
            $phone = $request->phone
        );

        // redirect if fails
        if (!$result) {
            return response()->view('admin.users.edit', [
                'customer' => $customer,
                'user' => $user,
                'errors' => $errors,
            ], 400);
        }

        // Remove image
        if ($request->_remove_image == 'true') {
            $user->removeProfileImage();
        }

        return redirect()->action('Admin\UserController@index', [
            'customer_uid' => $customer_uid,
        ]);
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
        $users = \Acelle\Model\User::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($users->get() as $user) {
            // authorize
            if ($user->customer->can('enable', $user)) {
                $user->enable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.user.disabled');
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
        $users = \Acelle\Model\User::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($users->get() as $user) {
            // authorize
            if ($user->customer->can('disable', $user)) {
                $user->disable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.user.disabled');
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

        $users = \Acelle\Model\User::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($users->get() as $user) {
            // authorize
            if (!$user->customer->can('delete', $user)) {
                return;
            }
        }

        foreach ($users->get() as $user) {
            $user->delete();
        }

        // Redirect to my lists page
        echo trans('messages.user.deleted');
    }

    public function loginAs(Request $request)
    {
        $user = User::findByUid($request->uid);
        $orig_id = $request->user()->uid;

        // authorize
        if (\Gate::denies('loginAs', $user)) {
            return $this->notAuthorized();
        }

        \Auth::login($user);
        \Session::put('orig_user_uid', $orig_id);

        return redirect()->action('HomeController@index');
    }

    public function oneClickLogin(Request $request)
    {
        $user = \Acelle\Model\User::findByUid($request->uid);

        return view('admin.users.oneClickLogin', [
            'url' => $user->generateOneClickLoginUrl(),
        ]);
    }
}
