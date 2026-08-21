<style>
    thead{
        background: #EFEFEF;
        height: 52px;
    }
    th{
        font-size: 16px;
        font-style: normal;
        font-weight: 300;
        line-height: normal;
        align-content: center;
    }
    td{
        height: 110px;
        align-content: center;
    }
</style>

<table class="table">
    <thead>
      <tr>
        <th style='border-radius: 24px 0px 0px 0px;' scope="col"></th>
        <th scope="col">Código</th>
        <th scope="col">Descripción</th>
        <th scope="col">Precio</th>
        <th scope="col">Cantidad</th>
        <th style='border-radius: 0px 24px 0px 0px;' scope="col"></th>
      </tr>
    </thead>
    <tbody>
        @foreach($cart as $item)
        <?php $producto = App\Models\Producto::find($item->id) ?>
        <tr>
            <td>
                <div style='width:96px;height:85px;border:1px solid #dfdfdf;border-radius:10px;background-image:url("imagenes/{{$producto->portada()->path}}");background-size:contain;background-position:center;background-repeat:no-repeat;'>

                </div>
            </td>
            <td>{{$producto->codigo}}</td>
            <td>{{$producto->nombre}}</td>
            <td>$ {{$producto->precio_final()}}</td>
            <td>
                @include('frontend.components.cantidad-carrito')
            </td>
            <td>
                @include('frontend.components.remove-btn', ['seccion' => 'public'])
            </td>
        </tr>
        @endforeach
    </tbody>
</table>