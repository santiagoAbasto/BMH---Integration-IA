{{-- <div style='height:48px;'>
    <form action="{{route('productos', ['categoria' => 0])}}" method="GET">
        @csrf
        @include('components/buscador')
    </form>

</div> --}}
{{-- <div style="  border-bottom: 1px solid #E0E0E0; padding-bottom: 14px">

<a style='color: var(--Verde, #236644);
font-family: "Roboto Condensed";
font-size: 16px;
font-style: normal;
font-weight: 400;
line-height: 130%; /* 20.8px */
letter-spacing: 0.96px;
text-transform: uppercase;'>Categorias</a>
</div> --}}
@foreach($categorias as $categoria)
<div class="filtroCat" style='padding-top: 0px; padding-left: 8px'>
    <a href='{{route('productos', ['categoria' => $categoria->id])}}' class='filtroCate {{$categoria_id == $categoria->id ? 'filtrando' : ''}}' data-id='{{$categoria->id}}'>{{ucfirst($categoria->nombre)}}</a>
</div>
@endforeach