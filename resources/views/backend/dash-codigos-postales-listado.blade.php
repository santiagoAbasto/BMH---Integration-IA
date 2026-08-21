@foreach($codigos as $codigo)
<tr>
    <td><input class='input{{$codigo->id}} seleccionable' type="checkbox" onclick='seleccionar_producto(event.target)' data-id={{$codigo->id}}></td>
    <td>{{$codigo->cp}}</td>
    <td>{{ucfirst($codigo->provincia)}}</td>
    <td>{{$codigo->localidad}}</td>
    <td>{{$codigo->zona}}</td>
</tr>
@endforeach

<script>
    // Checkear seleccionados
    document.querySelectorAll('.input-oferta').forEach(element => {
        var input = document.querySelector('.input' + element.value)
        if(input){
            input.checked = true
            input.classList.add('seleccionado')
        }
        
    });
    document.querySelectorAll('.seleccionable').forEach((element) => {
        var todos = document.getElementById('select-all')
        if(!element.checked){
            todos.classList.add('todos')
            todos.checked = false
        }
    })
    
</script>