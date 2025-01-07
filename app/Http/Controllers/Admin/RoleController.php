<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Model\Role;
use Acelle\Http\Controllers\Controller;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.roles.index');
    }

    public function list(Request $request)
    {
        // sort, pagination
        $roles = Role::global()->search($request->keyword)
            ->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);

        return view('admin.roles.list', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $role = Role::newGlobalRole();

        return view('admin.roles.create', [
            'role' => $role,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $role = Role::newGlobalRole();

        $errors = $role->saveRole(
            $request->name,
            $request->description,
            $request->permissions
        );

        // redirect if fails
        if (!$errors->isEmpty()) {
            return response()->view('admin.roles.create', [
                'role' => $role,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('Admin\RoleController@index')
            ->with('alert-success', trans('messages.role.created'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($uid)
    {
        $role = Role::findByUid($uid);

        return view('admin.roles.edit', [
            'role' => $role,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uid)
    {
        $role = Role::findByUid($uid);

        // authorize
        if (!$request->user()->admin->can('update', $role)) {
            return $this->notAuthorized();
        }

        $errors = $role->saveRole(
            $request->name,
            $request->description,
            $request->permissions
        );

        // redirect if fails
        if (!$errors->isEmpty()) {
            return response()->view('admin.roles.edit', [
                'role' => $role,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('Admin\RoleController@edit', $role->uid)
            ->with('alert-success', trans('messages.role.updated'));
    }

    public function delete(Request $request)
    {
        if (isSiteDemo()) {
            return response()->json([
                'status' => 'notice',
                'message' => trans('messages.operation_not_allowed_in_demo'),
            ], 403);
        }

        $roles = Role::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        $total = $roles->count();
        $deleted = 0;
        foreach ($roles->get() as $role) {
            // authorize
            if ($request->user()->admin->can('delete', $role)) {
                $role->delete();
                $deleted += 1;
            }
        }

        return response()->json([
        'message' => trans('messages.role.deleted', [ 'deleted' => $deleted, 'total' => $total]),
        ]);
    }

    public function enable(Request $request)
    {
        $roles = Role::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        $total = $roles->count();
        $done = 0;
        foreach ($roles->get() as $role) {
            // authorize
            if ($request->user()->admin->can('enable', $role)) {
                $role->enable();
                $done += 1;
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.role.enabled', [ 'done' => $done, 'total' => $total]),
        ]);
    }

    public function disable(Request $request)
    {
        $roles = Role::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        $total = $roles->count();
        $done = 0;
        foreach ($roles->get() as $role) {
            // authorize
            if ($request->user()->admin->can('disable', $role)) {
                $role->disable();
                $done += 1;
            }

        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.role.disabled', [ 'done' => $done, 'total' => $total]),
        ]);
    }
}
