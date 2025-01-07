<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // authorize
        if (\Gate::denies('read', new \Acelle\Model\Admin())) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (!$request->user()->admin->can("readAll", new \Acelle\Model\Admin())) {
            $request->merge(array("creator_id" => $request->user()->id));
        }

        $admins = \Acelle\Model\Admin::search($request);

        return view('admin.admins.index', [
            'admins' => $admins,
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
        if (\Gate::denies('read', new \Acelle\Model\Admin())) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (!$request->user()->admin->can("readAll", new \Acelle\Model\Admin())) {
            $request->merge(array("creator_id" => $request->user()->id));
        }

        $admins = \Acelle\Model\Admin::search($request)->paginate($request->per_page);

        return view('admin.admins._list', [
            'admins' => $admins,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $admin = \Acelle\Model\Admin::newAdmin();
        $user = \Acelle\Model\User::newDefault();

        // authorize
        if (\Gate::denies('create', $admin)) {
            return $this->notAuthorized();
        }

        return view('admin.admins.create', [
            'admin' => $admin,
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
        // Get current user
        $admin = \Acelle\Model\Admin::newAdmin();
        $user = \Acelle\Model\User::newDefault();

        // authorize
        if (\Gate::denies('create', $admin)) {
            return $this->notAuthorized();
        }

        // fill
        $admin->fill($request->all());
        $user->fill($request->all());

        // validate all
        $rules = [
            // user
            'email' => 'required|email|unique:users,email,'.$user->id.',id',
            'first_name' => 'required',
            'last_name' => 'required',
            'image' => 'nullable|image',

            // admin
            'admin_group_id' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
        ];

        $validator = \Validator::make($request->all(), $rules);

        // redirect if fails
        if ($validator->fails()) {
            return response()->view('admin.admins.create', [
                'admin' => $admin,
                'user' => $user,
                'errors' => $validator->errors(),
            ], 400);
        }

        //
        [$result, $errors] = $user->saveUser(
            $email = $request->email,
            $password = $request->password,
            $passwordConfirmation = $request->password_confirmation,
            $first_name = $request->first_name,
            $last_name = $request->last_name,
            $image = $request->image,
            $role_uid = \Acelle\Model\Role::getDefaultAdminRole()->uid
        );

        // save admin
        [$result, $errors] = $admin->saveAdmin(
            $user = $user,
            $admin_group_id = $request->admin_group_id,
            $creator_id = $request->user()->id,
            $timezone = $request->timezone,
            $language_id = $request->language_id,
            $create_customer_account = $request->create_customer_account
        );

        // redirect if fails
        if (!$result) {
            return response()->view('admin.admins.create', [
                'admin' => $admin,
                'user' => $user,
                'errors' => $errors,
            ], 400);
        }

        // success
        return redirect()->action('Admin\AdminController@index')
            ->with('alert-success', trans('messages.admin.created'));
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
        $admin = \Acelle\Model\Admin::findByUid($id);
        $user = $admin->user;

        // authorize
        if (\Gate::denies('update', $admin)) {
            return $this->notAuthorized();
        }

        return view('admin.admins.edit', [
            'admin' => $admin,
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
    public function update(Request $request, $id)
    {
        // Get current user
        $admin = \Acelle\Model\Admin::findByUid($id);
        $user = $admin->user;

        // authorize
        if (\Gate::denies('update', $admin)) {
            return $this->notAuthorized();
        }

        // fill
        $admin->fill($request->all());
        $user->fill($request->all());

        // validate all
        $rules = [
            // user
            'email' => 'required|email|unique:users,email,'.$user->id.',id',
            'first_name' => 'required',
            'last_name' => 'required',
            'image' => 'nullable|image',

            // admin
            'admin_group_id' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
        ];

        $validator = \Validator::make($request->all(), $rules);

        // redirect if fails
        if ($validator->fails()) {
            return response()->view('admin.admins.create', [
                'admin' => $admin,
                'user' => $user,
                'errors' => $validator->errors(),
            ], 400);
        }

        //
        [$result, $errors] = $user->saveUser(
            $email = $request->email,
            $password = $request->password,
            $passwordConfirmation = $request->password_confirmation,
            $first_name = $request->first_name,
            $last_name = $request->last_name,
            $image = $request->image,
            $role_uid = \Acelle\Model\Role::getDefaultAdminRole()->uid
        );

        // save admin
        [$result, $errors] = $admin->saveAdmin(
            $user = $user,
            $admin_group_id = $request->admin_group_id,
            $creator_id = $request->user()->id,
            $timezone = $request->timezone,
            $language_id = $request->language_id,
            $create_customer_account = $request->create_customer_account
        );

        // redirect if fails
        if (!$result) {
            return response()->view('admin.admins.edit', [
                'admin' => $admin,
                'user' => $user,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('Admin\AdminController@index')
            ->with('alert-success', trans('messages.admin.updated'));
    }

    public function select2(Request $request)
    {
        $result = [['id' => '1', 'text' => 'One'], ['id' => '2', 'text' => 'Two']];

        return response()->json($result);
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
        $items = \Acelle\Model\Admin::whereIn(
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
        echo trans('messages.admins.enabled');
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
        $items = \Acelle\Model\Admin::whereIn(
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
        echo trans('messages.admins.disabled');
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
        $items = \Acelle\Model\Admin::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if (\Gate::denies('delete', $item)) {
                return;
            }
        }

        foreach ($items->get() as $item) {
            $item->deleteAccount();
        }

        // Redirect to my lists page
        echo trans('messages.admins.deleted');
    }

    /**
     * Switch user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function loginAs(Request $request)
    {
        $admin = \Acelle\Model\Admin::findByUid($request->uid);

        // authorize
        if (\Gate::denies('loginAs', $admin)) {
            return;
        }

        $orig_id = $request->user()->uid;
        \Auth::login($admin->user);
        \Session::put('orig_admin_id', $orig_id);

        return redirect()->action('Admin\HomeController@index');
    }

    /**
     * Log in back user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function loginBack(Request $request)
    {
        $id = \Session::pull('orig_admin_id');
        $orig_user = \Acelle\Model\User::findByUid($id);

        \Auth::login($orig_user);

        return redirect()->action('Admin\AdminController@index');
    }

    public function oneClickLogin(Request $request)
    {
        $admin = \Acelle\Model\Admin::findByUid($request->uid);

        return view('admin.admins.oneClickLogin', [
            'url' => $admin->user->generateOneClickLoginUrl(),
        ]);
    }
}
