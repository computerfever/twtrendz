<?php

namespace Acelle\Jobs;

class SendConfirmationEmailJob extends Base
{
    protected $subscribers;
    protected $mailList;
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($subscribers, $mailList)
    {
        $this->subscribers = $subscribers;
        $this->mailList = $mailList;
        $this->customer = $this->mailList->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();

        foreach ($this->subscribers as $subscriber) {
            $this->mailList->sendSubscriptionConfirmationEmail($subscriber);
        }
    }
}
