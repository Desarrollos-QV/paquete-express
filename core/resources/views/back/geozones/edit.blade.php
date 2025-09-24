@extends('master.back')

@section('content')
    <!-- Geozone Heading -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-sm-flex align-items-center justify-content-between">
                <h3 class=" mb-0 "><b>{{ __('Create Geozone') }}</b> </h3>
                <a class="btn btn-primary btn-sm" href="{{route('back.geozones.index')}}"><i class="fas fa-chevron-left"></i> {{ __('Back') }}</a>
            </div>
        </div>
    </div>

    <form class="admin-form row" action="{{ route('back.geozones.update',$geozone->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @csrf
        @include('back.geozones.form')
    </form>
@endsection
