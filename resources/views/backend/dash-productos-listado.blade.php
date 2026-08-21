@foreach ($productos as $producto)
<tr>
    <td>{{$producto->orden}}</td>
    <td>{{$producto->codigo}}</td>
    <td>
            @if($producto->portada() && $producto->portada()->path)

        <img src="{{asset('imagenes/'.$producto->portada()->path)}}" class="img-thumbnail" style="max-width: 100px; height:70px;">
        @else
        <p>No tiene imagen</p>
        @endif
        </td>
    <td>{{ucfirst($producto->nombre)}}</td>
    <td>{{$producto->categoria()->first() != null ? ucfirst($producto->categoria()->first()->nombre) : '-'}}</td>
    {{-- <td>{{number_format($producto->precio, 2, ',', '.')}}</td> --}}
    <td><input class='categoriaCheckbox form-check-input' style='cursor: pointer;' type="checkbox" data-id="{{$producto->id}}" id="producto{{$producto->id}}" name="destacada" value="1" {{ $producto->destacada ? 'checked' : '' }}></td>
    <td>
    <div class='d-flex'>
        <a href='{{route('producto.edit', ['id' => $producto->id])}}'>
        <button type="button" class="btn btn-primary btn-sm me-1"><i class="fa-regular fa-pen-to-square"></i></button>
        </a>
        <form action="{{ route('producto.delete', ['id' => $producto->id]) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" ><i class="fa-solid fa-trash-can"></i></button>
        </form>    </div>
    
    </td>
</tr>


@endforeach