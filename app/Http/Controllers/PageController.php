<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Acelle\Events\MailListSubscription;
use Acelle\Model\Setting;
use Acelle\Model\MailList;
use Acelle\Model\IpLocation;
use Acelle\Library\StringHelper;

class PageController extends Controller
{
    /**
     * Redirect page if use outside url.
     */
    public function checkOutsideUrlRedirect($page)
    {
        if ($page->use_outside_url) {
            return redirect($page->outside_url);
        }
    }

    /**
     * Update list page content.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->list_uid);

        // authorize
        if (\Gate::denies('update', $list)) {
            return $this->notAuthorized();
        }

        $layout = \Acelle\Model\Layout::where('alias', $request->alias)->first();
        $page = \Acelle\Model\Page::findPage($list, $layout);

        // storing
        if ($request->isMethod('post')) {
            $page->fill($request->all());

            $validate = 'required';
            foreach ($layout->tags() as $tag) {
                if ($tag['required']) {
                    $validate .= '|substring:'.$tag['name'];
                }
            }

            $rules = array();

            // Check if use outside url
            if ($request->use_outside_url) {
                $rules['outside_url'] = 'active_url';
            } else {
                $rules['content'] = $validate;
                $rules['subject'] = 'required';
            }

            // Validation
            $this->validate($request, $rules);

            // option
            if ($request->term) {
                $list->setEmbeddedFormOption('term', $request->term);
                $list->setEmbeddedFormOption('enable_term', $request->enable_term);
            }

            // save
            $page->customer_id = $list->customer->id;
            $page->save();

            // Log
            $page->log('updated', $request->user()->customer);

            $request->session()->flash('alert-success', trans('messages.page.updated'));

            return redirect()->action('PageController@update', array('list_uid' => $list->uid, 'alias' => $layout->alias));
        }

        // return back
        $page->fill($request->old());

        return view('pages.update', [
            'list' => $list,
            'page' => $page,
            'layout' => $layout,
        ]);
    }

    /**
     * Preview page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->list_uid);

        // authorize
        if (\Gate::denies('update', $list)) {
            return $this->notAuthorized();
        }

        $layout = \Acelle\Model\Layout::where('alias', $request->alias)->first();
        $page = \Acelle\Model\Page::findPage($list, $layout);
        $page->content = $request->content;

        // render content
        $page->renderContent();

        return view('pages.preview_'.$layout->type, [
            'list' => $list,
            'page' => $page,
        ]);
    }

    /**
     * Sign up form page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function signUpForm(Request $request)
    {
        $customerUid = $request->customer_uid;
        $customer = \Acelle\Model\Customer::findByUid($customerUid);

        if (is_null($customer)) {
            throw new \Exception('Cannot find customer');
        } else {
            $customer->setUserDbConnection();
        }

        $list = \Acelle\Model\MailList::findByUid($request->list_uid);
        $layout = \Acelle\Model\Layout::findByAlias('sign_up_form');
        $page = \Acelle\Model\Page::findPage($list, $layout);

        // Language
        if ($list->customer && $list->customer->language) {
            \App::setLocale($list->customer->language->code);
            \Carbon\Carbon::setLocale($list->customer->language->code);
        }

        // Get old post values
        $values = [];
        if (null !== $request->old()) {
            foreach ($request->old() as $key => $value) {
                if (is_array($value)) {
                    $values[str_replace('[]', '', $key)] = implode(',', $value);
                } else {
                    $values[$key] = $value;
                }
            }
        }

        $page->renderContent($values);

        // Create subscriber
        if ($request->isMethod('post')) {
            // Captcha check
            if (\Acelle\Model\Setting::isListSignupCaptchaEnabled()) {
                // @hCaptcha
                if (\Acelle\Model\Setting::getCaptchaProvider() == 'hcaptcha') {
                    $hcaptcha = \Acelle\Hcaptcha\Client::initialize();
                    $success = $hcaptcha->check($request);
                    if (!$success) {
                        return view('somethingWentWrong', [
                            'message' => trans('messages.list.sign_up.invalid_captcha'),
                            'redirect_url' => action('PageController@signUpForm', ['list_uid' => $list->uid, 'customer_uid' => $list->customer->uid], false),
                        ]);
                    }
                } else {
                    $success = \Acelle\Library\Tool::checkReCaptcha($request);
                    if (!$success) {
                        return view('somethingWentWrong', [
                            'message' => trans('messages.list.sign_up.invalid_captcha'),
                            'redirect_url' => action('PageController@signUpForm', ['list_uid' => $list->uid, 'customer_uid' => $list->customer->uid], false),
                        ]);
                    }
                }
            }

            try {
                list($validator, $subscriber) = $list->subscribe($request, MailList::SOURCE_WEB);
            } catch (\Exception $ex) {
                return view('somethingWentWrong', ['message' => $ex->getMessage()]);
            }

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            // Timeline record
            \Acelle\Model\Timeline::recordSignUpFormOptIn($subscriber);

            if ($request->redirect_url) {
                return redirect()->away($request->redirect_url);
            } elseif ($list->subscribe_confirmation && !$subscriber->isSubscribed()) {
                // tell subscriber to check email for confirmation
                return redirect()->action('PageController@signUpThankyouPage', ['list_uid' => $list->uid, 'subscriber_id' => $subscriber->id, 'customer_uid' => $list->customer->uid]);
            } else {
                // All done, confirmed
                return redirect()->action(
                    'PageController@signUpConfirmationThankyou',
                    [
                        'list_uid' => $list->uid,
                        'id' => $subscriber->id,
                        'customer_uid' => $list->customer->uid,
                    ]
                );
            }
        }

        return view('pages.form', [
            'list' => $list,
            'page' => $page,
            'values' => $values,
        ]);
    }

    /**
     * Sign up thank you page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function signUpThankyouPage(Request $request)
    {
        $customerUid = $request->customer_uid;
        $customer = \Acelle\Model\Customer::findByUid($customerUid);

        if (is_null($customer)) {
            throw new \Exception('Cannot find customer');
        } else {
            $customer->setUserDbConnection();
        }

        $list = \Acelle\Model\MailList::findByUid($request->list_uid);
        $layout = \Acelle\Model\Layout::findByAlias('sign_up_thankyou_page');
        $page = \Acelle\Model\Page::findPage($list, $layout);
        $subscriber = \Acelle\Model\Subscriber::find($request->subscriber_id);

        // Language
        if ($list->customer && $list->customer->language) {
            \App::setLocale($list->customer->language->code);
            \Carbon\Carbon::setLocale($list->customer->language->code);
        }

        // redirect if use outside url
        if ($page->use_outside_url) {
            return redirect($page->getOutsideUrlWithUid($subscriber));
        }

        $page->renderContent(null, $subscriber);

        return view('pages.default', [
            'list' => $list,
            'page' => $page,
        ]);
    }

    /**
     * Sign up confirmation thank you page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function signUpConfirmationThankyou(Request $request)
    {
        $customerUid = $request->customer_uid;
        $customer = \Acelle\Model\Customer::findByUid($customerUid);

        if (is_null($customer)) {
            throw new \Exception('Cannot find customer');
        } else {
            $customer->setUserDbConnection();
        }

        $list = \Acelle\Model\MailList::findByUid($request->list_uid);
        $layout = \Acelle\Model\Layout::findByAlias('sign_up_confirmation_thankyou');
        $page = \Acelle\Model\Page::findPage($list, $layout);
        $subscriber = \Acelle\Model\Subscriber::find($request->id);

        // Language
        if ($list->customer && $list->customer->language) {
            \App::setLocale($list->customer->language->code);
            \Carbon\Carbon::setLocale($list->customer->language->code);
        }

        if (is_null($subscriber)) {
            echo "Subscriber no longer exists";
            return;
        }

        $page->renderContent(null, $subscriber);
        $subscriber->confirm();

        // redirect if use outside url
        if ($page->use_outside_url) {
            return redirect($page->getOutsideUrlWithUid($subscriber));
        }

        return view('pages.default', [
            'list' => $list,
            'page' => $page,
        ]);
    }

    /**
     * Unsibscribe form.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function unsubscribeForm(Request $request)
    {
        $messageId = StringHelper::base64UrlDecode($request->message_id);
        $customerUid = \Acelle\Library\StringHelper::extractCustomerUidFromMessageId($messageId);
        $customer = \Acelle\Model\Customer::findByUid($customerUid);
        if (!is_null($customer)) {
            $customer->setUserDbConnection();
        }

        // IMPORTANT: it does not create TrackingLog!!!
        $subscriber = \Acelle\Model\Subscriber::find($request->id);
        $list = $subscriber->mailList;
        $layout = \Acelle\Model\Layout::findByAlias('unsubscribe_form');
        $page = \Acelle\Model\Page::findPage($list, $layout);

        // Language
        if ($list->customer && $list->customer->language) {
            \App::setLocale($list->customer->language->code);
            \Carbon\Carbon::setLocale($list->customer->language->code);
        }

        $page->renderContent(null, $subscriber, $messageId);

        if ($request->isMethod('post')) {
            // User Tracking Information
            $trackingInfo = [
                'message_id' => $messageId,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            ];

            // GeoIP information
            $location = IpLocation::add($_SERVER['REMOTE_ADDR']);
            if (!is_null($location)) {
                $trackingInfo['ip_address'] = $location->ip_address;
            }

            $subscriber->unsubscribe($trackingInfo);

            // Timeline record
            \Acelle\Model\Timeline::recordUnsubscribedFromListUnsubscribeForm($subscriber);

            return redirect()->action('PageController@unsubscribeSuccessPage', ['list_uid' => $list->uid, 'id' => $subscriber->id, 'customer_uid' => $list->customer->uid]);
        }

        return view('pages.form', [
            'list' => $list,
            'page' => $page,
        ]);
    }

    /**
     * Unsibscribe form.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function unsubscribeSuccessPage(Request $request)
    {
        $customerUid = $request->customer_uid;
        $customer = \Acelle\Model\Customer::findByUid($customerUid);

        if (is_null($customer)) {
            throw new \Exception('Cannot find customer');
        } else {
            $customer->setUserDbConnection();
        }

        $list = \Acelle\Model\MailList::findByUid($request->list_uid);
        $layout = \Acelle\Model\Layout::findByAlias('unsubscribe_success_page');
        $page = \Acelle\Model\Page::findPage($list, $layout);
        $subscriber = \Acelle\Model\Subscriber::find($request->id);

        // Language
        if ($list->customer && $list->customer->language) {
            \App::setLocale($list->customer->language->code);
            \Carbon\Carbon::setLocale($list->customer->language->code);
        }

        $page->renderContent(null, $subscriber);

        // redirect if use outside url
        if ($page->use_outside_url) {
            return redirect($page->getOutsideUrlWithUid($subscriber));
        }

        return view('pages.default', [
            'list' => $list,
            'page' => $page,
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * Update profile form.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function profileUpdateForm(Request $request)
    {
        $customerUid = $request->customer_uid;
        $customer = \Acelle\Model\Customer::findByUid($customerUid);

        if (is_null($customer)) {
            throw new \Exception("Cannot find customer: '{$customerUid}'");
        } else {
            $customer->setUserDbConnection();
        }

        $list = \Acelle\Model\MailList::findByUid($request->list_uid);
        $layout = \Acelle\Model\Layout::findByAlias('profile_update_form');
        $page = \Acelle\Model\Page::findPage($list, $layout);
        $subscriber = \Acelle\Model\Subscriber::find($request->id);

        if (is_null($subscriber) || is_null($list)) {
            abort(404);
        }

        // Language
        if ($list->customer && $list->customer->language) {
            \App::setLocale($list->customer->language->code);
            \Carbon\Carbon::setLocale($list->customer->language->code);
        }

        $values = [];

        // Fetch subscriber fields to values
        foreach ($list->fields as $key => $field) {
            $value = $subscriber->getValueByField($field);
            if (is_array($value)) {
                $values[str_replace('[]', '', $key)] = implode(',', $value);
            } else {
                $values[$field->tag] = $value;
            }
        }

        // Get old post values
        if (null !== $request->old()) {
            foreach ($request->old() as $key => $value) {
                if (is_array($value)) {
                    $values[str_replace('[]', '', $key)] = implode(',', $value);
                } else {
                    $values[$key] = $value;
                }
            }
        }

        $page->renderContent($values, $subscriber);

        if ($request->isMethod('post')) {
            $rules = $subscriber->getRules();
            $rules['EMAIL'] .= '|in:'.$subscriber->email;
            // Validation
            $this->validate($request, $rules);

            // Update field
            $subscriber->updateFields($request->all());

            return redirect()->action('PageController@profileUpdateSuccessPage', ['list_uid' => $list->uid, 'id' => $subscriber->id, 'customer_uid' => $list->customer->uid]);
        }

        return view('pages.form', [
            'list' => $list,
            'page' => $page,
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * Update profile success.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function profileUpdateSuccessPage(Request $request)
    {
        $customerUid = $request->customer_uid;
        $customer = \Acelle\Model\Customer::findByUid($customerUid);

        if (is_null($customer)) {
            throw new \Exception('Cannot find customer');
        } else {
            $customer->setUserDbConnection();
        }

        $list = \Acelle\Model\MailList::findByUid($request->list_uid);
        $layout = \Acelle\Model\Layout::findByAlias('profile_update_success_page');
        $page = \Acelle\Model\Page::findPage($list, $layout);
        $subscriber = \Acelle\Model\Subscriber::find($request->id);

        // Language
        if ($list->customer && $list->customer->language) {
            \App::setLocale($list->customer->language->code);
            \Carbon\Carbon::setLocale($list->customer->language->code);
        }

        $page->renderContent(null, $subscriber);

        // redirect if use outside url
        if ($page->use_outside_url) {
            return redirect($page->getOutsideUrlWithUid($subscriber));
        }

        return view('pages.default', [
            'list' => $list,
            'page' => $page,
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * Send update profile request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function profileUpdateEmailSent(Request $request)
    {
        $customerUid = $request->customer_uid;
        $customer = \Acelle\Model\Customer::findByUid($customerUid);

        if (is_null($customer)) {
            throw new \Exception('Cannot find customer');
        } else {
            $customer->setUserDbConnection();
        }

        $list = \Acelle\Model\MailList::findByUid($request->list_uid);
        $layout = \Acelle\Model\Layout::findByAlias('profile_update_email_sent');
        $page = \Acelle\Model\Page::findPage($list, $layout);
        $subscriber = \Acelle\Model\Subscriber::find($request->id);

        // Language
        if ($list->customer && $list->customer->language) {
            \App::setLocale($list->customer->language->code);
            \Carbon\Carbon::setLocale($list->customer->language->code);
        }

        $page->renderContent(null, $subscriber);

        // SEND EMAIL
        try {
            $list->sendProfileUpdateEmail($subscriber);
        } catch (\Exception $ex) {
            return view('somethingWentWrong', ['message' => $ex->getMessage()]);
        }

        // redirect if use outside url
        if ($page->use_outside_url) {
            return redirect($page->getOutsideUrlWithUid($subscriber));
        }

        return view('pages.default', [
            'list' => $list,
            'page' => $page,
            'subscriber' => $subscriber,
        ]);
    }

    public function restoreDefault(Request $request)
    {
        $list = \Acelle\Model\MailList::findByUid($request->list_uid);

        // authorize
        if (\Gate::denies('update', $list)) {
            return $this->notAuthorized();
        }

        $layout = \Acelle\Model\Layout::where('alias', $request->alias)->first();
        $page = \Acelle\Model\Page::findPage($list, $layout);

        $page->delete();

        $request->session()->flash('alert-success', trans('messages.page.reset.success'));
        return redirect()->action('PageController@update', array('list_uid' => $list->uid, 'alias' => $layout->alias));
    }
}
