function agregar_carrito_publico(id, precio_descontado, cantidad = 1) {
    var contador = document.querySelector('.cantidad-contador' + id)
    
    
    
    if (contador != null) {
        cantidad = contador.innerText
    }




    // if (compraMinima >= cantidadMinima) {

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                    'content') // Agrega el token CSRF como encabezado
            },
            url: carritoAddUrl,
            type: 'POST',
            data: {
                producto_id: id,
                precio: precio_descontado,
                qty: cantidad
            },

            success: function(response) {
                iziToast.success({
                    title: 'Producto agregado al carrito',
                    backgroundColor: '#DAF6D3',
                    titleColor: '#479831',
                    iconColor: '#479831',
                    progressBar: false,
                    icon: 'fa-solid fa-square-check',
                    position: 'bottomRight',
                });
            },
            error: function(xhr) {
                console.error(xhr.responseText);
            }
        });
    // } else {
    //     iziToast.success({
    //         title: `La cantidad minima es de ${cantidadMinima}`,
    //         backgroundColor: '#9D150F',
    //         titleColor: '#ED391E',
    //         iconColor: '#DF0025',
    //         progressBar: false,
    //         icon: 'fa-solid fa-xmark',
    //         position: 'bottomRight',
    //     });
    // }

}

function agregar_carrito(dimension_id, precio_descontado) {
    var contador = document.querySelector('.cantidad-contador' + dimension_id)
    var cantidad = contador.getAttribute('data-cantidad')

    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                'content') // Agrega el token CSRF como encabezado
        },
        url: carritoAddUrl,
        type: 'POST',
        data: {
            dimension_id: dimension_id,
            precio: precio_descontado,
            qty: cantidad
        },

        success: function(response) {
            localStorage.setItem('carrito', JSON.stringify(response));

            iziToast.success({
                title: 'Producto agregado al carrito',
                backgroundColor: '#DAF6D3',
                titleColor: '#479831',
                iconColor: '#479831',
                progressBar: false,
                icon: 'fa-solid fa-square-check',
                position: 'bottomRight',
            });
        },
        error: function(xhr) {
            console.error(xhr.responseText);
        }
    });
}

function carrito_sumar(rowId, qty, seccion = 'public') {
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Agrega el token CSRF como encabezado
        },
        url: carritoSumarUrl,
        type: 'POST',
        data: {
            item_id: rowId,
            qty: qty,
            seccion: seccion
        },
        success: function(response) {
            $('#carrito-desplegado').html(response.view);
            actualizar_pedido(seccion);

            // Guardar el carrito completo en localStorage
            localStorage.setItem('carrito', JSON.stringify(response.cart));
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            toastr.info('Ha ocurrido un error');
        }
    });
}

function carrito_restar(rowId, qty, seccion = 'public', cantidad) {
    if (Number(cantidad) !== 0) {
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Agrega el token CSRF como encabezado
            },
            url: carritoQuitarUrl,
            type: 'POST',
            data: {
                item_id: rowId,
                qty: qty,
                seccion: seccion
            },
            success: function(response) {
                actualizar_pedido(seccion);
                $('#carrito-desplegado').html(response.view);

                // Guardar el carrito completo en localStorage
                localStorage.setItem('carrito', JSON.stringify(response.cart));
            },
            error: function(xhr) {
                console.error(xhr.responseText);
                toastr.info('Ha ocurrido un error');
            }
        });
    }
    // else {
    //     iziToast.success({
    //         title: [`La cantidad minima es de ${cantidadMinima}`](command:_github.copilot.openSymbolFromReferences?%5B%7B%22%24mid%22%3A1%2C%22path%22%3A%22%2FC%3A%2FUsers%2FNicolas%20osole%2FAppData%2FLocal%2FMicrosoft%2FTypeScript%2F5.4%2Fnode_modules%2F%40types%2Fjquery%2Fmisc.d.ts%22%2C%22scheme%22%3A%22file%22%7D%2C%7B%22line%22%3A7336%2C%22character%22%3A0%7D%5D "../../AppData/Local/Microsoft/TypeScript/5.4/node_modules/@types/jquery/misc.d.ts"),
    //         backgroundColor: '#9D150F',
    //         titleColor: '#ED391E',
    //         iconColor: '#DF0025',
    //         progressBar: false,
    //         icon: 'fa-solid fa-xmark',
    //         position: 'bottomRight',
    //     });
    // }
}

function carrito_remover(rowId, seccion = 'public') {
    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Agrega el token CSRF como encabezado
        },
        url: carritoRemoverUrl,
        type: 'POST',
        data: {
            item_id: rowId,
            seccion: seccion
        },
        success: function(response) {
            actualizar_pedido(seccion);
            $('#carrito-desplegado').html(response.view);

            // Guardar el carrito completo en localStorage
            localStorage.setItem('carrito', JSON.stringify(response.cart));
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            toastr.info('Ha ocurrido un error');
        }
    });
}
function actualizar_pedido(seccion = 'public') {


    $.ajax({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr(
                'content') // Agrega el token CSRF como encabezado
        },
        url: carritoActualizarUrl,
        type: 'POST',
        data: {
            seccion: seccion
        },
        success: function(response) {
            $('#carrito-total').html(response)
        },
        error: function(xhr) {
            console.error(xhr.responseText);
            toastr.info('Ha ocurrido un error')
        }
    });
}