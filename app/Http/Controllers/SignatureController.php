<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Acelle\Model\Signature;
use Acelle\Library\Facades\Hook;
use Acelle\Model\SendingDomain;
use Acelle\Library\ExtendedSwiftMessage;
use Acelle\Library\StringHelper;

use function Acelle\Helpers\dkim_sign_with_default_domain;

class SignatureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$request->user()->customer->can('list', Signature::class)) {
            return $this->notAuthorized();
        }

        $signatures = $request->user()->customer->signatures()
            ->search($request->keyword);

        return view('signatures.index', [
            'signatures' => $signatures,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        if (!$request->user()->customer->can('list', Signature::class)) {
            return $this->notAuthorized();
        }

        $signatures = $request->user()->customer->signatures()->search($request->keyword)
            ->orderBy($request->sort_order, $request->sort_direction ? $request->sort_direction : 'asc')
            ->paginate($request->per_page);

        return view('signatures.list', [
            'signatures' => $signatures,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('create', Signature::class)) {
            return $this->notAuthorized();
        }

        //
        $signature = $request->user()->customer->local()->newSignature();

        return view('signatures.create', [
            'signature' => $signature,
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
        // authorize
        if (!$request->user()->customer->can('create', Signature::class)) {
            return $this->notAuthorized();
        }

        //
        $signature = $request->user()->customer->local()->newSignature();

        // try to save
        list($result, $errors) = $signature->saveSignature(
            $name = $request->name,
            $content = $request->content,
            $is_default = $request->is_default,
        );

        // redirect if fails
        if (!$result) {
            return response()->view('signatures.create', [
                'signature' => $signature,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('SignatureController@index')
            ->with('alert-success', trans('messages.signature.added.success', [
                'name' => $signature->name,
            ]));
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
        // authorize
        if (!$request->user()->customer->can('create', Signature::class)) {
            return $this->notAuthorized();
        }

        //
        $signature = Signature::findByUid($id);

        return view('signatures.edit', [
            'signature' => $signature,
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
        // authorize
        if (!$request->user()->customer->can('create', Signature::class)) {
            return $this->notAuthorized();
        }

        //
        $signature = Signature::findByUid($id);

        // try to save
        list($result, $errors) = $signature->saveSignature(
            $name = $request->name,
            $content = $request->content,
            $is_default = $request->is_default,
        );

        // redirect if fails
        if (!$result) {
            return response()->view('signatures.edit', [
                'signature' => $signature,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('SignatureController@index')
            ->with('alert-success', trans('messages.signature.added.success', [
                'name' => $signature->name,
            ]));
    }

    /**
     * Custom sort signatures.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function sort(Request $request)
    {
        echo trans('messages._deleted_');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $signatures = Signature::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($signatures->get() as $signature) {
            // authorize
            if ($request->user()->customer->can('delete', $signature)) {
                $signature->delete();
            }
        }

        // Redirect to my lists page
        echo trans('messages.signatures.deleted');
    }

    /**
     * Disable sending server.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function disable(Request $request)
    {
        $signatures = Signature::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($signatures->get() as $signature) {
            // authorize
            if ($request->user()->customer->can('disable', $signature)) {
                $signature->disable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.signatures.disabled');
    }

    /**
     * Disable sending server.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function enable(Request $request)
    {
        $signatures = Signature::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($signatures->get() as $signature) {
            // authorize
            if ($request->user()->customer->can('enable', $signature)) {
                $signature->enable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.signatures.enabled');
    }

    public function setDefault(Request $request, $id)
    {
        // authorize
        if (!$request->user()->customer->can('create', Signature::class)) {
            return $this->notAuthorized();
        }

        //
        $signature = Signature::findByUid($id);

        //
        $signature->setDefault();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.signature.set_default.success'),
        ]);
    }

    public function selectBox(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('list', Signature::class)) {
            return $this->notAuthorized();
        }

        return view('signatures.selectBox', [
            'currentSignature' => $request->current_signature_uid ? Signature::findByUid($request->current_signature_uid) : null,
            'saveUrl' => $request->saveUrl,
        ]);
    }
}
