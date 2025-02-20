<h4>{{ trans('messages.sending_credit_plan.delete.confirm') }}</h4>
<ul class="modern-listing">
    @foreach ($plans->get() as $plan)
        <li class="d-flex align-items-center">
            <i class="material-symbols-rounded fs-4 me-3 text-danger">error_outline</i>
            <div>
                <h5 class="text-danger mb-1">{{ $plan->name }}</h5>
            </div>                      
        </li>
    @endforeach
</ul>