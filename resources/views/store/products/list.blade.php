<div class="product-container product-bdr">
    <table class="table border product-list-table"> 
        <thead class="product-header-bg">
            <tr>
                <th>
                    <div class="padding-cell">
                        <label>
                            <input list-control="all-checker" class="dt-checkboxes checkSingle styled" type="checkbox" value="">
                            <span class="check-symbol"></span>
                        </label>
                    </div>
                </th>
                <th colspan="2" style="text-align: center"> 
                    <span class="d-flex align-items-center padding-cell pl-0">
                        <span>{{ trans('store.product.name') }}</span>
                        <span class="column-controls ms-auto">
                            <span
                                list-action="sort"
                                sort-by="name"
                                sort-direction="{{ $sort_by == 'name' ? $sort_direction : 'asc' }}"
                                class="list_column_action ms-2 {{ $sort_by == 'name' ? 'active' : '' }}">
                                    <span class="material-symbols-rounded f-icon">sort</span>
                            </span>
                        </span>
                    </span>
                </th>
                <th>
                    <span class="d-flex align-items-center padding-cell pl-0">
                        <span>{{ trans('store.product.stock') }}</span>
                        <span class="column-controls ms-auto">
                            <span
                                list-action="sort"
                                sort-by="stock"
                                sort-direction="{{ $sort_by == 'stock' ? $sort_direction : 'asc' }}"
                                class="list_column_action ms-2 {{ $sort_by == 'stock' ? 'active' : '' }}">
                                <span class="material-symbols-rounded f-icon">sort</span>
                            </span>
                        </span>
                    </span>
                </th> 
                <th>
                    <span class="d-flex align-items-center padding-cell pl-0">
                        <span>{{ trans('store.product.price') }}</span>
                        <span class="column-controls ms-auto">
                            <span
                                list-action="sort"
                                sort-by="price"
                                sort-direction="{{ $sort_by == 'price' ? $sort_direction : 'asc' }}"
                                class="list_column_action ms-2 {{ $sort_by == 'price' ? 'active' : '' }}">
                                <span class="material-symbols-rounded f-icon">sort</span>
                            </span>
                        </span>
                    </span>
                </th>
                <th>
                    <span class="d-flex align-items-center padding-cell pl-0">
                        <span>{{ trans('store.product.status') }}</span>
                        <span class="column-controls ms-auto">
                            <span
                                list-action="sort"
                                sort-by="status"
                                sort-direction="{{ $sort_by == 'status' ? $sort_direction : 'asc' }}"
                                class="list_column_action ms-2 {{ $sort_by == 'status' ? 'active' : '' }}">
                                <span class="material-symbols-rounded f-icon">sort</span>
                            </span>
                        </span>
                    </span>
                </th>
                <th class="text-center" style="width: 20px;">
                    <div class="padding-cell">
                        {{ trans('messages.actions') }} <i class="ph-arrow-circle-dowsn"></i>
                    </div>
                </th>
            </tr>
        </thead>
        <tbody> 
            @foreach($products as $key => $product)  
            <tr> 
                <td style="text-align: center">
                    <div class="padding-cell">
                        <div class="formcheck"> 
                            <label>
                                <input list-control="row-checker" class="form-check-input dt-checkboxes checkSingle styled"  name="ids[]" type="checkbox" value="{{ $product->id }}" id="{{ $product->id }}">
                                <span class="check-symbol"></span>
                            </label>
                            <label class="form-check-label" for="{{ $product->id }}"></label>
                        </div>
                    </div>
                </td>
                <td width="1%">
                    <a  class="btn btn-icon py-0 px-1 me-1" 
                        href="{{ action('Store\ProductController@edit', [
                        'product' => $product,
                        'page' => request()->page,
                    ]) }}">
                        @if($product->getImageUrl())
                            <img class="proimage rounded-3" src="{{ $product->getImageUrl() }}" alt="" title="" style="width:50px">  
                        @else
                            <img class="proimage rounded-3" src="https://upload.wikimedia.org/wikipedia/commons/6/65/No-Image-Placeholder.svg" alt="" title="" width="50">  
                        @endif
                    </a>
                </td>
                <td>
                    <div class="xtooltip fw-bold pt-1" title="aaa">{{ $product->title }}</div>
                    <div class="text-secondary text-muted2 small">Sku ID: {{ $product->uid }}</div>
                </td>
                <td>
                    <div class="d-flex justify-content-start">
                        <div class="pt-1">{{ $product->stock }}</div>
                        <div class="pl-1">
                            <a  class="btn btn-icon py-0 px-1 me-1" 
                                    href="{{ action('Store\ProductController@edit', [
                                    'product' => $product,
                                    'page' => request()->page,
                                ]) }}">
                                <span class="material-symbols-rounded lada-edit-color">border_color</span>
                            </a> 
                        </div>
                    </div>
                </td> 
                <td>
                    <div class="d-flex justify-content-start">
                        <div class="pt-1">{{ $product->price }} <span class="text-secondary"> {{ $product->curency }} </span></div>
                        <div class="pl-1"><a  class="btn btn-icon py-0 px-1 me-1" 
                                href="{{ action('Store\ProductController@edit', [
                                'product' => $product,
                                'page' => request()->page,
                            ]) }}">
                            <span class="material-symbols-rounded lada-edit-color">border_color</span>
                        </a>
                    </div>
                </td>
                <td>
                    <div class="pt-1 me-1 form-switch" list-action="status-update">
                        <input class="form-check-input cbstatus" type="checkbox" role="switch"  
                        data-id="{{ $product->id }}" class="toggle-class"  
                        data-onstyle="success" data-offstyle="danger" data-toggle="toggle"
                        data-on="active" data-off="inactive" {{ $product->status =='active'? 'checked' : '' }}>
                    </div>
                </td>
                <td>
                    <div class="dropdown">
                        <a class="dropdown-toggle text-wrap lada-action-edit" data-bs-toggle="dropdown" aria-expanded="false"  style=" color: #1971ff;margin-right: 4px;">
                            Xem thêm chỉnh sửa
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#">Thêm hàng loạt</a></li>
                            <li><a class="dropdown-item" href="#">Chỉnh sủa hàng loạt</a></li>
                            <li><a class="dropdown-item" href="#">Quản lý hình ảnh</a></li>
                            <li>
                                <a  class="dropdown-item" 
                                    href="{{ action('Store\ProductController@edit', [
                                        'product' => $product,
                                        'page' => request()->page,
                                    ]) }}"> 
                                    Chỉnh sửa
                                </a>
                            </li>
                            <li>
                                <a  class="dropdown-item "
                                    list-action="delete-product" 
                                    href="{{ action('Store\ProductController@delete', [
                                        'id' =>   $product->id,
                                        'page'    => request()->page,
                                        'perPage' => $perPage
                                        ]) }}">
                                    Xóa sản phẩm
                                </a>
                            </li>
                        </ul>
                    </div>
                 
                </td>                     
            </tr>
            @endforeach
        </tbody>
    </table> 
</div>
<div class="d-flex justify-content-between mx-0 mb-3 mt-2 small">
    <div class="d-flex align-items-center ml-2 ">
        <div class="me-1">
            <div class="">
                <label class="mr-2">
                    @include('store.helpers.pagination.per_page', [
                        'perPage' => $perPage,
                    ])
                </label>
                records per page. 
                @if($products->total() > 0 )
                    Showing {{ $products->firstItem()  }} 
                    to {{ $products->lastItem() }} 
                    of {{ $products->total() }} 
                    entries
                @endif
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-md-6">
        <div class="dataTables_paginate paging_simple_numbers" id="DataTables_Table_0_paginate">
            {{ $products->appends(array( 
                'perPage' => $perPage
                ))->links('vendor.pagination.bootstrap-4') }} 
        </div>
    </div>
</div>


<script>







    $(function() {  
        productList.getDeleteCampaignsButtons().forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                let url = button.getAttribute('href');
                productList.deleteCampaign(url);
            });
        });        
    });

    var productList = {
        init: function() {
            // events
            this.events();
        },
        getDeleteCampaignsButtons() {
            return ProductIndex.productList.getContent().querySelectorAll('[list-action="delete-product"]');
        },
        deleteCampaign(url) {
            new Dialog('confirm', {
                message: "{{ trans('store.product.delete._confirm') }}",
                ok: function() {
                    ProductIndex.productList.addLoadingEffect();
                    // load list via ajax
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data:{
                            _method: 'delete' ,
                            _token: CSRF_TOKEN,  
                        },
                    }).done(function(response) {
                        notify({
                            type: response.status,
                            message: response.message,
                        });
                        // load list
                        ProductIndex.productList.load();
                    }).fail(function(jqXHR, textStatus, errorThrown){
                    }).always(function() {
                    });
                }
            })
        },
    }
</script>

