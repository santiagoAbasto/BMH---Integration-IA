<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Home;
use App\Models\Imagen;
use App\Models\Nosotros;
use App\Models\Categoria;
use App\Models\Subcategoria;
use App\Models\Producto;
use App\Models\Contacto;
use App\Models\User;
use App\Models\Admin;
use App\Models\Metadatos;
use App\Models\ZonaPostal;
use App\Models\Carrito;
use App\Models\Impuesto;
use App\Models\Anuncio;
use App\Models\Uso;
use App\Models\Novedad;
use App\Models\Mail;
use App\Models\Descarga;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        Admin::factory()->create([
            'name' => 'pablo',
            'username' => 'pablo',
            'email' => 'pablo@mail.com',
            'password' => 'pablopablo',
            'rol' => 'administrador'
        ]);
        User::factory()->create([
            'name' => 'Julián',
            'username' => 'julian',
            'email' => 'julian.lora.96@gmail.com',
            'password' => 'manomano',
            'rol' => 'cliente',
            'habilitado' => true,
            'dni' => '1234',
            'direccion' => 'direccion',
            'provincia' => '02',
            'localidad' => 'Agronomía',
            'celular' => '1234',
            'cp' => '154',
            'descuento' => '10',
        ]);
        User::factory()->create([
            'name' => 'Julián',
            'username' => 'vendedor',
            'email' => 'julian.ariel.lora.96@gmail.com',
            'password' => 'palopalo',
            'rol' => 'vendedor',
            'habilitado' => true,
            'descuento' => '10',
        ]);
        Imagen::factory()->create([
            'path' => 'logo.png',
            'sector' => 'logo',
        ]);
        Imagen::factory()->create([
            'path' => 'logo2.png',
            'sector' => 'logo2',
        ]);
        Imagen::factory()->create([
            'path' => 'baner.png',
            'orden' => 'aa',
            'tipo' => 'imagen',
            'sector' => 'home-slider',
        ]);
        Imagen::factory()->create([
            'path' => 'baner2.png',
            'orden' => 'aa',
            'tipo' => 'imagen',
            'sector' => 'home-slider',
        ]);
        Imagen::factory()->create([
            'path' => 'baner3.png',
            'orden' => 'aa',
            'tipo' => 'imagen',
            'sector' => 'home-slider',
        ]);
        Imagen::factory()->create([
            'path' => 'nosotros-slider.png',
            'orden' => 'aa',
            'sector' => 'nosotros-slider',
        ]);
        Imagen::factory()->create([
            'path' => 'nosotros-slider1.png',
            'orden' => 'aa',
            'sector' => 'nosotros-slider',
        ]);
        Imagen::factory()->create([
            'path' => 'nosotros-slider2.png',
            'orden' => 'aa',
            'sector' => 'nosotros-slider',
        ]);
        Imagen::factory()->create([
            'path' => 'nosotros-video.mp4',
            'orden' => 'aa',
            'sector' => 'nosotros-portada',
            'tipo' => 'video'
        ]);
        Imagen::factory()->create([
            'path' => 'nosotros-baner.png',
            'orden' => 'aa',
            'sector' => 'nosotros-baner',
        ]);
        Nosotros::factory()->create([
            'info' => 'Tu compañero de experiencias',
            'mision' => 'La vocación firme de satisfacer a los clientes desarrollando piezas a medida y la responsabilidad de hacer bien las cosas son las claves de nuestro crecimiento.',
            'vision' => 'La vocación firme de satisfacer a los clientes desarrollando piezas a medida y la responsabilidad de hacer bien las cosas son las claves de nuestro crecimiento.',
            'valores' => 'Nuestra compañía es la conjunción del esfuerzo, el trabajo cotidiano y la sencillez de un grupo humano liderados por su fundador, y su Hijo, Carlos O. Kase. Con estos valores como guía, ofreciendo productos y servicios de calidad.',
            'imagen_file' => 'nosotros.png',
            'info_home' => '<span style="white-space-collapse: preserve;">En Conometal, nos enorgullece ser referentes en la venta de artículos para gas envasado, focalizándonos especialmente en productos destinados al hogar, camping y pesca. Nuestro principal compromiso es brindar a nuestros clientes productos de la más alta calidad, asegurando su plena satisfacción y confianza en soluciones fiables y eficientes para sus necesidades relacionadas con el gas. <br><br>Contamos con una amplia variedad de productos que nos posiciona como la elección preferida para aquellos que valoran la excelencia en cada detalle de sus adquisiciones.</span><br>',
            'imagen_file_home' => 'nosotros-home.png',
            'titulo_home' => 'Más de 44 años de experiencia',
            'titulo_baner' => 'Planta industrial de 2.000 m2, y un centro de distribución integrado de 1.000m2.',
            'texto_baner' => 'Los inicios de INDUSTRIAS KC, se remontan al año 1969 cuando Carlos Kase con sus primeras herramientas monta un taller en Morón, Buenos aires. Ese fue el comienzo de una larga trayectoria que nos lleva después de 50 años a posicionarnos en un lugar de renombre.',
            'imagen_file_kovea' => 'kovea.png',
            'titulo_kovea' => 'Distribuidores oficiales en LATAM',
            'texto_kovea' => 'Disfruta de los productos Kovea de la mano exclusiva de Conometal. 

Ofrecemos una gran variedad de productos para que te acompañen en tus aventuras'
        ]);
        Categoria::factory()->create([
            'nombre' => 'Anafes y cocinas a gas G.L.P, gas butano y eléctricas',
            'orden' => 'aa',
            'alias' => 'anafes',
            'portada' => 'hidraulicos.png',
            'destacada' => true,
            'descuento' => '5',
        ]);
        Categoria::factory()->create([
            'nombre' => 'Abrazaderas',
            'alias' => 'abrazaderas',
            'orden' => 'bb',
            'portada' => 'mecanico.png',
            'destacada' => true
        ]);
        Categoria::factory()->create([
            'nombre' => 'Accesorios trefilados',
            'alias' => 'trefilados',
            'orden' => 'cc',
            'portada' => 'tirafondos.png',
            'destacada' => true
        ]);
        Categoria::factory()->create([
            'nombre' => 'Aceite lubricante en aerosol',
            'alias' => 'lubricantes',
            'orden' => 'dd',
            'portada' => 'fix.png',
            'destacada' => true
        ]);
        Imagen::factory()->create([
            'path' => 'anafe.png',
            'producto_id' => 1,
            'sector' => 'producto',
            'tipo' => 'portada'
        ]);
        
        Imagen::factory()->create([
            'path' => 'anafe2.png',
            'producto_id' => 2,
            'sector' => 'producto',
            'tipo' => 'portada'
        ]);
        Imagen::factory()->create([
            'path' => 'anafe3.png',
            'producto_id' => 3,
            'sector' => 'producto',
            'tipo' => 'portada'
        ]);
        Imagen::factory()->create([
            'path' => 'regulador.png',
            'producto_id' => 4,
            'sector' => 'producto',
            'tipo' => 'portada'
        ]);
        Contacto::factory()->create([
            'direccion' => 'Ferré 2252, Caba',
            'iframe' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13126.981249887522!2d-58.43338652253966!3d-34.66113443716882!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x95bccb48dfbfb537%3A0xa86a574d3ba8e180!2sBULONES%20KC!5e0!3m2!1ses!2sar!4v1718039311027!5m2!1ses!2sar" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            'tel' => '+54 (11) 4919-3232',
            'mail' => 'ventas@buloneskc.com.ar',
            'whatsapp' => '+54 (11) 4919-3232'
        ]);
        Metadatos::factory()->create([
            'seccion' => 'home'
        ]);
        Metadatos::factory()->create([
            'seccion' => 'nosotros'
        ]);
        Metadatos::factory()->create([
            'seccion' => 'productos'
        ]);
        Metadatos::factory()->create([
            'seccion' => 'descargas'
        ]);
        Metadatos::factory()->create([
            'seccion' => 'novedades'
        ]);
        Metadatos::factory()->create([
            'seccion' => 'contacto'
        ]);
        Producto::factory()->create([
            'nombre' => 'Anafe 1 hornalla importado a gas butano con valija PVC',
            'descripcion' => 'Anafe portátil a gas butano kushiro con maletin de transporte .
Entrada lateral para gas envasado.
Fácil de usar y transportar, ideal para camping y aire libre.',
            'precio' => 27800,
            'categoria_id' => 1,
            'descuento' => '10',
            'destacada' => true,
            'caracteristicas' => '<ul><li>Ideal para camping, pesca, picnics y cualquier otra excursión al aire libre.</li><li>Funciona con cartuchos de gas butano de 227 gr (No incluído).</li><li>Liviano y fácil de transportar.</li><li>Mecanismo de cerrado seguro.</li><li>Calidad líder en el mercado Máxima durabilidad.</li><li>Soporte para cocinar.</li><li>Bandeja para la grasa.</li><li>Palanca de fijación del cartucho de gas.</li><li>Control de llama Alta/Baja con encendido electrónico.</li><li>Consumo nominal 1500 Kcal/hora – 1.74 Kw</li><li>Presión de trabap 280mmc-2.74 (Autorregulada)</li><li>Categoria 13 B/P</li><li>Aprobado por el IGA.https://www.youtube.com/watch?v=_PdrzB4MN64<br></li></ul>'
        ]);
        Producto::factory()->create([
            'nombre' => 'Anafe Conometal 4 Hornallas Gas Natural O Envasado Cocina Color Negro Gas Natural O Envasado',
            'descripcion' => 'Anafe portátil a gas butano kushiro con maletin de transporte .
Entrada lateral para gas envasado.
Fácil de usar y transportar, ideal para camping y aire libre.',
            'precio' => 27800,
            'categoria_id' => 1,
            'destacada' => true,
            'caracteristicas' => '<ul><li>Ideal para camping, pesca, picnics y cualquier otra excursión al aire libre.</li><li>Funciona con cartuchos de gas butano de 227 gr (No incluído).</li><li>Liviano y fácil de transportar.</li><li>Mecanismo de cerrado seguro.</li><li>Calidad líder en el mercado Máxima durabilidad.</li><li>Soporte para cocinar.</li><li>Bandeja para la grasa.</li><li>Palanca de fijación del cartucho de gas.</li><li>Control de llama Alta/Baja con encendido electrónico.</li><li>Consumo nominal 1500 Kcal/hora – 1.74 Kw</li><li>Presión de trabap 280mmc-2.74 (Autorregulada)</li><li>Categoria 13 B/P</li><li>Aprobado por el IGA.https://www.youtube.com/watch?v=_PdrzB4MN64<br></li></ul>'
        ]);
        
        Producto::factory()->create([
            'nombre' => 'Regulador Gas Envasado Cabezal Para Garrafa De 10 Kg',
            'descripcion' => 'Anafe portátil a gas butano kushiro con maletin de transporte .
Entrada lateral para gas envasado.
Fácil de usar y transportar, ideal para camping y aire libre.',
            'precio' => 27800,
            'categoria_id' => 1,
            'destacada' => true,
            'caracteristicas' => '<ul><li>Ideal para camping, pesca, picnics y cualquier otra excursión al aire libre.</li><li>Funciona con cartuchos de gas butano de 227 gr (No incluído).</li><li>Liviano y fácil de transportar.</li><li>Mecanismo de cerrado seguro.</li><li>Calidad líder en el mercado Máxima durabilidad.</li><li>Soporte para cocinar.</li><li>Bandeja para la grasa.</li><li>Palanca de fijación del cartucho de gas.</li><li>Control de llama Alta/Baja con encendido electrónico.</li><li>Consumo nominal 1500 Kcal/hora – 1.74 Kw</li><li>Presión de trabap 280mmc-2.74 (Autorregulada)</li><li>Categoria 13 B/P</li><li>Aprobado por el IGA.https://www.youtube.com/watch?v=_PdrzB4MN64<br></li></ul>'
        ]);
        Producto::factory()->create([
            'nombre' => 'Anafe Cocina Enlozado 1 Hornalla Con Robinete Gas Envasado',
            'descripcion' => 'Anafe portátil a gas butano kushiro con maletin de transporte .
Entrada lateral para gas envasado.
Fácil de usar y transportar, ideal para camping y aire libre.',
            'precio' => 27800,
            'categoria_id' => 1,
            'destacada' => true,
            'caracteristicas' => '<ul><li>Ideal para camping, pesca, picnics y cualquier otra excursión al aire libre.</li><li>Funciona con cartuchos de gas butano de 227 gr (No incluído).</li><li>Liviano y fácil de transportar.</li><li>Mecanismo de cerrado seguro.</li><li>Calidad líder en el mercado Máxima durabilidad.</li><li>Soporte para cocinar.</li><li>Bandeja para la grasa.</li><li>Palanca de fijación del cartucho de gas.</li><li>Control de llama Alta/Baja con encendido electrónico.</li><li>Consumo nominal 1500 Kcal/hora – 1.74 Kw</li><li>Presión de trabap 280mmc-2.74 (Autorregulada)</li><li>Categoria 13 B/P</li><li>Aprobado por el IGA.https://www.youtube.com/watch?v=_PdrzB4MN64<br></li></ul>'
        ]);
        
        ZonaPostal::factory()->create([
            'nombre' => 'Z1',
            'costo' => '800'
        ]);
        ZonaPostal::factory()->create([
            'nombre' => 'Z2',
            'costo' => '1000'
        ]);
        Carrito::factory()->create([
            'info_retiro' => '<p>Tu pedido comenzará a procesarse una vez que recibamos el pago. </p><p><span style="font-size:14px">Enviaremos por mail la factura </span><strong>confirmando la fecha</strong><span style="font-size: 14px;"> para retirar por Av. Eva Perón 9805, Loma Hermosa de 8:00 a 12:30 y de 14:00 a 18:30hs.</span></p><p><span style="font-size:20px"><strong><span style="color:#e74c3c">ATENCIÓN: Pedidos a retirar en 72hs hábiles. </span></strong></span></p>',
            'info' => '<div style="color: var(--rojo, #CC252B);
            font-family: Poppins;
            font-size: 16px;
            font-style: normal;
            font-weight: 600;
            line-height: 180%;">Venta sujeta a disponibilidad en stock</div>
            <div style="color: var(--tipografia, #1E1E1E);
            font-family: Poppins;
            font-size: 15px;
            font-style: normal;
            font-weight: 300;
            line-height: 180%;">Los precios se encuentran expresados en pesos<br>
            Plazo de entrega a todo del país: 7 días</div>',
            'pedido' => 'Gracias por elegirnos. En breve te llegará un mail de confirmación y más información sobre tu pedido',
            'pedido_titulo' => '¡Tu pedido se ha enviado exitosamente!',

            
        ]);
        Impuesto::factory()->create([
            'nombre' => 'IVA',
            'porcentaje' => 21
        ]);
        Anuncio::factory()->create([
            'contenido' => 'anuncio'
        ]);
        Uso::factory()->create([
            'nombre' => 'tranqueras'
        ]);
        Uso::factory()->create([
            'nombre' => 'silos'
        ]);
        Uso::factory()->create([
            'nombre' => 'usos generales'
        ]);
        Novedad::factory()->create([
            'portada' => 'novedad1.png',
            'etiqueta' => 'productos',
            'titulo' => 'Te acompañamos en todas tus aventuras',
            'epigrafe' => 'Conocé sobre nuestro  equipamiento.',
            'texto' => 'texto',
            'destacada' => true
        ]);
        Novedad::factory()->create([
            'portada' => 'novedad2.png',
            'etiqueta' => 'productos',
            'titulo' => 'Al mal clima, calentate un cafecito nuestro anafe Conometal ',
            'epigrafe' => 'Conocé sobre nuestro  equipamiento.',
            'texto' => 'texto',
            'destacada' => true
        ]);
        Novedad::factory()->create([
            'portada' => 'novedad3.png',
            'etiqueta' => 'productos',
            'titulo' => 'Incorporamos nuevos productos a nuestro catálogo',
            'epigrafe' => 'Conocé sobre nuestro  equipamiento.',
            'texto' => 'texto',
            'destacada' => true
        ]);
        Mail::factory()->create([
            'registro_titulo' => '¡Gracias por registrarte!',
            'registro' => 'Te enviaremos un mail de confirmación cuando tu cuenta este habilitada para realizar pedidos',
            'habilitado_titulo' => 'Ya podés comprar en nuestra página',
            'habilitado' => 'Tu cuenta en Industras KC ha sido habilitada. Ya podés ingresar y realizar tu pedido',
        ]);
        Descarga::factory()->create([
            'nombre' => 'Lista de precios 2024',
            'archivo' => 'listadeprecios2024',
            'path' => 'listadeprecios.xlsx',
            'sector' => 'lista de precios'
        ]);
        Descarga::factory()->create([
            'nombre' => 'a',
            'archivo' => 'datos-kc-modelo',
            'path' => 'modelo.xlsx',
            'sector' => 'datos-modelo'
        ]);
        Descarga::factory()->create([
            'nombre' => 'a',
            'archivo' => 'datos-kc-actual',
            'path' => 'actual.xlsx',
            'sector' => 'datos-actual'
        ]);
    }
}
