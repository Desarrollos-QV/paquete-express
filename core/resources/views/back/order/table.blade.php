@foreach ($datas as $data)
    <tr id="order-bulk-delete">
        <td><input type="checkbox" class="bulk-item" value="{{ $data->id }}"></td>

        <td>
            {{ $data->transaction_number }}
        </td>
        <td>
        
            {{ json_decode(@$data->billing_info, true)['bill_first_name'] }}
        </td>

        <td>
            @if ($setting->currency_direction == 1)
                {{ $data->currency_sign }}{{ PriceHelper::OrderTotal($data) }}
            @else
                {{ PriceHelper::OrderTotal($data) }}{{ $data->currency_sign }}
            @endif
        </td>
        <td>
            @php
                $order = new App\Models\Order;
                $chk = $order->ViewScaleVolumetric($data->cart);
            @endphp
            <span class="badge bg-info">SW : {{ $chk['ScaleWeight'] }}</span>  <span class="badge bg-warning">VW: {{$chk['VolumetricWeight']}}</span>
        </td>
        <td>
            @if($data->type_ship == 0) <!-- Envio Local -->
            Envio Local
            @else
            Envio Foraneo
            @endif
        </td>
        <td>
            @if($data->guide_generated != null)
            <a class="btn btn-success btn-sm" href="{{ route('back.order.download.track', $data->id) }}" target="_blank">
                <i class="fas fa-download"></i>&nbsp; {{ __('Download PDF Track') }}
            </a>
            @else
            <a class="btn btn-primary btn-sm" href="{{ route('back.order.guide.generated', $data->id) }}">
                <i class="fas fa-file-invoice"></i>&nbsp; {{ __('Generated Guide') }}
            </a>
            @endif
        </td>
        <td>
            <div class="dropdown">
                <button
                    class="btn btn-{{ $data->payment_status == 'Paid' ? 'success' : 'danger' }} btn-sm dropdown-toggle"
                    type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true"
                    aria-expanded="false">
                    {{ $data->payment_status == 'Paid' ? __('Paid') : __('Unpaid') }}
                </button>
                <div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" data-toggle="modal" data-target="#statusModal" href="javascript:;"
                        data-href="{{ route('back.order.status', [$data->id, 'payment_status', 'Paid']) }}">{{ __('Paid') }}</a>
                    <a class="dropdown-item" data-toggle="modal" data-target="#statusModal" href="javascript:;"
                        data-href="{{ route('back.order.status', [$data->id, 'payment_status', 'Unpaid']) }}">{{ __('Unpaid') }}</a>
                </div>
            </div>
        </td>
        <td>
            <div class="dropdown">
                <button class="btn {{ $data->order_status }}  btn-sm dropdown-toggle" type="button"
                    id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    {{ $data->order_status }}
                </button>
                <div class="dropdown-menu animated--fade-in" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" data-toggle="modal" data-target="#statusModal" href="javascript:;"
                        data-href="{{ route('back.order.status', [$data->id, 'order_status', 'Pending']) }}">{{ __('Pending') }}</a>
                    <a class="dropdown-item" data-toggle="modal" data-target="#statusModal" href="javascript:;"
                        data-href="{{ route('back.order.status', [$data->id, 'order_status', 'In Progress']) }}">{{ __('In Progress') }}</a>
                    <a class="dropdown-item" data-toggle="modal" data-target="#statusModal" href="javascript:;"
                        data-href="{{ route('back.order.status', [$data->id, 'order_status', 'Delivered']) }}">{{ __('Delivered') }}</a>
                    <a class="dropdown-item" data-toggle="modal" data-target="#statusModal" href="javascript:;"
                        data-href="{{ route('back.order.status', [$data->id, 'order_status', 'Canceled']) }}">{{ __('Canceled') }}</a>
                </div>
            </div>
        </td>
        <td>
            <div class="action-list">
                <a class="btn btn-secondary btn-sm" href="{{ route('back.order.invoice', $data->id) }}">
                    <i class="fas fa-eye"></i>
                </a>
                <a class="btn btn-info btn-sm " href="{{ route('back.order.edit', $data->id) }}">
                    <i class="fas fa-pen"></i>
                </a>
                <a class="btn btn-danger btn-sm " data-toggle="modal" data-target="#confirm-delete" href="javascript:;"
                    data-href="{{ route('back.order.delete', $data->id) }}">
                    <i class="fas fa-trash-alt"></i>
                </a>

            </div>
        </td>
    </tr>
@endforeach
