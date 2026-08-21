<div id='loadMoreTrigger' style='text-align:center;margin-top:20px;margin-bottom:40px;'></div>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var loadMoreTrigger = document.getElementById('loadMoreTrigger');
        var triggerActivated = false;
        var xpag = 12;
        var contador = xpag;
        window.addEventListener('scroll', function() {
            if (triggerActivated) return;
            var triggerPosition = loadMoreTrigger.getBoundingClientRect().top;
            var windowPosition = window.innerHeight;
    
            // Si el elemento de carga está visible en la ventana, carga más productos
            if (triggerPosition <= windowPosition) {
                loadMoreTrigger.innerHTML = '<div class="loading-spinner"></div>  Cargando productos';
                triggerActivated = true;
                $.ajax({
                    url: "{{ route('productos.load') }}",
                    type: 'GET',
                    data: {
                        categoria_id: {{$categoria_id}},
                        busqueda: "{{$busqueda}}",
                        contador:contador,
                        xpag:xpag,
                    },
                    success: function(response) {
                        //console.log(response)
                        contador = contador + xpag;
                        $('#listado').append(response);
                        if(response == ''){
                            console.log('vacio')
                        } else {
                            triggerActivated = false
                        }
                        loadMoreTrigger.innerHTML = ''
                        
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        loadMoreTrigger.innerHTML = ''
                    }
                });
            }
        });
    })
</script>