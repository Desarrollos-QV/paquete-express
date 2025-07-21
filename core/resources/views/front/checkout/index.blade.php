@extends('master.front')

@section('title')
    {{ __('Billing') }}
@endsection

@section('content')
    <!-- Page Title-->
    <div class="page-title">
        <div class="container">
            <div class="column">
                <ul class="breadcrumbs">
                    <li><a href="{{ route('front.index') }}">{{ __('Home') }}</a> </li>
                    <li class="separator"></li>
                    <li>{{ __('Billing address') }}</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Page Content-->
    <div class="container padding-bottom-3x mb-1 checkut-page">
        <div class="row">
            <div class="col-xl-8 col-lg-8">
                <div class="row">
                    <div class="col-12">
                        <section class="card widget widget-featured-posts widget-featured-products p-4">
                            <h3 class="widget-title">{{ __('Items In Your Cart') }}</h3>
                            @foreach ($cart as $key => $item)
                                <div class="entry">
                                    <div class="entry-thumb"><a href="{{ route('front.product', $item['slug']) }}"><img
                                                src="{{ url('/core/public/storage/images/' . $item['photo']) }}"
                                                alt="Product"></a>
                                    </div>
                                    <div class="entry-content">
                                        <h4 class="entry-title"><a href="{{ route('front.product', $item['slug']) }}">
                                                {{ Str::limit($item['name'], 45) }}

                                            </a></h4>
                                        <span class="entry-meta">{{ $item['qty'] }} x

                                            @php
                                                $totalAttributePrice = 0;
                                                foreach ($item['attribute']['option_price'] as $option_price) {
                                                    $totalAttributePrice += $option_price;
                                                }
                                                $price = $item['main_price'] + $totalAttributePrice;
                                            @endphp
                                            {{ PriceHelper::setCurrencyPrice($price) }}.</span>

                                        @foreach ($item['attribute']['option_name'] as $optionkey => $option_name)
                                            <div class="entry-meta">
                                                <span
                                                    class="entry-meta d-inline">{{ $item['attribute']['names'][$optionkey] }}:</span>
                                                <span class="entry-meta d-inline"><b>{{ $option_name }}</b></span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </section>
                        <div class="card">
                            <div class="card-body">
                                <h6>{{ __('Billing Address') }}</h6>
                                <form id="checkoutBilling" action="{{ route('front.checkout.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="single_page_checkout" value="1">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="checkout-fn">{{ __('First Name') }}*</label>
                                                <input
                                                    class="form-control {{ $errors->has('bill_first_name') ? 'requireInput' : '' }}"
                                                    name="bill_first_name" type="text" id="checkout-fn"
                                                    value="{{ isset($user) ? $user->first_name : '' }}">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="checkout-ln">{{ __('Last Name') }}*</label>
                                                <input
                                                    class="form-control {{ $errors->has('bill_last_name') ? 'requireInput' : '' }}"
                                                    name="bill_last_name" type="text" id="checkout-ln"
                                                    value="{{ isset($user) ? $user->last_name : '' }}">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="checkout_email_billing">{{ __('E-mail Address') }}*</label>
                                                <input
                                                    class="form-control {{ $errors->has('bill_email') ? 'requireInput' : '' }}"
                                                    name="bill_email" type="email" id="checkout_email_billing"
                                                    value="{{ isset($user) ? $user->email : '' }}">
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label for="checkout-phone">{{ __('Phone Number') }}*</label>
                                                <input
                                                    class="form-control {{ $errors->has('bill_phone') ? 'requireInput' : '' }}"
                                                    name="bill_phone" type="text" id="checkout-phone"
                                                    value="{{ isset($user) ? $user->phone : '' }}">
                                            </div>
                                        </div>
                                    </div>
                                    @if (PriceHelper::CheckDigital())
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="form-group">
                                                    <label for="checkout-company">{{ __('Company') }}</label>
                                                    <input class="form-control" name="bill_company" type="text" required
                                                        id="checkout-company"
                                                        value="{{ isset($user) ? $user->bill_company : '' }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="checkout-address1">{{ __('Address') }}*</label>
                                                    <input
                                                        class="form-control {{ $errors->has('bill_address1') ? 'requireInput' : '' }}"
                                                        name="bill_address1" type="text" id="checkout-address1"
                                                        value="{{ isset($user) ? $user->bill_address1 : '' }}">
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="checkout-zip">{{ __('Zip Code') }}*</label>
                                                    <input
                                                        class="form-control {{ $errors->has('bill_zip') ? 'requireInput' : '' }}"
                                                        name="bill_zip" type="text" id="checkout-zip"
                                                        value="{{ isset($user) ? $user->bill_zip : '' }}">
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="checkout-city">{{ __('City') }}*</label>
                                                    <select class="form-control select2 select-search" name="bill_city"
                                                        id="checkout-city" required disabled>
                                                        <option value="{{ isset($user) ? $user->bill_city : '' }}">Select
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="checkout-city">{{ __('Colonia') }}*</label>
                                                    <select class="form-control select2 select-search" name="bill_colonia"
                                                        id="checkout-colonia" required disabled>
                                                        <option value="{{ isset($user) ? $user->bill_colonia : '' }}">
                                                            Select</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="form-group">
                                                    <label for="checkout-country">{{ __('Country') }}</label>
                                                    <select class="form-control" name="bill_country"
                                                        id="billing-country">
                                                        <option selected>{{ __('Choose Country') }}</option>
                                                        @foreach (DB::table('countries')->get() as $country)
                                                            <option value="{{ $country->name }}"
                                                                {{ isset($user) && $user->bill_country == $country->name ? 'selected' : '' }}>
                                                                {{ $country->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <input class="form-control" name="shippingInfo" required type="hidden" id="shippingInfo">
                                    @endif
                                </form>
                                <input class="form-control" name="peso" required type="hidden" step="0.1"
                                    id="peso" value="{{ $peso }}">
                                <input class="form-control" name="alto" type="hidden" step="0.1" id="alto"
                                    value="{{ $alto }}">
                                <input class="form-control" name="ancho" required type="hidden" step="0.1"
                                    id="ancho" value="{{ $ancho }}">
                                <input class="form-control" name="largo" type="hidden" step="0.1" id="largo"
                                    value="{{ $largo }}">
                                <input class="form-control" name="pvolum" type="hidden" id="pvolum"
                                    value="{{ $pvolum }}">
                                <input id="token_compomex" type="hidden" value="{{ $token }}">
                                <input id="code_zip" type="hidden" value="{{ $code_zip }}">
                                <input id="apikey_estafeta" type="hidden" value="{{ $apikey_estafeta }}">
                                <input id="client_secret_estafeta" type="hidden" value="{{ $client_secret_estafeta }}">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Sidebar          -->
            <div class="col-xl-4 col-lg-4">
                @include('includes.single_checkout_sidebar', $cart)
                @include('includes.single_checkout_modal')
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            var checkout_zip = $("#checkout-zip").val();
            if (checkout_zip != null) {
                check();
            }
            $("#checkout-zip").blur(function() {
                check();
            });

            function check() {

                var input_value = $("#checkout-zip").val(),
                    token_compomex = $("#token_compomex").val(),
                    code_zip = $("#code_zip").val(),
                    apikey_estafeta = $("#apikey_estafeta").val(),
                    client_secret_estafeta = $("#client_secret_estafeta").val();

                /**
                 * 
                 * Obtencion De ZIP CODE con COPOMEX
                 *  
                 ***/
                $.ajax({
                    url: '{{ route('user.shipping.code.submit') }}',
                    type: "GET",
                    data: {
                        codezip: input_value,
                        token_compomex: token_compomex,
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(response) {
                        $("#checkout-colonia").empty();
                        $("#checkout-city").empty();

                        $("#checkout-colonia").prop('disabled', false);
                        $("#checkout-city").prop('disabled', false);

                        if (response.code == 200) {
                            $("#checkout-city").append('<option value="' + response.ciudad + '">' +
                                response.ciudad + '</option>');
                            $.each(response.colonias, function(key, value) {
                                $("#checkout-colonia").append('<option value="' + value + '">' +
                                    value + '</option>');
                            });
                        }
                    }
                });


                /**
                 * 
                 * Conexion con API de Estafeta
                 *  
                 ***/

                var pvolum = $("#pvolum").val(),
                    alto = $("#alto").val(),
                    ancho = $("#ancho").val(),
                    largo = $("#largo").val();

                $.ajax({
                    url: '{{ route('user.shipping.paquete') }}',
                    type: "GET",
                    data: {
                        codezip: input_value,
                        code_zip_tienda: code_zip,
                        pvolum: pvolum,
                        alto: alto,
                        ancho: ancho,
                        largo: largo,
                        _token: '{{ csrf_token() }}'
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log("shipping => ", response);

                        if (response.code == 200) {
                            $("#shipping_id_select").empty();
                            $("#shipping_id_select").prop('disabled', false);
                            $("#shipping_id_select").append(
                                `<option value="" selected disabled>{{ __('Select Shipping Method') }}*</option>`
                            );

                            $("#shippingInfo").attr('value',JSON.stringify(response.data));

                            $.each(response.data, function(key, value) {
                                $("#shipping_id_select").append(`<option 
                                    data-price="${value.price}"
                                    data-warranty="${value.warranty}" 
                                    value="${value.code}">${value.name}</option>`);
                            });
                        }
                    }
                });
            };
        });
    </script>

@endsection
