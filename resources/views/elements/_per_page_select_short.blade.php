<div class="d-flex align-items-center" total-items-count="{{ number_with_delimiter($items->toArray()["total"]) }}">
    <div class="num_per_page mr-auto my-0">
        @if (isset($items))
            <label>
                {{ $items->toArray()["per_page"]*($items->toArray()["current_page"]-1)+1 }} -
                {{ ($items->toArray()["per_page"]*$items->toArray()["current_page"] > $items->toArray()["total"] ? $items->toArray()["total"] : $items->toArray()["per_page"]*$items->toArray()["current_page"]) }} /
                {{ $items->toArray()["total"] }}
            <input type="hidden" name="total_items_count" value="{{ $items->toArray()["total"] }}" />
        @endif
    </div>
    <div class="d-flex align-items-center">@include('helpers._pagination', ['paginator' => $items])</div>
</div>
