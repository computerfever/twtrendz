<?php

namespace Acelle\Http\Controllers\Api;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Acelle\Events\MailListSubscription;
use Acelle\Model\Subscriber;
use Acelle\Model\MailList;
use Acelle\Model\IpLocation;

/**
 * /api/v1/lists/{list_id}/subscribers - API controller for managing list's subscribers.
 */
class SubscriberController extends Controller
{
    /**
     * Display all list's subscribers.
     *
     * GET /api/v1/lists/{list_id}/subscribers
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $list_id List's id
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = \Auth::guard('api')->user();
        $list = \Acelle\Model\MailList::findByUid($request->list_uid);

        // authorize
        if (!$list) {
            return \Response::json(array('message' => trans('List not found')), 400);
        }
        // authorize
        if (!$user->can('read', $list)) {
            return \Response::json(array('message' => 'You\'re not authorized to access this resources'), 401);
        }
        if (isset($request->per_page)) {
            $per_page = $request->per_page;
        } else {
            $per_page = \Acelle\Model\Subscriber::$itemsPerPage;
        }

        $subscribers = \Acelle\Model\Subscriber::search($request, $user->customer)
            ->where('mail_list_id', '=', $list->id)
            ->paginate($per_page);

        $result = [];
        foreach ($subscribers as $subscriber) {
            $row = [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status,
            ];

            foreach ($list->fields as $field) {
                if ($field->tag != 'EMAIL') {
                    $row[$field->tag] = $subscriber->getValueByField($field);
                }
            }

            $result[] = $row;
        }

        return \Response::json($result, 200);
    }

    /**
     * Create subscriber for a mail list.
     *
     * POST /api/v1/lists/{list_id}/subscribers
     *
     * @param \Illuminate\Http\Request $request All subscriber information: EMAIL (required), FIRST_NAME (?), LAST_NAME (?),... (depending on the list fields configuration)
     * @param string                   $list_id List's id
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $user = \Auth::guard('api')->user();
            $list = MailList::findByUid($request->list_uid);

            // authorize
            if (!$list) {
                return \Response::json(array('message' => trans('List not found')), 400);
            }

            if (!$user->can('update', [ $list, $more = 1 ])) {
                return response()->json(['message' => 'Unauthorized!'], 401);
            }

            if (!$user->can('addMoreSubscribers', [ $list, $more = 1 ])) {
                return response()->json(['message' => 'List quota exceeded'], 403);
            }

            // status is invalid
            if ($request->has('status')) {
                if (!in_array($request->status, [
                    Subscriber::STATUS_SUBSCRIBED,
                    Subscriber::STATUS_UNSUBSCRIBED,
                    Subscriber::STATUS_UNCONFIRMED,
                ])) {
                    return response()->json(['message' => 'Subscriber status is not valid, allowed values are: subscribed, unsubscribed, unconfirmed'], 403);
                }
            }

            // Validate & and create subscriber
            // Throw ValidationError exception in case of failure
            list($validator, $subscriber) = $list->subscribe($request, MailList::SOURCE_API);

            if (is_null($subscriber)) {
                return response()->json($validator->messages(), 403);
            }

            // update tags
            if ($request->tag) {
                $subscriber->updateTags(explode(',', $request->tag));
            }

            // update status \ overides default one
            if ($request->has('status')) {
                $subscriber->status = $request->status;
                $subscriber->save();
            }

            // Timeline record
            \Acelle\Model\Timeline::recordApiAdded($subscriber, $user->customer);

            return \Response::json(array(
                'status' => 1,
                'message' => ($list->subscribe_confirmation) ? trans('messages.subscriber.confirmation_email_sent') : trans('messages.subscriber.created'),
                'subscriber_id' => $subscriber->id,
            ), 200);
        } catch (\Exception $ex) {
            return \Response::json(array('message' => $ex->getMessage()), 500);
        }
    }

    /**
     * Display the specified subscriber information.
     *
     * GET /api/v1/subscribers/{id}
     *
     * @param string $list_id List's id
     * @param string $id      Subsciber's id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = \Auth::guard('api')->user();

        $subscriber = \Acelle\Model\Subscriber::where('id', '=', $id)
            ->first();

        $list = $subscriber->mailList;

        // check if item exists
        if (!$subscriber) {
            return \Response::json(array('message' => 'Subscriber not found'), 404);
        }

        // authorize
        if (!$user->can('read', $subscriber)) {
            return \Response::json(array('message' => 'Unauthorized'), 401);
        }

        // result
        $result = [
            'id' => $subscriber->id,
            'email' => $subscriber->email,
            'status' => $subscriber->status,
            'source' => $subscriber->from,
            'ip_address' => $subscriber->ip,
            'tags' => $subscriber->getTags(),
        ];

        foreach ($list->fields as $field) {
            if ($field->tag != 'EMAIL') {
                $subscriber[$field->tag] = $subscriber->getValueByField($field);
            }
        }

        return \Response::json(['subscriber' => $result], 200);
    }

    /**
     * Subscribe a subscriber.
     *
     * PATCH /api/v1/lists/{list_id}/subscribers/{id}/subscribe
     *
     * @param string $list_id List's id
     * @param string $id      Subsciber's id
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribe($list_uid, $id)
    {
        $user = \Auth::guard('api')->user();
        $subscriber = \Acelle\Model\Subscriber::find($id);

        // check if item exists
        if (!$subscriber) {
            return \Response::json(array('status' => 0, 'message' => 'Subscriber not found'), 404);
        }

        $list = $subscriber->mailList;

        // check if item subscribed
        if ($subscriber->status == 'subscribed') {
            return \Response::json(array('status' => 0, 'message' => 'Already subscribed'), 409);
        }

        // authorize
        if (!$user->can('subscribe', $subscriber)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        // User Tracking Information
        $trackingInfo = [
            'message_id' => null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ];

        // GeoIP information
        $location = IpLocation::add($_SERVER['REMOTE_ADDR']);
        if (!is_null($location)) {
            $trackingInfo['ip_address'] = $location->ip_address;
        }

        $subscriber->subscribe($trackingInfo);

        // Timeline record
        \Acelle\Model\Timeline::recordApiSubscribed($subscriber, $user->customer);

        // Log
        $subscriber->log('subscribed', $user->customer);

        return \Response::json(array('status' => 1, 'message' => 'Subscribed'), 200);
    }

    /**
     * Unsubscribe a subscriber.
     *
     * PATCH /api/v1/lists/{list_id}/subscribers/{id}/unsubscribe
     *
     * @param string $list_id List's id
     * @param string $id      Subsciber's id
     *
     * @return \Illuminate\Http\Response
     */
    public function unsubscribe($list_uid, $id)
    {
        $user = \Auth::guard('api')->user();
        $subscriber = \Acelle\Model\Subscriber::find($id);

        // check if item exists
        if (!$subscriber) {
            return \Response::json(array('status' => 0, 'message' => 'Subscriber not found'), 404);
        }

        $list = $subscriber->mailList;

        // check if item unsubscribed
        if ($subscriber->status == 'unsubscribed') {
            return \Response::json(array('status' => 0, 'message' => 'Already unsubscribed'), 409);
        }

        // authorize
        if (!$user->can('unsubscribe', $subscriber)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        // User Tracking Information
        $trackingInfo = [
            'message_id' => null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ];

        // GeoIP information
        $location = IpLocation::add($_SERVER['REMOTE_ADDR']);
        if (!is_null($location)) {
            $trackingInfo['ip_address'] = $location->ip_address;
        }

        // Actually Unsubscribe with tracking information
        $subscriber->unsubscribe($trackingInfo);

        // Log
        $subscriber->log('unsubscribed', $user->customer);

        return \Response::json(array('status' => 1, 'message' => 'Unsubscribed'), 200);
    }

    /**
     * Unsubscribe a subscriber.
     *
     * PATCH /api/v1/lists/{list_id}/subscribers/{id}/unsubscribe
     *
     * @param string $list_id List's id
     * @param string $id      Subsciber's id
     *
     * @return \Illuminate\Http\Response
     */
    public function unsubscribeEmail($list_uid, $email)
    {
        $email = strtolower(trim($email));

        // check valid email address
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return \Response::json(array('status' => 0, 'message' => 'Email is not valid'), 400);
        }

        $user = \Auth::guard('api')->user();
        $list = \Acelle\Model\MailList::findByUid($list_uid);
        $subscribers = $list->subscribers()->byEmail($email);

        // check if item exists
        if (!$subscribers->count()) {
            return \Response::json(array('status' => 0, 'message' => 'Subscriber not found'), 404);
        }

        // User Tracking Information
        $trackingInfo = [
            'message_id' => null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        ];

        // GeoIP information
        $location = IpLocation::add($_SERVER['REMOTE_ADDR']);
        if (!is_null($location)) {
            $trackingInfo['ip_address'] = $location->ip_address;
        }

        //
        foreach ($subscribers->get() as $subscriber) {
            // check if item unsubscribed
            if ($subscriber->status == 'unsubscribed') {
                continue;
            }

            // authorize
            if (!$user->can('unsubscribe', $subscriber)) {
                continue;
            }

            // Actually Unsubscribe with tracking information
            $subscriber->unsubscribe($trackingInfo);

            // Log
            $subscriber->log('unsubscribed', $user->customer);
        }

        return \Response::json(array('status' => 1, 'message' => "{$email} was unsubscribed"), 200);
    }

    /**
     * Delete a subscriber.
     *
     * DELETE /api/v1/lists/{list_id}/subscribers/{id}/delete
     *
     * @param string $list_id List's id
     * @param string $id      Subsciber's id
     *
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $user = \Auth::guard('api')->user();
        $subscriber = \Acelle\Model\Subscriber::find($id);

        // check if item exists
        if (!$subscriber) {
            return \Response::json(array('status' => 0, 'message' => 'Subscriber not found'), 404);
        }

        $list = $subscriber->mailList;

        // check if item exists
        if (!$subscriber) {
            return \Response::json(array('status' => 0, 'message' => 'Subscriber not found'), 404);
        }

        // authorize
        if (!$user->can('delete', $subscriber)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        // Log
        $subscriber->log('deleted', $user->customer);

        // Unsubscribe
        $subscriber->delete();

        // update MailList cache
        event(new \Acelle\Events\MailListUpdated($list));

        return \Response::json(array('status' => 1, 'message' => 'Deleted'), 200);
    }

    /**
     * Display the specified subscriber by email.
     *
     * GET /api/v1/lists/{list_id}/subscribers/{id}
     *
     * @param string $list_id List's id
     * @param string $id      Subsciber's id
     *
     * @return \Illuminate\Http\Response
     */
    public function showByEmail($email)
    {
        $user = \Auth::guard('api')->user();

        $subscribers = \Acelle\Model\Subscriber::where('email', '=', $email)->get();

        // check if item exists
        if (empty($subscribers)) {
            return \Response::json(array('message' => 'Subscriber not found'), 404);
        }

        $rows = [];
        foreach ($subscribers as $subscriber) {
            // authorize
            if ($user->can('read', $subscriber)) {
                // subscriber
                $row = [
                    'id' => $subscriber->id,
                    'list_uid' => $subscriber->mailList->uid,
                    'email' => $subscriber->email,
                    'status' => $subscriber->status,
                    'source' => $subscriber->from,
                    'ip_address' => $subscriber->ip,
                ];

                foreach ($subscriber->mailList->fields as $field) {
                    if ($field->tag != 'EMAIL') {
                        $row[$field->tag] = $subscriber->getValueByField($field);
                    }
                }

                $rows[] = $row;
            }
        }

        return \Response::json(['subscribers' => $rows], 200);
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
        $user = \Auth::guard('api')->user();
        $customer = $user->customer;
        $subscriber = \Acelle\Model\Subscriber::find($id);

        // authorize
        if (!$user->can('update', $subscriber)) {
            return \Response::json(array('message' => 'Unauthorized'), 401);
        }

        // status is invalid
        if ($request->has('status')) {
            if (!in_array($request->status, [
                Subscriber::STATUS_SUBSCRIBED,
                Subscriber::STATUS_UNSUBSCRIBED,
                Subscriber::STATUS_UNCONFIRMED,
            ])) {
                return response()->json(['message' => 'Subscriber status is not valid, allowed values are: subscribed, unsubscribed, unconfirmed'], 403);
            }
        }

        $validator = \Validator::make($request->all(), $subscriber->mailList->getFieldRules());
        if ($validator->fails()) {
            return response()->json($validator->messages(), 403);
        }

        // Update field
        $subscriber->updateFields($request->all());

        // update tags
        if ($request->tag) {
            $subscriber->updateTags(explode(',', $request->tag));
        }

        // update status \ overides default one
        if ($request->has('status')) {
            $subscriber->status = $request->status;
            $subscriber->save();
        }

        // Log
        $subscriber->log('updated', $customer);

        // update MailList cache
        event(new \Acelle\Events\MailListUpdated($subscriber->mailList));

        return \Response::json(array(
            'status' => 1,
            'message' => trans('messages.subscriber.updated'),
            'subscriber_id' => $subscriber->id,
        ), 200);
    }

    /**
     * Add subscriber tag.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function addTag(Request $request, $id)
    {
        $user = \Auth::guard('api')->user();
        $subscriber = \Acelle\Model\Subscriber::find($id);

        // authorize
        if (!$user->can('update', $subscriber)) {
            return \Response::json(array('message' => 'Unauthorized'), 401);
        }

        // update tags
        if ($request->tag) {
            $subscriber->addTags(explode(',', $request->tag));
        }

        // add tag
        return \Response::json(array(
            'status' => 1,
            'message' => trans('messages.subscriber.tag_added'),
            'subscriber_id' => $subscriber->id,
            'tags' => $subscriber->getTags(),
        ), 200);
    }

    public function removeTag(Request $request, $id)
    {
        $user = \Auth::guard('api')->user();
        $subscriber = \Acelle\Model\Subscriber::find($id);

        // authorize
        if (!$user->can('update', $subscriber)) {
            return \Response::json(array('message' => 'Unauthorized'), 401);
        }

        // update tags
        if ($request->tag) {
            $subscriber->removeTags(explode(',', $request->tag));
        }

        // add tag
        return \Response::json(array(
            'status' => 1,
            'message' => trans('messages.subscriber.tag_removed'),
            'subscriber_id' => $subscriber->id,
            'tags' => $subscriber->getTags(),
        ), 200);
    }
}
