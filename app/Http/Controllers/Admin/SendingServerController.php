<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Acelle\Model\SendingServer;
use Acelle\Library\Facades\Hook;
use Acelle\Model\SendingDomain;
use Acelle\Library\ExtendedSwiftMessage;
use Acelle\Library\StringHelper;

use function Acelle\Helpers\dkim_sign_with_default_domain;

class SendingServerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if (!$request->user()->admin->can('read', new SendingServer())) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (!$request->user()->admin->can("readAll", new SendingServer())) {
            $request->merge(array("admin_id" => $request->user()->admin->id));
        }

        // exlude customer seding servers
        $request->merge(array("no_customer" => true));

        $items = \Acelle\Model\SendingServer::search($request->keyword)
            ->filter($request);

        return view('admin.sending_servers.index', [
            'items' => $items,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        if (!$request->user()->admin->can('read', new SendingServer())) {
            return $this->notAuthorized();
        }

        // If admin can view all sending domains
        if (!$request->user()->admin->can("readAll", new SendingServer())) {
            $request->merge(array("admin_id" => $request->user()->admin->id));
        }

        // exlude customer seding servers
        $request->merge(array("no_customer" => true));

        $items = \Acelle\Model\SendingServer::system()->search($request->keyword)
            ->filter($request)
            ->orderBy($request->sort_order, $request->sort_direction ? $request->sort_direction : 'asc')
            ->paginate($request->per_page);

        return view('admin.sending_servers._list', [
            'items' => $items,
        ]);
    }

    /**
     * Select sending server type.
     *
     * @return \Illuminate\Http\Response
     */
    public function select(Request $request)
    {
        // Sending servers added by Plugins
        $more = Hook::execute('register_sending_server');
        return view('admin.sending_servers.select', ['more' => $more]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $server = new SendingServer();
        $server->type = $request->type;
        $server = $server->mapType();

        $server->status = 'active';
        $server->uid = '0';
        $server->quota_value = '1000';
        $server->quota_base = '1';
        $server->quota_unit = 'hour';
        $server->fill($request->old());

        $server->name = trans('messages.sending_server.type.' . $request->type);

        // authorize
        if (!$request->user()->admin->can('create', SendingServer::class)) {
            return $this->notAuthorized();
        }

        return view('admin.sending_servers.create', [
            'server' => $server,
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
        if (!$request->user()->admin->can('create', SendingServer::class)) {
            return $this->notAuthorized();
        }

        // New sending server
        list($validator, $server) = SendingServer::createFromArray(array_merge($request->all(), [
            'admin_id' => $request->user()->admin->id,
        ]));

        // Failed
        if ($validator->fails()) {
            if ($server->isExtended()) {
                // Redirect to plugin's create page
                return redirect()->back()->withErrors($validator)->withInput();
            } else {
                return redirect()->action('Admin\SendingServerController@create', $server->type)
                        ->withErrors($validator)->withInput();
            }
        }

        // Success
        $request->session()->flash('alert-success', trans('messages.sending_server.created'));

        // Redirect to Edit page
        if ($server->isExtended()) {
            return redirect($server->getEditUrl());
        } else {
            return redirect()->action('Admin\SendingServerController@edit', [$server->uid, $server->type]);
        }
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
        $server = SendingServer::findByUid($id);
        $server = SendingServer::mapServerType($server);
        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        dispatch(new \Acelle\Jobs\UpdateSendingServerJob($server));

        // bounce / feedback hanlder nullable
        if ($request->old() && empty($request->old()["bounce_handler_id"])) {
            $server->bounce_handler_id = null;
        }
        if ($request->old() && empty($request->old()["feedback_loop_handler_id"])) {
            $server->feedback_loop_handler_id = null;
        }

        $server->fill($request->old());

        $notices = [];

        try {
            $server->test();
            $server->syncIdentities();
            $server->setDefaultFromEmailAddress();
        } catch (\Exception $ex) {
            $server->disable();

            $notices[] = [
                'title' => trans('messages.sending_server.connect_failed'),
                'message' => $ex->getMessage()
            ];
        }

        $identities = [];
        $allIdentities = [];

        try {
            $identities = $server->getVerifiedIdentities();
            $allIdentities = array_key_exists('identities', $server->getOptions()) ? $server->getOptions()['identities'] : [];
        } catch (\Exception $ex) {
            $notices[] = [
                'title' => trans('messages.sending_server.identities_list_failed'),
                'message' => $ex->getMessage(),
            ];
        }

        // options
        if (isset($request->old()['options'])) {
            $server->options = json_encode($request->old()['options']);
        }

        $bigNotices = Hook::execute('generate_big_notice_for_sending_server', [$server]);

        return view('admin.sending_servers.edit', [
            'server' => $server,
            'bigNotices' => $bigNotices,
            'notices' => $notices,
            'identities' => $identities,
            'allIdentities' => $allIdentities,
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
        $current_user = $request->user();
        $server = SendingServer::findByUid($id);
        $server = SendingServer::mapServerType($server);

        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        // save posted data
        if ($request->isMethod('patch')) {
            // Save current user info
            $server->fill($request->all());

            // validation
            $validator = $server->validConnection($request->all());

            if ($validator->fails()) {
                if ($server->isExtended()) {
                    return redirect($server->getEditUrl())->withErrors($validator)
                            ->withInput();
                } else {
                    return redirect()->action('Admin\SendingServerController@edit', [$server->uid, $server->type])
                            ->withErrors($validator)
                            ->withInput();
                }
            }

            // bounce / feedback hanlder nullable
            if (empty($request->bounce_handler_id)) {
                $server->bounce_handler_id = null;
            }
            if (empty($request->feedback_loop_handler_id)) {
                $server->feedback_loop_handler_id = null;
            }

            if ($server->save()) {
                $request->session()->flash('alert-success', trans('messages.sending_server.updated'));

                if ($server->isExtended()) {
                    return redirect($server->getEditUrl());
                } else {
                    return redirect()->action('Admin\SendingServerController@edit', [$server->uid, $server->type]);
                }
            }
        }
    }

    /**
     * Custom sort items.
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
        $items = SendingServer::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if ($request->user()->admin->can('delete', $item)) {
                $item->doDelete();
            }
        }

        // Redirect to my lists page
        echo trans('messages.sending_servers.deleted');
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
        $items = SendingServer::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if ($request->user()->admin->can('disable', $item)) {
                $item->disable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.sending_servers.disabled');
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
        $items = SendingServer::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($items->get() as $item) {
            // authorize
            if ($request->user()->admin->can('enable', $item)) {
                $item->enable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.sending_servers.enabled');
    }

    /**
     * Test Sending server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function test(Request $request, $uid)
    {
        // Get current user
        $current_user = $request->user();

        // Fill new server info
        if ($uid) {
            $server = SendingServer::findByUid($uid);
        } else {
            $server = new SendingServer();
            $server->uid = 0;
        }

        $server->fill($request->all());

        // authorize
        if (!$current_user->admin->can('test', $server)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            $message = new ExtendedSwiftMessage();
            $msgId = StringHelper::generateMessageId(StringHelper::getDomainFromEmail($request->from_email));
            $message->setId($msgId);
            $message->getHeaders()->addTextHeader('X-Acelle-Message-Id', $msgId); // required for SendGrid API sending server
            $message->setContentType('text/plain; charset=utf-8');
            $message->setSubject($request->subject);
            $message->setFrom($request->from_email);
            $message->setTo($request->to_email);
            $message->setReplyTo($request->from_email);
            // $message->setEncoder(\Swift_Encoding::get8bitEncoding());
            $message->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('8bit'));
            $message->addPart($request->content, 'text/plain');

            if (config('custom.sign_with_default_domain')) {
                $message = dkim_sign_with_default_domain($message);
            }

            try {
                $server->mapType()->sendTestEmail($message);
            } catch (\Exception $ex) {
                return response()->json([
                    'status' => 'error', // or success
                    'message' => $ex->getMessage()
                ], 401);
                return;
            }

            return response()->json([
                'status' => 'success', // or success
                'message' => trans('messages.sending_server.test_email_sent')
            ]);
            return;
        }

        return view('admin.sending_servers.test', [
            'server' => $server,
        ]);
    }

    /**
     * Test Sending server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function testConnection(Request $request, $uid)
    {
        $server = SendingServer::findByUid($uid);
        $server = SendingServer::mapServerType($server);

        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        try {
            $server->test();

            return trans('messages.sending_server.test_success');
        } catch (\Exception $e) {
            $server->disable();

            return $e->getMessage();
        }
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
        echo SendingServer::adminSelect2($request);
    }

    /**
     * Sending Limit Form.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function sendingLimit(Request $request)
    {
        if (!$request->uid) {
            $server = new SendingServer();
        } else {
            $server = SendingServer::findByUid($request->uid);
        }

        $server->fill($request->all());

        // Default quota
        if ($server->quota_value == -1) {
            $server->quota_value = '1000';
            $server->quota_base = '1';
            $server->quota_unit = 'hour';
            $server->setOption('sending_limit', '1000_per_hour');
        }

        // save posted data
        if ($request->isMethod('post')) {
            $selectOptions = $server->getSendingLimitSelectOptions();

            return view('admin.sending_servers.form._sending_limit', [
                'quotaValue' => $request->quota_value,
                'quotaBase' => $request->quota_base,
                'quotaUnit' => $request->quota_unit,
                'server' => $server,
            ]);
        }

        return view('admin.sending_servers.form.sending_limit', [
            'server' => $server,
        ]);
    }

    /**
     * Save sending server config settings.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function config(Request $request, $uid)
    {
        // find server
        $server = SendingServer::findByUid($uid)->mapType();

        // authorize
        if (!$request->user()->admin->can('update', $server)) {
            return $this->notAuthorized();
        }

        // Save current user info
        $server->fill($request->all());

        // default sever quota
        if ($request->options) {
            $server->setOptions($request->options); // options = json_encode($request->options);
            $server->updateIdentitiesList($request->options);
        }

        // Sening limit
        if ($request->options['sending_limit'] != 'custom' && $request->options['sending_limit'] != 'current') {
            $limits = SendingServer::sendingLimitValues()[$request->options['sending_limit']];
            $server->quota_value = $limits['quota_value'];
            $server->quota_unit = $limits['quota_unit'];
            $server->quota_base = $limits['quota_base'];
        }

        // save posted data
        $this->validate($request, $server->getConfigRules());

        // bounce / feedback hanlder nullable
        if (empty($request->bounce_handler_id)) {
            $server->bounce_handler_id = null;
        }
        if (empty($request->feedback_loop_handler_id)) {
            $server->feedback_loop_handler_id = null;
        }

        if ($server->save()) {
            $request->session()->flash('alert-success', trans('messages.sending_server.updated'));

            if ($server->isExtended()) {
                return redirect($server->getEditUrl());
            } else {
                return redirect()->action('Admin\SendingServerController@edit', [$server->uid, $server->type]);
            }
        }
    }

    /**
     * Sending Limit Form.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function awsRegionHost(Request $request)
    {
        if ($request->uid) {
            $server = SendingServer::findByUid($request->uid);
        } else {
            $server = new SendingServer();
        }

        foreach (SendingServer::awsRegionSelectOptions() as $option) {
            if (isset($option['host']) && $option['value'] == $request->aws_region) {
                $server->host = $option['host'];
            }
        }
        return view('admin.sending_servers.form._aws_region_host', [
            'server' => $server,
        ]);
    }

    /**
     * Add domain to sending server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function addDomain(Request $request, $uid)
    {
        $server = SendingServer::findByUid($request->uid);

        // save posted data
        if ($request->isMethod('post')) {
            $valid = true;

            if (checkEmail($request->domain)) {
                // validation
                $validator = \Validator::make($request->all(), [
                    'domain' => 'required|email',
                ]);

                if (in_array(strtolower($request->domain), $server->getVerifiedIdentities())) {
                    $validator->errors()->add('domain', trans('messages.sending_identity.exist_error'));
                    $valid = false;
                }

                if (!$valid || $validator->fails()) {
                    return redirect()->action('Admin\SendingServerController@addDomain', $server->uid)
                                ->withErrors($validator)
                                ->withInput();
                }
            } else {
                // validation
                $validator = \Validator::make($request->all(), [
                    'domain' => 'required|regex:'.SendingDomain::VALIDATION_REGEXP,
                ]);

                if (in_array(strtolower($request->domain), $server->getVerifiedIdentities())) {
                    $validator->errors()->add('domain', trans('messages.sending_identity.exist_error'));
                    $valid = false;
                }

                if (!$valid || $validator->fails()) {
                    return redirect()->action('Admin\SendingServerController@addDomain', $server->uid)
                                ->withErrors($validator)
                                ->withInput();
                }
            }

            $server->addIdentity(strtolower($request->domain));

            $request->session()->flash('alert-success', trans('messages.sending_server.updated'));
            return;
        }

        return view('admin.sending_servers.add_domain', [
            'server' => $server,
        ]);
    }

    /**
     * Remove domain from sending server.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function removeDomain(Request $request, $uid, $identity)
    {
        $server = SendingServer::findByUid($request->uid)->mapType();
        $server->removeIdentity(base64_decode($identity));

        $request->session()->flash('alert-success', trans('messages.sending_server.domain.removed'));
        if ($server->isExtended()) {
            return redirect($server->getEditUrl());
        } else {
            return redirect()->action('Admin\SendingServerController@edit', [$server->uid, $server->type]);
        }
    }

    /**
     * Dropbox list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function fromDropbox(Request $request)
    {
        $server = SendingServer::findByUid($request->uid);

        $droplist = $server->verifiedIdentitiesDroplist(strtolower(trim($request->keyword)));
        return response()->json($droplist);
    }
}
