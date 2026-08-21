@extends('layouts.plantilla-front')

@section('metadatos')
<meta name='keyword' content='{{App\Models\Metadatos::all()[0]->keyword}}'>
<meta name='descripcion' content='{{App\Models\Metadatos::all()[0]->descripcion}}'>
@endsection

@section('content')

  <section class="novedades" style='padding-top:81px;padding-bottom:170px;'>
      <div class="container">
  
        
        <div class="row">
  
          @foreach ($novedades as $novedad)
          <div class='col-lg-4'>
            @include('components/novedad')
          </div>
          @endforeach
  
        </div>
      </div>
    </section>
    
@endsection