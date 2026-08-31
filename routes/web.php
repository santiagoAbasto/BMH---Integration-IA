<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImagenController;
use App\Http\Controllers\NosotrosController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\MetadatosController;
use App\Http\Controllers\CodigoPostalController;
use App\Http\Controllers\ZonaPostalController;
use App\Http\Controllers\Auth\UserAuthController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ImpuestoController;
use App\Http\Controllers\AnuncioController;
use App\Http\Controllers\CaracteristicaController;
use App\Http\Controllers\DimensionController;
use App\Http\Controllers\DescargaController;
use App\Http\Controllers\NovedadController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\MedidaController;
use App\Http\Controllers\RepuestosController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

// Rutas para usuarios
Route::prefix('user')->name('user.')->group(function () {
    Route::post('/login', [UserAuthController::class, 'login'])->name('login'); // las rutas de esto se definen en el ajax
});

// Rutas para administradores
Route::get('/login/admin', [AdminAuthController::class, 'create'])->name('admin');
Route::prefix('admin')->name('admin.')->group(function () {
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login');
    Route::post('/logout', [AdminAuthController::class, 'destroy'])->name('logout');
});

// Route::middleware(['auth:admin', 'admin'])->group(function () {
//     // Rutas de administrador
// });

Route::controller(HomeController::class)->group(function () {
    Route::get('/', 'home')->name('home');
    Route::get('buscar', 'search')->name('search');
    
    Route::middleware('admin')->group(function(){
        Route::put('dashboard/logo-update', 'updateLogo')->name('logo.update');
        Route::put('dashboard/slider-update', 'updateSlider')->name('slider.update');
        Route::put('dashboard/slider-texto', 'slider_texto')->name('slider.texto');
    });

    
});

Route::controller(NosotrosController::class)->group(function () {
    Route::get('nosotros', 'index')->name('nosotros');
    Route::put('dashboard/nosotros', 'update')->middleware('admin')->name('nosotros.update');
});

Route::controller(CategoriaController::class)->group(function () {
    
    Route::get('categorias', 'index')->name('categorias');
    Route::get('/categoria/{id}/atributos', 'getAtributos')->name('categoria.atributos');

    Route::middleware('admin')->group(function(){
        Route::get('dashboard/categorias', 'dash_categorias')->name('dashboard.categorias');
        Route::get('dashboard/categoria/crear', 'create')->name('categoria.create');
        Route::post('dashboard/categoria/store', 'store')->name('categoria.store');
        Route::put('dashboard/categorias/update', 'update')->name('categoria.update');
        Route::delete('dashboard/subcat/delete', 'subcategoria_delete')->name('subcategoria.delete');
        Route::delete('dashboard/categorias', 'delete')->name('categoria.delete');
        Route::post('dashboard/categoria-destacada', 'actualizarDestacado')->name('categoria.destacada');
        Route::get('dashboard/categoria/{id}', 'show')->name('categoria.show');
        Route::post('dashboard/categoria/actualizar-porcentaje', 'actualizarPorcentaje')->name('categoria.actualizarPorcentaje');
    });

});

Route::controller(MedidaController::class)->group(function () {
    
    Route::middleware('admin')->group(function(){
        Route::post('dashboard/medida/store', 'store')->name('medida.store');
        Route::put('dashboard/medidas/update', 'update')->name('medida.update');
        Route::delete('dashboard/medidas', 'delete')->name('medida.delete');
    });

});

Route::controller(RepuestosController::class)->group(function () {
    
    Route::middleware('admin')->group(function(){
        Route::post('dashboard/repuesto/store', 'store')->name('repuesto.store');
        Route::put('dashboard/repuesto/update', 'update')->name('repuesto.update');
        Route::delete('dashboard/repuesto', 'delete')->name('repuesto.delete');
    });

});

Route::controller(ProductoController::class)->group(function () {
    
    Route::get('productos', 'index')->name('productos');
Route::get('filtro-rodamiento', 'filtroRodamiento')->name('filtroRodamientos');
    Route::get('productos-filtrar', 'filtrar_productos')->name('productos.filtrar');
    Route::get('ofertas', 'ofertas')->name('ofertas');
    Route::get('producto', 'producto')->name('producto');
    Route::get('load', 'load')->name('productos.load');
    Route::get('searchZona', 'searchZona')->name('search.zona');
    Route::get('/getProductoValor/{columnaId}/{productoId}/{categoriaId}', 'getProductoValor');

    Route::middleware('cliente')->group(function(){
        Route::get('productos-zona-clientes', 'productos_clientes')->name('productos.clientes');
        Route::get('productos-zona-filter', 'productos_clientes_filter')->name('productos.clientes.filter');
        Route::get('productos-zona-home', 'productos_clientes')->name('productos.home');
        Route::post('productos-clientes-buscar', 'productos_clientes_buscar')->name('productos.clientes.buscar');
        Route::get('lista-de-precios', 'lista_de_precios')->name('lista');
        Route::get('productos-carrito-vista', 'productos_vista')->name('productos.carrito.vista');

    });

    Route::middleware('admin')->group(function(){
        Route::post('dashboard/producto-destacado', 'actualizarDestacado')->name('producto.destacada');
        Route::get('dashboard/productos', 'dash_productos')->name('dashboard.productos');
        Route::get('dashboard/productos/exportar-excel', 'exportarExcel')->name('dashboard.productos.exportar');
        Route::get('dashboard/productos/crear', 'create')->name('producto.create');
        Route::post('dashboard/producto/store', 'store')->name('producto.store');
        Route::get('dashboard/productos/editar', 'edit')->name('producto.edit');
        Route::put('dashboard/productos/actualizar', 'update')->name('producto.update');
        Route::delete('dashboard/producto/delete', 'delete')->name('producto.delete');
        Route::put('dashboard/producto-imagen-update', 'imagen_update')->name('producto.imagen.update');
        Route::put('dashboard/producto-portada-update', 'portada_update')->name('producto.portada');
        Route::post('dashboard/buscar-producto', 'dash_buscar_producto')->name('dashboard.buscar.producto');
        Route::get('dashboard/productos/buscar-partes', 'buscarPartes')->name('producto.buscar.partes');
        Route::post('dashboard/buscar-producto-oferta', 'dash_buscar_producto_oferta')->name('dashboard.buscar.producto.oferta');
        Route::post('dashboard/oferta/store', 'oferta_store')->name('oferta.store');
        Route::post('actualizar-precios', 'actualizarPreciosExcel')->name('actualizar.precios');
        Route::post('actualizar-clientes', 'actualizar_clientes')->name('actualizar.clientes');
        Route::post('actualizar-categoriasExcel', 'actualizar_categoriasExcel')->name('actualizar.categoriasExcel');
        Route::post('/producto/actualizar-porcentaje', 'actualizarPorcentaje')->name('producto.actualizarPorcentaje');
        Route::get('/admin/categoria/{id}/caracteristicas', 'getCategoriaCaracteristicas');


    });
});

Route::controller(DimensionController::class)->middleware('admin')->group(function () {
    Route::delete('dashboard/dimension-delete', 'delete')->name('dimension.delete');
});

Route::controller(DescargaController::class)->group(function () {
    Route::get('descargas', 'index')->name('descargas');
    Route::get('datos-kc', 'datos_kc')->name('datos.kc');
    Route::post('datos-kc-update', 'datos_kc_update')->name('datos.update');
    Route::middleware('admin')->group(function(){
        Route::get('dashboard/descargas', 'dash_descargas')->name('dashboard.descargas');
        Route::get('dashboard/lista-de-precios', 'dash_lista')->name('dashboard.lista');
        Route::post('dashboard/descarga-store', 'store')->name('descarga.store');
        Route::put('dashboard/descarga-update', 'update')->name('descarga.update');
        Route::delete('dashboard/descarga-delete', 'delete')->name('descarga.delete');
    });
});


Route::controller(CaracteristicaController::class)->group(function () {

    Route::middleware('admin')->group(function(){
        Route::get('dashboard/caracteristica', 'dash_caracteristicas')->name('dashboard.caracteristicas');
        // Route::get('dashboard/lista-de-precios', 'dash_lista')->name('dashboard.lista');
        Route::post('dashboard/caracteristicas-store', 'store')->name('caracteristicas.store');
        Route::put('dashboard/caracteristicas-update', 'update')->name('caracteristicas.update');
        Route::delete('dashboard/caracteristicas-delete', 'delete')->name('caracteristicas.delete');
    });
});

Route::controller(NovedadController::class)->group(function () {
    Route::get('novedades', 'index')->name('novedades');
    Route::get('novedad', 'novedad')->name('novedad');
    Route::middleware('admin')->group(function(){
        Route::get('dashboard/novedades', 'dash_novedades')->name('dashboard.novedades');
        Route::get('dashboard/novedades/crear', 'create')->name('novedad.crear');
        Route::get('dashboard/novedades/editar', 'edit')->name('novedad.editar');
        Route::post('dashboard/novedad-store', 'store')->name('novedad.store');
        Route::put('dashboard/novedad-update', 'update')->name('novedad.update');
        Route::delete('dashboard/novedad-delete', 'delete')->name('novedad.delete');
        Route::post('dashboard/novedad-destacada', 'actualizarDestacada')->name('novedad.destacada');
    });
});


Route::controller(ContactoController::class)->group(function () {
    Route::get('contacto', 'index')->name('contacto');
    
    
    Route::put('contacto/update', 'update')->middleware('admin')->name('contacto.update');
    Route::post('contacto/enviar', 'mailContacto')->name('enviar.mail');
});

Route::controller(PedidoController::class)->middleware('admin')->group(function () {
    Route::get('pedidos', 'ventas')->name('ventas');
        Route::get('pedido-datos', 'pedido_datos')->name('pedido.datos');
        Route::get('pedido-pdf', 'pedido_pdf')->name('pedido.pdf');
    Route::post('dashboard/buscar-pedido', 'dash_buscar_pedido')->name('dashboard.buscar.pedido');
    Route::post('dashboard/modificar-estado', 'update_estado')->name('update.estado');
    Route::post('dashboard/modificar-estado-orden', 'update_estado_orden')->name('update.estado.orden');
    Route::delete('pedido-delete', 'delete')->name('pedido.delete');
    Route::get('datos-transferencia', 'postcompra_transferencia')->name('postcompra.transferencia');
    Route::get('pedido-realizado', 'postcompra_general')->name('postcompra.general');
    Route::put('descuento-update', 'descuento_update')->name('descuento.update');
});

Route::controller(CarritoController::class)->group(function () {

    Route::get('carrito-publico', 'carrito_publico')->name('carrito.publico');
    Route::post('carrito/add', 'agregar_carrito')->name('carrito.agregar');
    Route::post('carrito/sumar', 'carrito_sumar')->name('carrito.sumar');
    Route::post('carrito/quitar', 'carrito_quitar')->name('carrito.quitar');
    Route::post('carrito/remover', 'carrito_remover')->name('carrito.remover');
    Route::post('carrito/actualizar-pedido', 'actualizar_pedido')->name('carrito.actualizar');



    Route::get('pedido', 'pedido')->name('pedido');
    Route::get('carrito/actualizar-total-pedido', 'actualizar_total_pedido')->name('actualizar.total.pedido');
    Route::get('compra-efectuada', 'registrar_pedido')->name('registrar.pedido');
    Route::post('realizar-pedido', 'realizar_pedido')->name('realizar.pedido');


    Route::middleware('cliente')->group(function(){
        Route::get('/carrito', 'carrito')->name('carrito');
        Route::get('/actualizar-subtotal', 'actualizar_subtotal')->name('actualizar.subtotal');
        
        Route::get('tipo-pago', 'tipo_envio')->name('tipo.envio');
        Route::post('repetir-pedido', 'repetir_pedido')->name('repetir.pedido');
        Route::post('repetir-pedido', 'repetir_pedido')->name('repetir.pedido');
      



    });
    
    // Route::post('carrito-desplegado/sumar', 'carrito_desplegado_sumar')->name('carrito.desplegado.sumar');
    // Route::post('carrito-desplegado/quitar', 'carrito_desplegado_quitar')->name('carrito.desplegado.quitar');
    // Route::post('carrito-desplegado/remover', 'carrito_desplegado_remover')->name('carrito.desplegado.remover');
    // Route::post('carrito/calcular', 'calcular_envio')->name('carrito.calcular');

    // Route::post('credito', 'generar_credito')->name('generar.credito');
    // Route::post('debito', 'generar_debito')->name('generar.debito');
    // Route::post('realizar-pedido', 'realizar_pedido')->name('realizar.pedido');
    
    // Route::get('checkeo-stock', 'checkear_stock')->name('checkear.stock');

    Route::middleware('admin')->group(function(){
        Route::get('dashboard/carrito/informacion', 'informacion')->name('dashboard.informacion');
        Route::put('dashboard/carrito/informacion/update', 'carrito_info_update')->name('carrito.informacion.update');
        Route::post('dashboard/carrito/bonifaciones', 'carrito_bonificaciones')->name('carrito.bonificaciones');

    });
});

Route::controller(MailController::class)->middleware('admin')->group(function () {
    
    Route::get('dashboard/mails', 'index')->name('dashboard.mails');
    Route::put('dashboard/mails-update', 'update')->name('mails.update');
    // Route::delete('newsletter/delete', 'delete')->name('newsletter.delete');
    // Route::get('newsletter/enviar', 'enviar')->name('newsletter.enviar');

    
});

Route::controller(NewsletterController::class)->group(function () {
    Route::post('newsletter/crear', 'crear')->name('newsletter.crear');
    
    Route::middleware('admin')->group(function(){
        Route::get('dashboard/newsletter', 'newsletter')->name('newsletter');
        Route::delete('newsletter/delete', 'delete')->name('newsletter.delete');
        Route::get('newsletter/enviar', 'enviar')->name('newsletter.enviar');
    });
    
});

Route::controller(CodigoPostalController::class)->group(function () {
    Route::get('dashboard/codigos-postales', 'codigos_postales')->middleware('admin')->name('dashboard.cp');
    Route::post('buscar-cp', 'buscar')->name('buscar.cp');
    Route::put('cp-update', 'update')->middleware('admin')->name('cp.update');
});

Route::controller(ZonaPostalController::class)->middleware('admin')->group(function () {
    Route::get('zonas-postales', 'zonas')->name('zonas.postales');
    Route::post('zonas-postales/store', 'store')->name('zona.store');
    Route::put('zonas-postales/update', 'update')->name('zona.update');
    Route::delete('zonas-postales/delete', 'delete')->name('zona.delete');
});

Route::controller(ImpuestoController::class)->middleware('admin')->group(function (){
    Route::get('impuestos', 'impuestos')->name('dashboard.impuestos');
    Route::put('impuesto-update', 'update')->name('impuesto.update');
});

Route::controller(AnuncioController::class)->middleware('admin')->group(function (){
    Route::get('anuncio', 'index')->name('dashboard.anuncio');
    Route::put('anuncio/update', 'update')->name('anuncio.update');
    Route::post('anuncio/mostrar', 'mostrar')->name('anuncio.mostrar');
});

Route::post('dashboard/crear-usuario', [RegisteredUserController::class, 'store'])
                ->name('crear.usuario');

Route::controller(UserController::class)->group(function () {
    Route::get('registro', 'form_registro')->name('form-registro');

    Route::middleware('cliente')->group(function(){
        Route::get('mis-datos', 'cliente_datos')->name('cliente.datos');
        Route::post('mis-datos-reventa', 'reventaCliente')->name('reventa.user');
        Route::post('mis-datos-margenes', 'margenesReventa')->name('reventa.margenes');
        Route::post('mis-datos-cliente', 'updateDate')->name('clienteD.update');
        Route::get('historial', 'cliente_historial')->name('cliente.historial');
        Route::get('cliente-pedido', 'vendedor_pedido_datos')->name('cliente.pedido');
    });

    // Route::middleware('vendedor')->group(function(){
    //     Route::get('clientes', 'vendedor_clientes')->name('vendedor.clientes');
    //     Route::put('clientes-asociar', 'vendedor_clientes_asociar')->name('vendedor.clientes.asociar');
    //     Route::delete('clientes-desasociar', 'vendedor_clientes_desasociar')->name('vendedor.clientes.desasociar');
    //     Route::get('cliente', 'vendedor_cliente')->name('vendedor.cliente');
    //     Route::get('cliente-pedido', 'vendedor_pedido_datos')->name('vendedor.pedido.datos');
    // });
    
    
    Route::middleware('admin')->group(function(){
        Route::get('dashboard/nuevo-usuario', 'nuevoUsuario')->name('nuevo.usuario');
        Route::get('dashboard/usuarios', 'usuarios')->name('usuarios');
        Route::delete('eliminar-usuario', 'delete')->name('usuario.delete');
        Route::put('editar-usuario', 'update')->name('usuario.update');
        Route::get('dashboard/clientes', 'clientes')->name('dashboard.clientes');
        Route::get('dashboard/vendedores', 'vendedores')->name('dashboard.vendedores');
        Route::put('dashboard/clientes/update', 'cliente_update')->name('cliente.update');
        Route::post('dashboard/clientes/buscar', 'cliente_buscar')->name('cliente.buscar');
        Route::delete('dashboard/clientes/delete', 'cliente_delete')->name('cliente.delete');
        Route::post('dashboard/clientes/habilitar', 'cliente_habilitar')->name('cliente.habilitar');
        Route::put('dashboard/vendedores/update', 'vendedor_update')->name('vendedor.update');
        Route::delete('eliminar-vendedor', 'vendedor_delete')->name('vendedor.delete');

    });
});

Route::controller(MetadatosController::class)->middleware('admin')->group(function () {
    Route::put('editar-metadato', 'update')->name('metadatos.update');
});

Route::controller(DashboardController::class)->middleware(['admin'])->group(function () {
    Route::get('/adm', 'index');
    Route::get('/dashboard', 'index')->name('dashboard');

    Route::get('dashboard/logo', 'logo')->name('dashboard.logo');
    Route::get('dashboard/home-slider', 'home_slider')->name('dashboard.home-slider');

    Route::get('dashboard/ofertas', 'ofertas')->name('dashboard.ofertas');

    Route::get('dashboard/nosotros', 'nosotros')->name('dashboard.nosotros');
    
    Route::get('dashboard/contacto', 'contacto')->name('dashboard.contacto');
    
    Route::get('dashboard/metadatos', 'metadatos')->name('metadatos');

    Route::get('dashboard/medidas', 'medidas')->name('dashboard.medidas');
    Route::get('dashboard/repuestos', 'repuestos')->name('dashboard.repuestos');
    Route::get('dashboard/bonificaciones', 'bonificaciones')->name('dashboard.bonificaciones');

});

Route::controller(ImagenController::class)->middleware('admin')->group(function () {
    Route::post('dashboard/home-slider', 'store')->name('imagen.store');
    Route::delete('dashboard/home-slider', 'delete')->name('imagen.delete');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// BMH AI Technical Sales Advisor
require __DIR__.'/assistant.php';

require __DIR__.'/auth.php';
