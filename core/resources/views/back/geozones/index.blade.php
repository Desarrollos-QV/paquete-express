@extends('master.back')

@section('content')
    <div class="page-wrapper">
        <div class="page-content">
            <!--breadcrumb-->
            <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                <div class="breadcrumb-title pe-3">Zonas</div>
                <div class="ps-3">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 p-0">
                            <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Listado</li>
                        </ol>
                    </nav>
                </div>
            </div>
            <!--end breadcrumb-->

            <div class="row">
                <div class="col-xl-12 mx-auto" style="text-align: right;">
                    <a href="{{ route('back.geozones.create') }}">
                        <button type="button" class="btn btn-success px-3 radius-10">Agregar Nueva Zona</button>
                    </a>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12 mx-auto">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="admin-table" width="100%"
                                    cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Cobertura</th>
                                            {{-- <th>Código postal</th> --}}
                                            <th>Costo del servicio</th>
                                            <th>Status</th>
                                            <th style="text-align: right">Opciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $row)
                                            <tr>
                                                <td>{{ $row->zone_name }}</td>
                                                <td>{{ number_format((float) $row->coverage, 2, '.', '') }} KM</td>
                                                {{-- <td>{{ $row->cp }}</td> --}}
                                                <td>${{ number_format($row->cost,2) }}</td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button
                                                            class="btn btn-{{ $row->status == 1 ? 'success' : 'danger' }} btn-sm dropdown-toggle"
                                                            type="button" id="dropdownMenuButton" data-toggle="dropdown"
                                                            aria-haspopup="true" aria-expanded="false">
                                                            {{ $row->status == 1 ? __('Enabled') : __('Disabled') }}
                                                        </button>
                                                        <div class="dropdown-menu animated--fade-in"
                                                            aria-labelledby="dropdownMenuButton">
                                                            <a class="dropdown-item"
                                                                href="{{ route('back.geozones.status', [$row->id, 1]) }}">{{ __('Enable') }}</a>
                                                            <a class="dropdown-item"
                                                                href="{{ route('back.geozones.status', [$row->id, 0]) }}">{{ __('Disable') }}</a>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td style="text-align: right">
                                                    <div class="action-list">
                                                        <a class="btn btn-secondary btn-sm "
                                                            href="{{ route('back.geozones.edit', [$row->id]) }}">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        @if ($row->id != 1)
                                                            <a class="btn btn-danger btn-sm " data-toggle="modal"
                                                                data-target="#confirm-delete" href="javascript:;"
                                                                data-href="{{ route('back.geozones.destroy', [$row->id]) }}">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    
{{-- DELETE MODAL --}}

  <div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-labelledby="confirm-deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">

		<!-- Modal Header -->
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">{{ __('Confirm Delete?') }}</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
		</div>

		<!-- Modal Body -->
        <div class="modal-body">
			{{ __('You are going to delete this Geozone. All contents related with this Geozone will be lost.') }} {{ __('Do you want to delete it?') }}
		</div>

		<!-- Modal footer -->
        <div class="modal-footer">
			<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
			<form action="" class="d-inline btn-ok" method="POST">

                @csrf

                @method('DELETE')

                <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>

			</form>
		</div>

      </div>
    </div>
  </div>

{{-- DELETE MODAL ENDS --}}

@endsection
