<?php

namespace Acelle\Http\Controllers;

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
    public function index(Request $request)
    {
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

        return view('roles.index');
    }

    public function list(Request $request)
    {
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

        // sort, pagination
        $roles = $request->user()->customer->ownOrGlobalRoles()
            ->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);

        return view('roles.list', [
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
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

        $role = Role::newAccountRole($request->user()->customer);

        return view('roles.create', [
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
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

        $role = Role::newAccountRole($request->user()->customer);

        $errors = $role->saveRole(
            $request->name,
            $request->description,
            $request->permissions
        );

        // redirect if fails
        if (!$errors->isEmpty()) {
            return response()->view('roles.create', [
                'role' => $role,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('RoleController@index')
            ->with('alert-success', trans('messages.role.created'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $uid)
    {
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

        $role = Role::findByUid($uid);

        return view('roles.edit', [
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
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

        $role = Role::findByUid($uid);

        // authorize
        if (!$request->user()->customer->can('update', $role)) {
            return $this->notAuthorized();
        }

        $errors = $role->saveRole(
            $request->name,
            $request->description,
            $request->permissions
        );

        // redirect if fails
        if (!$errors->isEmpty()) {
            return response()->view('roles.edit', [
                'role' => $role,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('RoleController@edit', $role->uid)
            ->with('alert-success', trans('messages.role.updated'));
    }

    public function delete(Request $request)
    {
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

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
            if ($request->user()->customer->can('delete', $role)) {
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
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

        $roles = Role::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        $total = $roles->count();
        $done = 0;
        foreach ($roles->get() as $role) {
            // authorize
            if ($request->user()->customer->can('enable', $role)) {
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
        if (!$request->user()->hasPermission('account.full_access')) {
            return $this->notAuthorized();
        }

        $roles = Role::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        $total = $roles->count();
        $done = 0;
        foreach ($roles->get() as $role) {
            // authorize
            if ($request->user()->customer->can('disable', $role)) {
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
