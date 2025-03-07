
<li class="nav-item {{ (request()->is('ecommerce*')) ? 'active' : '' }}">
	<a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseEcommerce" aria-expanded="false" aria-controls="collapseEcommerce">
		<i class="fab fa-product-hunt"></i>
		<span>@lang('Ecommerce')</span>
	</a>
	<div id="collapseEcommerce" class="collapse {{ (request()->is('ecommerce*')) ? 'show' : '' }}" aria-labelledby="collapseEcommerce">
		<div class="py-2 collapse-inner rounded">
			<a class="collapse-item {{ (request()->is('ecommerce/products*')) ? 'active' : '' }}" href="{{ route('products.index') }}">@lang('Products')</a>
			<a class="collapse-item {{ (request()->is('ecommerce/orders*')) ? 'active' : '' }}" href="{{ route('orders.index') }}">@lang('Orders')</a>
		</div>
	</div>
</li>