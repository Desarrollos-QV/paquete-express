@extends('master.back')

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-sm-flex align-items-center justify-content-between">
                <h3 class=" mb-0 bc-title"> <b>Cotizador de envios</b> </h3>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-xl-12 col-lg-12 col-md-12">
            <div class="card o-hidden border-0 shadow-lg">
                <div class="card-body ">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="p-5">
                                @include('alerts.alerts')
                                <div class="container pl-0 pr-0 ml-0 mr-0 w-100 mw-100">
                                    <div id="tabs">
                                        <!-- Tab panes -->
                                        <div class="tab-content">
                                            <div id="conf" class="container tab-pane active">
                                                <div class="row justify-content-center">
                                                    <div class="col-lg-12">
                                                        <form action="{{ route('back.setting.cotizador.update') }}"
                                                            method="POST" enctype="multipart/form-data">

                                                            @csrf
                                                            <div class="form-group ">
                                                                <h3><b>Datos Cotización</b></h3>
                                                            </div>
                                                            <div class="form-group ">
                                                                <label for="code_zip">Código Postal</label>
                                                                <input type="text" class="form-control "
                                                                    id="code_zip" name="code_zip"
                                                                    placeholder="{{ __('Enter CCódigo Postal') }}"
                                                                    value="{{ $setting->code_zip }}">
                                                            </div>
                                                            <div class="form-group ">
                                                                <label for="token_compomex">Token Api
                                                                    COPOMEX</label>
                                                                <input type="text" class="form-control "
                                                                    id="token_compomex" name="token_compomex"
                                                                    placeholder="{{ __('Enter Token Api COPOMEX') }}"
                                                                    value="{{ $setting->token_compomex }}">
                                                            </div>

                                                            <hr />
                                                            <div class="form-group mt-1">
                                                                <label for="apikey_estafeta">
                                                                    Credenciales para Estafeta
                                                                </label>
                                                                <div class="form-group row mt-2">
                                                                    <div class="col-md-12">
                                                                        <label for="apikey_estafeta">{{ __('API Key / Client ID') }}</label>
                                                                        <input type="text" class="form-control "
                                                                        id="apikey_estafeta" name="apikey_estafeta"
                                                                        placeholder="{{ __('API Key / Client ID') }}"
                                                                        value="{{ $setting->apikey_estafeta }}">
                                                                    </div>

                                                                    <div class="col-md-12 mt-2">
                                                                        <label for="secretkey_estafeta">
                                                                            {{ __('Secret Key / Client Secret') }}</label>
                                                                        
                                                                        <input type="text" class="form-control "
                                                                        id="client_secret_estafeta" name="client_secret_estafeta"
                                                                        placeholder="{{ __('Shared Secret / Client Secret') }}"
                                                                        value="{{ $setting->client_secret_estafeta }}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <div class="form-group d-flex justify-content-center">
                                                                    <button type="submit"
                                                                        class="btn btn-secondary btn-block w-100">{{ __('Submit') }}</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
