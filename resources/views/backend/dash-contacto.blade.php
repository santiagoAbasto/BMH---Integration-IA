@extends('layouts.plantilla-back')

@section('content')

<h1 class='mb-4'>Contacto</h1>

  @if(session('success'))
      <div class="alert alert-success">
          {{ session('success') }}
      </div>
  @endif

  <div class="card">
    <div class="card-header">
      Editar información
    </div>
    <div class="card-body">

      <form action="{{route('contacto.update')}}"  method="POST" enctype="multipart/form-data">
        @csrf
        @method('put')

        <div class="mb-3">
          <label for="direccion" class="form-label">Dirección</label>
          <input type="text" class="form-control"  name='direccion' value='{{$contacto->direccion}}'>
        </div>
        <div class="mb-3">
          <label for="iframe" class="form-label">Google Maps Iframe</label>
          <input type="text" class="form-control"  name='iframe' value='{{$contacto->iframe}}' required>
        </div>
        <div class='row'>
          <div class="mb-3 col-6">
            <label for="mail" class="form-label">Email</label>
            <input type="text" class="form-control"  name='mail' value='{{$contacto->mail}}'>
          </div>
          <div class="mb-3 col-6">
            <label for="tel" class="form-label">Teléfono</label>
            <input type="text" class="form-control"  name='tel' value='{{$contacto->tel}}'>
          </div>
        </div>
        <div class='row'>
          
          <div class="mb-3 col-6">
            <label for="wpp" class="form-label">WhatsApp</label>
            <input type="text" class="form-control"  name='wpp' value='{{$contacto->whatsapp}}'>
          </div>
          <div class="mb-3 col-6">
            <label for="fb" class="form-label">Facebook</label>
            <input type="text" class="form-control"  name='fb' value='{{$contacto->facebook}}'>
          </div>
          <div class="mb-3 col-6">
            <label for="ig" class="form-label">Instagram</label>
            <input type="text" class="form-control"  name='ig' value='{{$contacto->instagram}}'>
          </div>

          <div class="mb-3 col-6">
            <label for="ig" class="form-label">Tik tok</label>
            <input type="text" class="form-control"  name='tiktok' value='{{$contacto->tiktok}}'>
          </div>
        </div>
        

        <button type="submit" class="btn btn-primary" style='float: right;'>Actualizar</button>
      </form>
    </div>
  </div>

  
@endsection