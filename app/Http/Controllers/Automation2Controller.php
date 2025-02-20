<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Acelle\Http\Requests;
use Acelle\Model\Automation2;
use Acelle\Model\MailList;
use Acelle\Model\Email;
use Acelle\Model\Attachment;
use Acelle\Model\Template;
use Acelle\Model\Subscriber;
use Acelle\Model\Setting;
use Illuminate\Support\Facades\Storage;
use Acelle\Model\TemplateCategory;
use Acelle\Jobs\ForceTriggerAutomation;
use Exception;
use Acelle\Model\Customer as MasterCustomer;

class Automation2Controller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // authorize
        if (\Gate::denies('list', Automation2::class)) {
            return $this->notAuthorized();
        }

        return view('automation2.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        // authorize
        if (\Gate::denies('list', Automation2::class)) {
            return $this->notAuthorized();
        }

        $automations = $request->user()->customer->local()->automation2s()
            ->search($request->keyword)
            ->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);

        return view('automation2._list', [
            'automations' => $automations,
        ]);
    }

    public function wizardTrigger(Request $request)
    {
        $types = Automation2::getTriggerTypes();

        if ($request->trigger_type) {
            $automation = new Automation2();

            return view('automation2.wizardTriggerOption', [
                'automation' => $automation,
                'trigger' => $automation->getTrigger(),
                'trigger_type' => $request->trigger_type,
                'rules' => $this->triggerRules()[$request->trigger_type],
            ]);
        }

        return view('automation2.wizardTrigger', [
            'types' => $types,
        ]);
    }

    public function wizardTriggerOption(Request $request)
    {
        $localCustomer = $request->user()->customer->local();

        // saving
        if ($request->isMethod('post')) {
            $rules = $this->triggerRules()[$request->options['key']];

            // make validator
            $validator = Validator::make($request->all(), array_merge($rules, [
                'mail_list_uid' => 'required',
            ]));

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.wizardTriggerOption', [
                    'trigger_type' => $request->trigger_type,
                    'errors' => $validator->errors(),
                ], 400);
            }

            $automation = $localCustomer->newDefaultAutomation2();

            return view('automation2.wizard', [
                'automation' => $automation,
                'trigger_type' => $request->trigger_type,
            ]);
        }

        return view('automation2.wizardTriggerOption', [
            'trigger_type' => $request->trigger_type,
        ]);
    }

    public function wizardListFieldSelect(Request $request)
    {
        if (!$request->list_uid) {
            return null;
        }

        $list = \Acelle\Model\MailList::findByUid($request->list_uid);

        return view('automation2.wizardListFieldSelect', [
            'list' => $list,
        ]);
    }

    public function wizard(Request $request)
    {
        $localCustomer = $request->user()->customer->local();
        $automation = $localCustomer->newDefaultAutomation2();

        // authorize
        if (\Gate::denies('create', $automation)) {
            return $this->noMoreItem();
        }

        // saving
        if ($request->isMethod('post')) {
            $params = $request->all();

            if (isset($params['options'])) {
                $params['options'] = $this->prepareOptions($params['options'], $localCustomer->master());
            }

            $validator = $automation->createFromArray($params);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.wizard', [
                    'automation' => $automation,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.created.redirecting'),
                'url' => action('Automation2Controller@edit', ['uid' => $automation->uid])
            ], 201);
        }

        return view('automation2.wizard', [
            'automation' => $automation,
        ]);
    }

    /**
     * Update automation.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uid)
    {
        // find automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // fill before save
        $automation->fillRequest($request);

        // make validator
        $validator = Validator::make($request->all(), $automation->rules());

        // redirect if fails
        if ($validator->fails()) {
            return response()->view('automation2.settings', [
                'automation' => $automation,
                'errors' => $validator->errors(),
            ], 400);
        }

        // pass validation and save
        $automation->updateMailList(MailList::findByUid($request->mail_list_uid));

        // save
        $automation->save();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.updated'),
        ], 201);
    }

    /**
     * Update automation.
     *
     * @return \Illuminate\Http\Response
     */
    public function saveData(Request $request, $uid)
    {
        // find automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        if ($request->resetTrigger) {
            $automation->saveDataAndResetTriggers($request->data);
        } else {
            $automation->saveData($request->data);
        }
    }

    /**
     * Creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $automation->updateCacheInBackground();

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.edit', [
            'automation' => $automation,
        ]);
    }

    /**
     * Automation settings in sidebar.
     *
     * @return \Illuminate\Http\Response
     */
    public function settings(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.settings', [
            'automation' => $automation,
        ]);
    }

    /**
     * Select trigger type popup.
     *
     * @return \Illuminate\Http\Response
     */
    public function triggerSelectPupop(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $types = Automation2::getTriggerTypes();

        return view('automation2.triggerSelectPupop', [
            'types' => $types,
            'automation' => $automation,
            'trigger' => $automation->getTrigger(),
        ]);
    }

    /**
     * Select trigger type confirm.
     *
     * @return \Illuminate\Http\Response
     */
    public function triggerSelectConfirm(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $rules = $this->triggerRules()[$request->key];

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.triggerSelectConfirm', [
            'key' => $request->key,
            'automation' => $automation,
            'trigger' => $automation->getTrigger(),
            'rules' => $rules,
        ]);
    }

    /**
     * Select trigger type.
     *
     * @return array
     */
    public function triggerRules()
    {
        return [
            'welcome-new-subscriber' => [],
            'say-happy-birthday' => [
                'options.before' => 'required',
                'options.at' => 'required',
                'options.field' => 'required',
            ],
            'specific-date' => [
                'options.date' => 'required',
                'options.at' => 'required',
            ],
            'say-goodbye-subscriber' => [],
            'api-3-0' => [],
            'subscriber-added-date' => [
                'options.delay' => 'required',
                'options.at' => 'required',
            ],
            'weekly-recurring' => [
                'options.days_of_week' => 'required',
                'options.at' => 'required',
            ],
            'monthly-recurring' => [
                'options.days_of_month' => 'required|array|min:1',
                'options.at' => 'required',
            ],
            'woo-abandoned-cart' => [
                'options.source_uid' => 'required',
            ],
            Automation2::TRIGGER_TAG_BASED => [
                'options.tags' => 'required',
            ],
            Automation2::TRIGGER_REMOVE_TAG => [
                'options.tags' => 'required',
            ],
            Automation2::TRIGGER_ATTRIBUTE_UPDATE => [
                'options.field_uid' => 'required',
                'options.value' => 'required',
            ],
        ];
    }

    /**
     * Validate trigger.
     *
     * @return \Illuminate\Http\Response
     */
    public function vaidateTrigger($request, $type)
    {
        $valid = true;

        $rules = $this->triggerRules()[$type];

        // make validator
        $validator = Validator::make($request->all(), $rules);

        $valid = $valid && !$validator->fails();

        return [$validator,  $valid];
    }

    /**
     * Select trigger type.
     *
     * @return \Illuminate\Http\Response
     */
    public function triggerSelect(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        list($validator, $result) = $this->vaidateTrigger($request, $request->options['key']);

        // redirect if fails
        if (!$result) {
            return response()->view('automation2.triggerSelectConfirm', [
                'key' => $request->options['key'],
                'automation' => $automation,
                'trigger' => $automation->getTrigger(),
                'rules' => $this->triggerRules()[$request->options['key']],
                'errors' => $validator->errors(),
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.trigger.added'),
            'title' => trans('messages.automation.trigger.title', [
                'title' => trans('messages.automation.trigger.tree.' . $request->options["key"])
            ]),
            'options' => $this->prepareOptions($request->options, $request->user()->customer), // master OK
            'rules' => $this->triggerRules()[$request->options['key']],
        ]);
    }

    public function prepareOptions($options, MasterCustomer $customer)
    {
        if (isset($options['key']) && $options['key'] == 'specific-date') {
            if (isset($options['date'])) {
                $date = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $options['date'] . ' ' . $options['at'], $customer->timezone);

                $options['date'] = $date->timezone(config('app.timezone'))->format(config('custom.date_format'));
                $options['at'] = $date->timezone(config('app.timezone'))->format(config('custom.time_format'));
            }
        }

        return $options;
    }

    /**
     * Select action type popup.
     *
     * @return \Illuminate\Http\Response
     */
    public function actionSelectPupop(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $types = [
            'send-an-email',
            'wait',
            'condition',
            'operation',
            'outgoing-webhook'
        ];

        return view('automation2.actionSelectPupop', [
            'types' => $types,
            'automation' => $automation,
            'hasChildren' => $request->hasChildren,
        ]);
    }

    public function waitCreate(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.waitCreate', [
            'automation' => $automation,
            'element' => $automation->getElement(),
        ]);
    }

    public function conditionCreate(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.conditionCreate', [
            'automation' => $automation,
            'element' => $automation->getElement(),
        ]);
    }

    /**
     * Select action type confirm.
     *
     * @return \Illuminate\Http\Response
     */
    public function conditionSetting(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.condition.setting', [
            'automation' => $automation,
            'element' => $automation->getElement($request->element_id),
        ]);
    }

    public function waitSave(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $delayOptions = \Acelle\Model\Automation2::getDelayOptions();
        $parts = explode(' ', $request->time);
        $title = trans('messages.time.wait_for') . ' ' . $parts[0] . ' ' . trans_choice('messages.time.' . $parts[1], $parts[0]);

        foreach ($delayOptions as $deplayOption) {
            if ($deplayOption['value'] == $request->time) {
                $title = trans('messages.automation.wait.delay.' . $request->time);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.action.added'),
            'title' => $title,
            'options' => [
                'key' => $request->key,
                'time' => $request->time,
            ],
        ]);
    }

    public function conditionSave(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        if ($request->type == 'open') {
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.action.added'),
                'title' => trans('messages.automation.action.condition.read_email.title'),
                'options' => [
                    'key' => $request->key,
                    'type' => $request->type,
                    'email' => empty($request->email) ? null : $request->email,
                    'wait' => $request->wait,
                ],
            ]);
        } elseif ($request->type == 'click') {
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.action.added'),
                'title' => trans('messages.automation.action.condition.click_link.title'),
                'options' => [
                    'key' => $request->key,
                    'type' => $request->type,
                    'email_link' => empty($request->email_link) ? null : $request->email_link,
                    'wait' => $request->wait,
                ],
            ]);
        } elseif ($request->type == 'cart_buy_anything') {
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.action.updated'),
                'title' => trans('messages.automation.action.condition.cart_buy_anything.title'),
                'options' => [
                    'key' => $request->key,
                    'type' => $request->type,
                ],
            ]);
        } elseif ($request->type == 'cart_buy_item') {
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.action.updated'),
                'title' => trans('messages.automation.action.condition.cart_buy_item.title', [
                    'item' => $request->item_title,
                ]),
                'options' => [
                    'key' => $request->key,
                    'type' => $request->type,
                    'item_id' => $request->item_id,
                    'item_title' => $request->item_title,
                ],
            ]);
        } else {
            throw new \Exception("Condition type {$request->type} not found!");
        }
    }

    /**
     * Edit trigger.
     *
     * @return \Illuminate\Http\Response
     */
    public function triggerEdit(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $rules = $this->triggerRules()[$request->key];

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            list($validator, $result) = $this->vaidateTrigger($request, $request->options['key']);

            // redirect if fails
            if (!$result) {
                return response()->view('automation2.triggerEdit', [
                    'key' => $request->options['key'],
                    'automation' => $automation,
                    'trigger' => $automation->getTrigger(),
                    'rules' => $this->triggerRules()[$request->options['key']],
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.trigger.updated'),
                'title' => trans('messages.automation.trigger.title', [
                    'title' => trans('messages.automation.trigger.tree.' . $request->options["key"])
                ]),
                'options' => $this->prepareOptions($request->options, $request->user()->customer),
            ]);
        }

        return view('automation2.triggerEdit', [
            'key' => $request->key,
            'automation' => $automation,
            'trigger' => $automation->getTrigger(),
            'rules' => $rules,
        ]);
    }

    public function waitEdit(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // saving
        if ($request->isMethod('post')) {
            $delayOptions = \Acelle\Model\Automation2::getDelayOptions();
            $parts = explode(' ', $request->time);
            $title = trans('messages.time.wait_for') . ' ' . $parts[0] . ' ' . trans_choice('messages.time.' . $parts[1], $parts[0]);

            foreach ($delayOptions as $deplayOption) {
                if ($deplayOption['value'] == $request->time) {
                    $title = trans('messages.automation.wait.delay.' . $request->time);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.action.updated'),
                'title' => $title,
                'options' => [
                    'key' => $request->key,
                    'time' => $request->time,
                ],
            ]);
        }

        return view('automation2.waitEdit', [
            'key' => $request->key,
            'automation' => $automation,
            'element' => $automation->getElement($request->id),
        ]);
    }

    public function conditionEdit(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // saving
        if ($request->isMethod('post')) {
            if ($request->type == 'open') {
                return response()->json([
                    'status' => 'success',
                    'message' => trans('messages.automation.action.updated'),
                    'title' => trans('messages.automation.action.condition.read_email.title'),
                    'options' => [
                        'key' => $request->key,
                        'type' => $request->type,
                        'email' => empty($request->email) ? null : $request->email,
                        'wait' => $request->wait,
                    ],
                ]);
            } elseif ($request->type == 'click') {
                return response()->json([
                    'status' => 'success',
                    'message' => trans('messages.automation.action.updated'),
                    'title' => trans('messages.automation.action.condition.click_link.title'),
                    'options' => [
                        'key' => $request->key,
                        'type' => $request->type,
                        'email_link' => empty($request->email_link) ? null : $request->email_link,
                        'wait' => $request->wait,
                    ],
                ]);
            } elseif ($request->type == 'cart_buy_anything') {
                return response()->json([
                    'status' => 'success',
                    'message' => trans('messages.automation.action.updated'),
                    'title' => trans('messages.automation.action.condition.cart_buy_anything.title'),
                    'options' => [
                        'key' => $request->key,
                        'type' => $request->type,
                        'wait' => $request->wait,
                    ],
                ]);
            } elseif ($request->type == 'cart_buy_item') {
                return response()->json([
                    'status' => 'success',
                    'message' => trans('messages.automation.action.updated'),
                    'title' => trans('messages.automation.action.condition.cart_buy_item.title', [
                        'item' => $request->item_title,
                    ]),
                    'options' => [
                        'key' => $request->key,
                        'type' => $request->type,
                        'item_id' => $request->item_id,
                        'item_title' => $request->item_title,
                    ],
                ]);
            } else {
                throw new \Exception("Condition type {$request->type} not found!");
            }
        }

        return view('automation2.conditionEdit', [
            'key' => $request->key,
            'automation' => $automation,
            'element' => $automation->getElement($request->id),
        ]);
    }

    /**
     * Email setup.
     *
     * @return \Illuminate\Http\Response
     */
    public function emailSetup(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $trackingDomain = Setting::isYes('campaign.tracking_domain');

        if ($request->email_uid) {
            $email = Email::findByUid($request->email_uid);
        } else {
            $email = Email::newDefault();
            $email->action_id = $request->action_id;
            $email->automation2_id = $automation->id;
            $email->customer_id = $automation->customer_id;
            // $email->save();
        }

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // saving
        if ($request->isMethod('post')) {
            // fill before save
            $email->fillAttributes($request->all());

            // Tacking domain
            if (isset($params['custom_tracking_domain']) && $params['custom_tracking_domain'] && isset($params['tracking_domain_uid'])) {
                $tracking_domain = \Acelle\Model\TrackingDomain::findByUid($params['tracking_domain_uid']);
                if ($tracking_domain) {
                    $this->tracking_domain_id = $tracking_domain->id;
                } else {
                    $this->tracking_domain_id = null;
                }
            } else {
                $this->tracking_domain_id = null;
            }

            // make validator
            $validator = Validator::make($request->all(), $email->rules($request));

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.email.setup', [
                    'automation' => $automation,
                    'email' => $email,
                    'trackingDomain' => $trackingDomain,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // pass validation and save
            $email->save();

            return response()->json([
                'status' => 'success',
                'title' => trans('messages.automation.send_a_email', ['title' => $email->subject]),
                'message' => trans('messages.automation.email.set_up.success'),
                'url' => action('Automation2Controller@emailTemplate', [
                    'uid' => $automation->uid,
                    'email_uid' => $email->uid,
                ]),
                'options' => [
                    'email_uid' => $email->uid,
                ],
            ], 201);
        }

        return view('automation2.email.setup', [
            'automation' => $automation,
            'email' => $email,
            'trackingDomain' => $trackingDomain,
        ]);
    }

    /**
     * Delete automation email.
     *
     * @return \Illuminate\Http\Response
     */
    public function emailDelete(Request $request, $uid, $email_uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // delete email
        $email->deleteAndCleanup();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.email.deteled'),
        ], 201);
    }

    /**
     * Email template.
     *
     * @return \Illuminate\Http\Response
     */
    public function emailTemplate(Request $request, $uid, $email_uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        if (!$email->hasTemplate()) {
            return redirect()->action('Automation2Controller@templateCreate', [
                'uid' => $automation->uid,
                'email_uid' => $email->uid,
            ]);
        }

        return view('automation2.email.template', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Email show.
     *
     * @return \Illuminate\Http\Response
     */
    public function email(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.index', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Email confirm.
     *
     * @return \Illuminate\Http\Response
     */
    public function emailConfirm(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // saving
        if ($request->isMethod('post')) {
        }

        return view('automation2.email.confirm', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Create template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateCreate(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.template.create', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Create template from layout.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateLayout(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            $template = Template::findByUid($request->template_uid);
            $email->setTemplate($template);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.email.template.theme.selected'),
            ], 201);
        }

        // default tab
        if ($request->from != 'mine' && !$request->category_uid) {
            $request->category_uid = TemplateCategory::first()->uid;
        }

        return view('automation2.email.template.layout', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function templateLayoutList(Request $request)
    {
        $automation = Automation2::findByUid($request->uid);
        $email = Email::findByUid($request->email_uid);

        // from
        if ($request->from == 'mine') {
            $templates = $request->user()->customer->templates()->email(); // master ok
        } elseif ($request->from == 'gallery') {
            $templates = Template::shared()->notPreserved()->email();
        } else {
            $templates = Template::shared()->notPreserved()->email()
                ->orWhere('customer_id', '=', $request->user()->customer->id);
        }

        $templates = $templates->notPreserved()->search($request->keyword);

        // category id
        if ($request->category_uid) {
            $templates = $templates->categoryUid($request->category_uid);
        }

        $templates = $templates->orderBy($request->sort_order, $request->sort_direction)
            ->paginate($request->per_page);

        return view('automation2.email.template.layoutList', [
            'automation' => $automation,
            'email' => $email,
            'templates' => $templates,
        ]);
    }

    /**
     * Select builder for editing template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateBuilderSelect(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.template.templateBuilderSelect', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Edit campaign template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateEdit(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // save campaign html
        if ($request->isMethod('post')) {
            $rules = array(
                'content' => 'required',
            );

            $this->validate($request, $rules);

            if (get_tmp_quota($request->user()->customer, 'unsubscribe_url_required') == 'yes' && Setting::isYes('campaign.enforce_unsubscribe_url_check')) {
                if (strpos($request->content, '{UNSUBSCRIBE_URL}') === false) {
                    return response()->json(['message' => trans('messages.template.validation.unsubscribe_url_required')], 400);
                }
            }

            $email->setTemplateContent($request->content, function ($email) {
                $email->updateLinks();
            });

            return response()->json([
                'status' => 'success',
            ]);
        }

        return view('automation2.email.template.edit', [
            'automation' => $automation,
            'list' => $automation->mailList,
            'email' => $email,
            'templates' => $request->user()->customer->getBuilderTemplates(),
            'customer' => $automation->customer, // master DB OK
        ]);
    }

    /**
     * Email html content.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateContent(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.template.content', [
            'content' => $email->getTemplateContent(),
        ]);
    }

    /**
     * Upload template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateUpload(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // validate and save posted data
        if ($request->isMethod('post')) {
            $email->uploadTemplate($request);

            // return
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.email.template.uploaded')
            ]);

            // throw a validation error otherwise
        }

        return view('automation2.email.template.upload', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Remove exist template.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateRemove(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $email->removeTemplate();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.email.template.removed'),
        ], 201);
    }

    /**
     * Template preview.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templatePreview(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.template.preview', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Template preview.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templatePreviewContent(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        echo $email->getHtmlContent();
    }

    /**
     * Attachment upload.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function emailAttachmentUpload(Request $request, $uid, $email_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        foreach ($request->file as $file) {
            $email->uploadAttachment($file);
        }
    }

    /**
     * Attachment remove.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function emailAttachmentRemove(Request $request, $uid, $email_uid, $attachment_uid)
    {
        $automation = Automation2::findByUid($uid);
        $attachment = Attachment::findByUid($request->attachment_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $attachment->remove();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.email.attachment.removed'),
        ], 201);
    }

    /**
     * Attachment download.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function emailAttachmentDownload(Request $request, $uid, $email_uid, $attachment_uid)
    {
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($request->email_uid);
        $attachment = Attachment::findByUid($request->attachment_uid);

        // authorize
        if (\Gate::denies('read', $automation)) {
            return $this->notAuthorized();
        }

        return response()->download(storage_path('app/' . $attachment->file), $attachment->name);
    }

    /**
     * Enable automation.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function enable(Request $request)
    {
        // Japan + not license
        if (config('custom.japan') && !\Acelle\Helpers\LicenseHelper::hasActiveLicense()) {
            return response()->json([
                'status' => 'error',
                'message' => trans('messages.license.required'),
            ], 400);
        }

        $automations = Automation2::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($automations->get() as $automation) {
            // authorize
            if (\Gate::allows('enable', $automation)) {
                $automation->enable();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => trans_choice('messages.automation.enabled', $automations->count()),
        ]);
    }

    /**
     * Disable event.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function disable(Request $request)
    {
        $automations = Automation2::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($automations->get() as $automation) {
            // authorize
            if (\Gate::allows('disable', $automation)) {
                $automation->disable();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => trans_choice('messages.automation.disabled', $automations->count()),
        ]);
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
            return response()->json([
                'status' => 'notice',
                'message' => trans('messages.operation_not_allowed_in_demo'),
            ], 403);
        }

        $automations = Automation2::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($automations->get() as $automation) {
            // authorize
            if (\Gate::allows('delete', $automation)) {
                $automation->deleteAndCleanup();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => trans_choice('messages.automation.deleted', $automations->count()),
        ]);
    }

    /**
     * Automation insight page.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function insight(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('view', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.insight', [
            'automation' => $automation,
            'stats' => $automation->readCache('SummaryStats'),
            'insight' => $automation->getInsight(),
        ]);
    }

    /**
     * Automation contacts list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function contacts(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('view', $automation)) {
            return $this->notAuthorized();
        }

        $subscribers = $automation->subscribers();
        $count = $subscribers->count();

        return view('automation2.contacts.index', [
            'automation' => $automation,
            'count' => $count,
            'stats' => $automation->getSummaryStats(),
            'subscribers' => $subscribers,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function contactsList(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('view', $automation)) {
            return $this->notAuthorized();
        }

        $sortBy = $request->sortBy ?: 'subscribers.id';
        $sortOrder = $request->sortOrder ?: 'DESC';

        // list by type
        $subscribers = $automation->getSubscribersWithTriggerInfo()
                                  ->simpleSearch($request->keyword)
                                  ->orderBy($sortBy, $sortOrder);

        // Make sure $count returns the same list of $subscribers
        $count = $automation->subscribers()->simpleSearch($request->keyword)->count();

        // setPath() is required, otherwise, it may produce http link even in https
        $contacts = $subscribers->fastPaginate($request->per_page, $count);
        $contacts->setPath(action('Automation2Controller@contactsList', [ 'uid' => $automation->uid ]));

        return view('automation2.contacts.list', [
            'automation' => $automation,
            'contacts' => $contacts,
            'customer' => $automation->customer, // Master DB OK
        ]);
    }

    /**
     * Automation timeline.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function timeline(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('view', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.timeline.index', [
            'automation' => $automation,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function timelineList(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('view', $automation)) {
            return $this->notAuthorized();
        }

        $timelines = $automation->timelines()->paginate($request->per_page);

        return view('automation2.timeline.list', [
            'automation' => $automation,
            'timelines' => $timelines,
        ]);
    }

    /**
     * Automation contact profile.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function profile(Request $request, $uid, $contact_id)
    {
        $automation = Automation2::findByUid($uid);
        $contact = Subscriber::find($contact_id);

        // authorize
        if (\Gate::denies('view', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.profile', [
            'automation' => $automation,
            'contact' => $contact,
        ]);
    }

    /**
     * Automation remove contact.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function removeContact(Request $request, $uid, $contact_id)
    {
        $automation = Automation2::findByUid($uid);
        $contact = Subscriber::find($contact_id);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.contact.deleted'),
        ], 201);
    }

    /**
     * Automation tag contact.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function tagContact(Request $request, $uid, $contact_id)
    {
        $automation = Automation2::findByUid($uid);
        $contact = Subscriber::find($contact_id);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // saving
        if ($request->isMethod('post')) {
            $contact->updateTags($request->tags);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.contact.tagged', [
                    'contact' => $contact->getFullName(),
                ]),
            ], 201);
        }

        return view('automation2.contacts.tagContact', [
            'automation' => $automation,
            'contact' => $contact,
        ]);
    }

    /**
     * Automation tag contacts.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function tagContacts(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);
        $subscribers = $automation->subscribers();

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // saving
        if ($request->isMethod('post')) {
            // make validator
            $validator = Validator::make($request->all(), [
                'tags' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.contacts.tagContacts', [
                    'automation' => $automation,
                    'subscribers' => $subscribers,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Copy to list
            foreach ($subscribers->get() as $subscriber) {
                $subscriber->addTags($request->tags);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.contacts.tagged', [
                    'count' => $subscribers->count(),
                ]),
            ], 201);
        }

        return view('automation2.contacts.tagContacts', [
            'automation' => $automation,
            'subscribers' => $subscribers,
        ]);
    }

    /**
     * Automation remove contact tag.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function removeTag(Request $request, $uid, $contact_id)
    {
        $automation = Automation2::findByUid($uid);
        $contact = Subscriber::find($contact_id);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $contact->removeTag($request->tag);

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.contact.tag.removed', [
                'tag' => $request->tag,
            ]),
        ], 201);
    }

    /**
     * Automation export contacts.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function exportContacts(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $subscribers = $automation->subscribers();

        // saving
        if ($request->isMethod('post')) {
            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.contacts.exported'),
            ], 201);
        }
    }

    /**
     * Automation copy contacts to new list.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function copyToNewList(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $subscribers = $automation->subscribers();

        // saving
        if ($request->isMethod('post')) {
            // make validator
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.contacts.copyToNewList', [
                    'automation' => $automation,
                    'subscribers' => $subscribers,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Crate new list
            $list = $automation->mailList->copy($request->name);

            // Copy to list
            foreach ($subscribers->get() as $subscriber) {
                $subscriber->copy($list);
            }

            // update cache
            $list->updateCache();

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.contacts.copied_to_new_list', [
                    'count' => $subscribers->count(),
                    'list' => $list->name,
                ]),
            ], 201);
        }

        return view('automation2.contacts.copyToNewList', [
            'automation' => $automation,
            'subscribers' => $subscribers,
        ]);
    }

    /**
     * Automation template classic builder.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateEditClassic(Request $request, $uid, $email_uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // saving
        if ($request->isMethod('post')) {
            $rules = array(
                'content' => 'required',
            );

            $this->validate($request, $rules);

            if (get_tmp_quota($request->user()->customer, 'unsubscribe_url_required') == 'yes' && Setting::isYes('campaign.enforce_unsubscribe_url_check')) {
                if (strpos($request->content, '{UNSUBSCRIBE_URL}') === false) {
                    return response()->json(['message' => trans('messages.template.validation.unsubscribe_url_required')], 400);
                }
            }

            $email->setTemplateContent($request->content, function ($email) {
                $email->updateLinks();
            });

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.email.content.updated'),
            ], 201);
        }

        return view('automation2.email.template.editClassic', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Automation template classic builder.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function templateEditPlain(Request $request, $uid, $email_uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // saving
        if ($request->isMethod('post')) {
            $rules = array(
                'plain' => 'required',
            );

            // make validator
            $validator = Validator::make($request->all(), $rules);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.email.template.editPlain', [
                    'automation' => $automation,
                    'email' => $email,
                    'errors' => $validator->errors(),
                ], 400);
            }

            $email->plain = $request->plain;
            $email->save();

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.email.plain.updated'),
            ], 201);
        }

        return view('automation2.email.template.editPlain', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    /**
     * Segment select.
     *
     * @return \Illuminate\Http\Response
     */
    public function segmentSelect(Request $request)
    {
        if (!$request->list_uid) {
            return '';
        }

        // init automation
        if ($request->uid) {
            $automation = Automation2::findByUid($request->uid);

            // authorize
            if (\Gate::denies('view', $automation)) {
                return $this->notAuthorized();
            }
        } else {
            $automation = new Automation2();

            // authorize
            if (\Gate::denies('create', $automation)) {
                return $this->notAuthorized();
            }
        }

        $list = MailList::findByUid($request->list_uid);

        return view('automation2.segmentSelect', [
            'automation' => $automation,
            'list' => $list,
        ]);
    }

    /**
     * Display a listing of subscribers.
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribers(Request $request, $uid)
    {
        // init
        $automation = Automation2::findByUid($uid);
        $list = $automation->mailList;

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.subscribers.index', [
            'automation' => $automation,
            'list' => $list,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribersList(Request $request, $uid)
    {
        // init
        $automation = Automation2::findByUid($uid);
        $list = $automation->mailList;

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $subscribers = $automation->subscribers()->search($request)
            ->where('mail_list_id', '=', $list->id);

        // $total = distinctCount($subscribers);
        $total = $subscribers->count();
        $subscribers->with(['mailList']);
        $subscribers = $subscribers->paginate($request->per_page);

        $fields = $list->getFields->whereIn('uid', explode(',', $request->columns));

        return view('automation2.subscribers._list', [
            'automation' => $automation,
            'subscribers' => $subscribers,
            'total' => $total,
            'list' => $list,
            'fields' => $fields,
        ]);
    }

    /**
     * Remove subscriber from automation.
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribersRemove(Request $request, $uid, $subscriber_id)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $subscriber = Subscriber::find($subscriber_id);

        // authorize
        if (\Gate::denies('update', $subscriber)) {
            return;
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.subscriber.removed'),
        ], 201);
    }

    /**
     * Restart subscriber for automation.
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribersRestart(Request $request, $uid, $subscriber_id)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $subscriber = Subscriber::find($subscriber_id);

        // authorize
        if (\Gate::denies('update', $subscriber)) {
            return;
        }

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.automation.subscriber.restarted'),
        ], 201);
    }

    /**
     * Display a listing of subscribers.
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribersShow(Request $request, $uid, $subscriber_id)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $subscriber = Subscriber::find($subscriber_id);

        // authorize
        if (\Gate::denies('read', $subscriber)) {
            return;
        }

        return view('automation2.subscribers.show', [
            'automation' => $automation,
            'subscriber' => $subscriber,
        ]);
    }

    /**
     * Get last saved time.
     *
     * @return \Illuminate\Http\Response
     */
    public function lastSaved(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('view', $automation)) {
            return;
        }

        return trans('messages.automation.designer.last_saved', ['time' => $automation->updated_at->diffForHumans()]);
    }

    public function debug(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);
        $type = $automation->getTriggerAction()->getOption('key');

        switch ($type) {
            case Automation2::TRIGGER_TYPE_WELCOME_NEW_SUBSCRIBER:

                $subscribers = $automation->getSubscribersWithTriggerInfo();

                if ($request->input('orderBy')) {
                    $subscribers = $subscribers->orderBy($request->input('orderBy'), $request->input('orderDir'));
                }

                $subscribers = $subscribers->simplePaginate(50);

                return view('automation2.debug_list_subscription', [
                    'automation' => $automation,
                    'subscribers' => $subscribers,
                ]);

                break;

            case Automation2::TRIGGER_TYPE_SAY_GOODBYE_TO_SUBSCRIBER:

                $subscribers = $automation->getSubscribersWithTriggerInfo()->where('subscribers.status', '=', 'unsubscribed');

                if ($request->input('orderBy')) {
                    $subscribers = $subscribers->orderBy($request->input('orderBy'), $request->input('orderDir'));
                }

                $subscribers = $subscribers->simplePaginate(50);

                return view('automation2.debug_list_unsubscription', [
                    'automation' => $automation,
                    'subscribers' => $subscribers,
                ]);

                break;

            case Automation2::TRIGGER_TYPE_SAY_HAPPY_BIRTHDAY:
                $subscribers = $automation->getSubscribersWithDateOfBirth();

                if ($request->input('email')) {
                    $subscribers = $subscribers->searchByEmail($request->input('email'));
                }

                if ($request->input('orderBy')) {
                    $subscribers = $subscribers->orderBy($request->input('orderBy'), $request->input('orderDir'));
                }

                $subscribers = $subscribers->simplePaginate(50);

                return view('automation2.debug', [
                    'automation' => $automation,
                    'subscribers' => $subscribers,
                ]);

                break;

            default:
                # code...
                break;
        }
    }

    public function debugTrigger(Request $request, $uid)
    {
        $trigger = AutoTrigger::findByUid($uid);

        return view('automation2.debug', [
            'trigger' => $trigger,
        ]);
    }

    public function triggerNow(Request $request)
    {
        $automation = Automation2::findByUid($request->automation);
        $subscriber = Subscriber::find($request->subscriber);

        $existingTrigger = $automation->getAutoTriggerFor($subscriber);

        if (!is_null($existingTrigger)) {
            echo sprintf("%s already triggered. Click <a href='%s'>here</a> for more details", $subscriber->email, action('AutoTrigger@show', [ 'id' => $existingTrigger->id ]));
            return;
        }

        // Manually trigger, force!
        $automation->logger()->info(sprintf('Manually trigger contact %s', $subscriber->email));

        // Force trigger a contact
        // Even inactive contacts - in case of Say-Goodbye-Trigger for example
        $trigger = $automation->initTrigger($subscriber, $force = true);
        $trigger->check($manually = true);

        return redirect()->action('AutoTrigger@show', [ 'id' => $trigger->id ]);
    }

    /**
     * Get last saved time.
     *
     * @return \Illuminate\Http\Response
     */
    public function contactRetry(Request $request, $uid, $contact_id)
    {
        $automation = Automation2::findByUid($uid);
        $contact = Subscriber::find($contact_id);
        // authorize
        if (\Gate::denies('view', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.contacts.list.error_row', [
            'automation' => $automation,
            'contact' => $contact,
        ]);
    }

    /**
     * Get wait time.
     *
     * @return \Illuminate\Http\Response
     */
    public function waitTime(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // saving
        if ($request->isMethod('post')) {
            return response()->json([
                'status' => 'success',
                'amount' => $request->amount,
                'unit' => $request->unit
            ]);
        }

        return view('automation2.waitTime', [
            'automation' => $automation,
        ]);
    }

    /**
     * Change cart automation2 list for auto adding byuer.
     *
     * @return \Illuminate\Http\Response
     */
    public function cartWait(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // saving
        if ($request->isMethod('post')) {
            // make validator
            $validator = Validator::make($request->all(), [
                'amount' => 'required',
                'unit' => 'required',
            ]);
            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.cartWait', [
                    'automation' => $automation,
                    'trigger' => $automation->getTrigger(),
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.cart.wait_updated'),
                'options' => [
                    'wait' => $request->amount . "_" . $request->unit,
                ]
            ]);
        }

        return view('automation2.cartWait', [
            'automation' => $automation,
            'trigger' => $automation->getTrigger(),
        ]);
    }

    /**
     * Change cart automation2 list for auto adding byuer.
     *
     * @return \Illuminate\Http\Response
     */
    public function cartChangeList(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // saving
        if ($request->isMethod('post')) {
            // make validator
            $validator = Validator::make($request->all(), [
                'options.list_uid' => 'required',
            ]);
            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.cartChangeList', [
                    'automation' => $automation,
                    'trigger' => $automation->getTrigger(),
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.cart.list_updated'),
                'options' => $request->options
            ]);
        }

        return view('automation2.cartChangeList', [
            'automation' => $automation,
            'trigger' => $automation->getTrigger(),
        ]);
    }

    /**
     * Change cart automation2 list for auto adding byuer.
     *
     * @return \Illuminate\Http\Response
     */
    public function cartChangeStore(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // saving
        if ($request->isMethod('post')) {
            // make validator
            $validator = Validator::make($request->all(), [
                'options.source_uid' => 'required',
            ]);
            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.cartChangeSore', [
                    'automation' => $automation,
                    'trigger' => $automation->getTrigger(),
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.cart.store_updated'),
                'options' => $request->options
            ]);
        }

        return view('automation2.cartChangeSore', [
            'automation' => $automation,
            'trigger' => $automation->getTrigger(),
        ]);
    }

    /**
     * Cart stats.
     *
     * @return \Illuminate\Http\Response
     */
    public function cartStats(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        return view('automation2.cart.stats', [
            'automation' => $automation,
        ]);
    }

    /**
     * Cart list.
     *
     * @return \Illuminate\Http\Response
     */
    public function cartList(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        return view('automation2.cart.list', [
            'automation' => $automation,
        ]);
    }

    /**
     * Cart list.
     *
     * @return \Illuminate\Http\Response
     */
    public function cartItems(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        return view('automation2.cart.items', [
            'automation' => $automation,
        ]);
    }

    /**
     * Operation select popup.
     *
     * @return \Illuminate\Http\Response
     */
    public function operationSelect(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // saving
        if ($request->isMethod('post')) {
            // return response()->json([
            //     'status' => 'success',
            //     'amount' => $request->amount,
            //     'unit' => $request->unit
            // ]);
        }

        return view('automation2.operationSelect', [
            'automation' => $automation,
            'types' => [
                'update_contact',
                'tag_contact',
                'copy_contact',
                \Acelle\Library\Automation\Operate::OPERATION_REMOVE_TAG,
            ],
        ]);
    }

    /**
     * Operation edit popup.
     *
     * @return \Illuminate\Http\Response
     */
    public function operationCreate(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // saving
        if ($request->isMethod('post')) {
            return response()->json([
                'status' => 'success',
                'title' => trans('messages.automation.operation.' . $request->options['operation_type']),
                'options' => $request->options,
                'message' => trans('messages.automation.operation.added'),
            ]);
        }

        return view('automation2.operationCreate', [
            'automation' => $automation,
            'types' => [
                'update_contact',
                'tag_contact',
                'copy_contact',
            ],
        ]);
    }

    /**
     * Operation edit popup.
     *
     * @return \Illuminate\Http\Response
     */
    public function operationShow(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        return view('automation2.operationShow', [
            'automation' => $automation,
            'element' => $automation->getElement($request->id),
        ]);
    }

    /**
     * Operation edit popup.
     *
     * @return \Illuminate\Http\Response
     */
    public function operationEdit(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // saving
        if ($request->isMethod('post')) {
            return response()->json([
                'status' => 'success',
                'title' => trans('messages.automation.operation.' . $request->options['operation_type']),
                'options' => $request->options,
                'message' => trans('messages.automation.operation.updated'),
            ]);
        }

        return view('automation2.operationEdit', [
            'automation' => $automation,
            'element' => $automation->getElement($request->id),
        ]);
    }

    public function run(Request $request)
    {
        $automation = Automation2::findByUid($request->automation);
        $automation->check();
        echo "Done";
    }

    public function sendTestEmail(Request $request)
    {
        $email = Email::findByUid($request->email_uid);

        if ($request->isMethod('post')) {
            try {
                // Send
                $email->sendTestEmail($request->input('email'));

                // OK
                return response()->json([
                    'status' => 'success',
                    'message' => 'OK',
                ]);
            } catch (Exception $ex) {
                return response()->view('automation2.sendTestEmail', [
                    'email' => $email,
                    'error' => $ex->getMessage(),
                ], 400);
            }
        }

        return view('automation2.sendTestEmail', [
            'email' => $email,
        ]);
    }

    public function conditionWaitCustom(Request $request)
    {
        if ($request->isMethod('post')) {
            // make validator
            $validator = \Validator::make($request->all(), [
                'wait_amount' => 'required',
                'wait_unit' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.condition.conditionWaitCustom', [
                    'errors' => $validator->errors(),
                ], 400);
            }

            return view('automation2.condition._wait_select');
        }

        return view('automation2.condition.conditionWaitCustom');
    }

    public function webhooks(Request $request)
    {
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('view', $email->automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.webhooks', [
            'email' => $email,
        ]);
    }

    public function webhooksList(Request $request)
    {
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('view', $email->automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.webhooksList', [
            'email' => $email,
        ]);
    }

    public function webhooksAdd(Request $request)
    {
        $email = Email::findByUid($request->email_uid);
        $webhook = $email->newWebhook();

        // authorize
        if (\Gate::denies('update', $email->automation)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            list($webhook, $validator) = $webhook->createFromArray($request->all());

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.email.webhooksAdd', [
                    'email' => $email,
                    'webhook' => $webhook,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'message' => trans('messages.webhook.added'),
            ]);
        }

        return view('automation2.email.webhooksAdd', [
            'email' => $email,
            'webhook' => $webhook,
        ]);
    }

    public function webhooksLinkSelect(Request $request)
    {
        $email = Email::findByUid($request->email_uid);

        // authorize
        if (\Gate::denies('view', $email->automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.webhooksLinkSelect', [
            'email' => $email,
        ]);
    }

    public function webhooksEdit(Request $request)
    {
        $webhook = \Acelle\Model\EmailWebhook::findByUid($request->webhook_uid);

        // authorize
        if (\Gate::denies('update', $webhook->email->automation)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            list($webhook, $validator) = $webhook->updateFromArray($request->all());

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.email.webhooksEdit', [
                    'webhook' => $webhook,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'message' => trans('messages.webhook.updated'),
            ]);
        }

        return view('automation2.email.webhooksEdit', [
            'webhook' => $webhook,
        ]);
    }

    public function webhooksDelete(Request $request)
    {
        $webhook = \Acelle\Model\EmailWebhook::findByUid($request->webhook_uid);

        // authorize
        if (\Gate::denies('update', $webhook->email->automation)) {
            return $this->notAuthorized();
        }

        $webhook->delete();

        return response()->json([
            'message' => trans('messages.webhook.deleted'),
        ]);
    }

    public function webhooksSampleRequest(Request $request)
    {
        $webhook = \Acelle\Model\EmailWebhook::findByUid($request->webhook_uid);

        // authorize
        if (\Gate::denies('view', $webhook->email->automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.webhooksSampleRequest', [
            'webhook' => $webhook,
        ]);
    }

    public function webhooksTest(Request $request)
    {
        $webhook = \Acelle\Model\EmailWebhook::findByUid($request->webhook_uid);
        $result = null;

        // authorize
        if (\Gate::denies('view', $webhook->email->automation)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            $client = new \GuzzleHttp\Client();

            try {
                $response = $client->request('GET', $webhook->endpoint, [
                    'headers' => [
                        "content-type" => "application/json"
                    ],
                    'body' => '{hello: "world"}',
                    'http_errors' => false,
                ]);

                $result = [
                    'status' => 'sent',
                    'code' => $response->getStatusCode(),
                    'message' => $response->getReasonPhrase(),
                ];
            } catch (\Exception $e) {
                $result = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return view('automation2.email.webhooksTest', [
            'webhook' => $webhook,
            'result' => $result,
        ]);
    }

    public function emailPreheader(Request $request, $uid, $email_uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.email.preheader', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    public function emailPreheaderAdd(Request $request, $uid, $email_uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        // Save posted data
        if ($request->isMethod('post')) {
            $validator = \Validator::make($request->all(), [
                'preheader' => 'required',
            ]);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.email.preheaderAdd', [
                    'automation' => $automation,
                    'email' => $email,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // update preheader
            $email->setPreheader($request->preheader);

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.preheader.updated'),
            ]);
        }

        return view('automation2.email.preheaderAdd', [
            'automation' => $automation,
            'email' => $email,
        ]);
    }

    public function emailPreheaderRemove(Request $request, $uid, $email_uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);
        $email = Email::findByUid($email_uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        $email->removePreheader();

        return response()->json([
            'status' => 'success',
            'message' => trans('messages.preheader.removed'),
        ]);
    }

    public function conditionRemove(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        return view('automation2.condition.remove', [
            'automation' => $automation,
        ]);
    }

    public function copy(Request $request, $uid)
    {
        // init automation
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('create', $automation)) {
            return $this->notAuthorized();
        }

        if ($request->isMethod('post')) {
            [$validator, $newAutomation] = $automation->copy($request->name, $request->mail_list_uid);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('automation2.copy', [
                    'automation' => $automation,
                    'newAutomation' => $newAutomation,
                    'errors' => $validator->errors(),
                ], 400);
            }

            return response()->json([
                'status' => 'success',
                'message' => trans('messages.automation.copied', [
                    'name' => $newAutomation->name,
                ]),
            ]);
        }

        return view('automation2.copy', [
            'automation' => $automation,
        ]);
    }

    public function triggerAll($uid)
    {
        // Instantiate automation and dispatch the job
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        try {
            ForceTriggerAutomation::dispatch($automation);

            return response()->json([
                'message' => trans('messages.automation.trigger_all.success'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function emailOverview(Request $request, $email_uid)
    {
        $email = Email::findByUid($email_uid);

        return view('automation2.email.overview.main', [
            'email' => $email,
        ]);
    }

    public function retryAllFailedActions($uid)
    {
        // Instantiate automation and dispatch the job
        $automation = Automation2::findByUid($uid);

        // authorize
        if (\Gate::denies('update', $automation)) {
            return $this->notAuthorized();
        }

        try {
            $automation->retryAllFailedActions();

            return response()->json([
                'message' => trans('messages.automation.action.retry.success'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function emailSaveSignature(Request $request, $email_uid)
    {
        $email = Email::findByUid($email_uid);
        $signature = \Acelle\Model\Signature::findByUid($request->signature_uid);

        $email->setSignature($signature);
    }

    public function outgoingWebhookSetup(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);
        $options = array(
            'send_method' => 'post',
            'authorization_method' => 'basic_auth',
            'authorization' => array(
                'bearer_token' => null,
                'username' => null,
                'password' => null,
                'custom_key' => null,
                'custom_value' => null,
            ), 'endpoint_url' => null,
            'header' => 'with_headers',
            'headers' => array(),
            'body_type' => 'key_value_pair',
            'body_parameters' => array(),
            'plain_text' => null,
        );

        //
        if ($request->id) {
            $options = array_merge($options, json_decode(json_encode($automation->getElement($request->id)->get('options')), true));
        }

        return view('automation2.outgoing_webhook.setup', [
            'automation' => $automation,
            'element' => $request->id ? $automation->getElement($request->id) : null,
            'options' => $options,
        ]);
    }

    public function outgoingWebhookTestPopup(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        return view('automation2.outgoing_webhook.testPopup', [
            'automation' => $automation,
        ]);
    }

    public function outgoingWebhookTest(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);
        $options = $request->webhook;
        $result = $this->outgoingWebhookTestRun($options);

        return view('automation2.outgoing_webhook.testResult', [
            'automation' => $automation,
            'options' => $options,
            'result' => $result,
        ]);
    }

    public function outgoingWebhookSave(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        //
        $data = array_merge([
            'title' => trans('messages.automation.outgoing_webhook'),
            'options' => $request->webhook,
        ]);

        return response()->json($data);
    }

    public function outgoingWebhookShow(Request $request, $uid)
    {
        $automation = Automation2::findByUid($uid);

        // var_export(json_decode(json_encode($automation->getElement($request->id)->get('options')), true));

        return view('automation2.outgoing_webhook.show', [
            'automation' => $automation,
            'element' => $automation->getElement($request->id),
        ]);
    }

    public function outgoingWebhookTestRun($options)
    {
        // Prepare cURL initialization
        $ch = curl_init();

        // Set the URL from the options
        curl_setopt($ch, CURLOPT_URL, $options['endpoint_url']);

        // Set the HTTP method (POST)
        $method = strtoupper($options['send_method']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        // Set the Authorization (Basic Auth)
        if ($options['authorization_method'] === 'basic_auth') {
            $username = $options['authorization']['username'];
            $password = $options['authorization']['password'];
            curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        }

        // Set Headers
        $headers = [];
        if ($options['header'] === 'with_headers' && !empty($options['headers'])) {
            foreach ($options['headers'] as $header) {
                $headers[] = $header['key'] . ': ' . $header['value'];
            }
        }

        // Add Content-Type based on body type
        if ($options['body_type'] === 'key_value_pair') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } elseif ($options['body_type'] === 'plain_text') {
            // No special Content-Type needed for plain text
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set the body type and parameters
        $body = '';
        if ($options['body_type'] === 'plain_text') {
            $body = $options['plain_text'];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } elseif ($options['body_type'] === 'key_value_pair') {
            $bodyParams = [];
            $options['body_parameters'] = $options['body_parameters'] ?? [];
            foreach ($options['body_parameters'] as $param) {
                $bodyParams[] = urlencode($param['key']) . '=' . urlencode($param['value']);
            }
            $body = implode('&', $bodyParams);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // Set additional cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        // Execute the cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);  // Get HTTP response code

        // Capture cURL errors
        $curlError = curl_errno($ch) ? curl_error($ch) : null;

        // Close the cURL session
        curl_close($ch);

        // Display all response aspects
        return [
            'methos' => $method,
            'body' => $body,
            'error' => $curlError,
            'http_code' => $httpCode,
            'response' => $response,
        ];
    }
}
