<li class="nav-item {{ (request()->is('leads*')) ? 'active' : '' }}">
	<a class="nav-link" href="{{ route('leads.index') }}">
	<i class="fas fa-user-friends"></i>
	<span>@lang('Leads')</span></a>
</li>