<div class="col-lg-6 col-md-12 mx-auto">
	<div class="card">
		<div class="card-body">
			<div class="tab-content" id="myTabContent1">
				<h1 style="font-size: 20px">Información de la zona</h1>
				<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
					<div class="card py-3 m-b-30">
						<div class="card-body">
							<div class="row g-3" style="padding-bottom: 1rem;">
								<div class="form-group col-md-12">
									<label for="name">Ubicación</label>
									<input id="pac-input" class="controls form-control" name="name" value="{{ $data->name }}" type="text" placeholder="Ingresa la Ciudad">
								</div>
								<div class="form-group col-md-6">
									<label for="zone_name">Nombre de la zona</label>
									<input type="text" name="zone_name" id="zone_name" value="{{$data->zone_name}}" class="form-control" required>
								</div>
								{{-- <div class="form-group col-md-6">
									<label for="cp">Código postal</label>
									<input type="text" name="cp" id="cp" value="{{$data->cp}}" class="form-control" required>
								</div> --}}
								<div class="form-group col-md-6">
									<label for="price">Costo del servicio en esta área</label>
									<input type="text" name="price" id="price" value="{{$data->price}}" class="form-control" required>
								</div>
							</div> 
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>


<div class="col-lg-6 col-md-12 mx-auto">
	<div class="card">
		<div class="card-body">
			<div class="tab-content" id="myTabContent1">
				<h1 style="font-size: 20px">
					Zona de cobertura
				</h1>

				<div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="name">Perimetro de servicio</label>
							<input type="text" readonly id="coverage" class="form-control" value="{{$data->coverage}} km" name="coverage">
						</div>

						<div class="form-group col-md-6">
							<label for="name">Status de la zona</label>
							<select name="status" id="status" class="form-control">
								<option value="1" @if($data->status == 1) selected @endif>Enable</option>
								<option value="0" @if($data->status == 0) selected @endif>Disable</option>
							</select>
						</div>

						<div class="form-group col-md-12" style="margin-top:25px;">
							<label for="name">Selecciona tu zona</label>
							@include('back.geozones.google')
						</div>
					</div>
					
					
					<button type="submit" class="btn btn-success btn-cta">Guardar Cambios</button>
				</div>
			</div>
		</div>
	</div>
</div>
