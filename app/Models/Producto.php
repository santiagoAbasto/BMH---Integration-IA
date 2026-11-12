<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Image;
use App\Services\LogosSitio;
use Illuminate\Support\Facades\Auth;
use App\Models\Impuesto;

class Producto extends Model
{
    use HasFactory;

    public const ORDEN_MANUAL = 'manual';
    public const ORDEN_ALFA_ASC = 'alfa_asc';
    public const ORDEN_ALFA_DESC = 'alfa_desc';

    /** Modos válidos para el criterio de orden de equivalencias/aplicaciones/partes. */
    public const MODOS_ORDEN = [
        self::ORDEN_MANUAL,
        self::ORDEN_ALFA_ASC,
        self::ORDEN_ALFA_DESC,
    ];

    public static function normalizarModoOrden($valor): string
    {
        return in_array($valor, self::MODOS_ORDEN, true) ? (string) $valor : self::ORDEN_MANUAL;
    }

    /**
     * Orden de los productos: el manual (campo `orden`), y por nombre entre
     * los que lo comparten.
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('nombre')->orderBy('id');
    }

    /**
     * El orden de las listas de precios en Excel: por rubro y, dentro de cada
     * uno, por código (A2 antes que A10). Necesita la relación categoria.
     *
     * @param  \Illuminate\Support\Collection<int, self>  $productos
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function ordenarPorRubroYCodigo(\Illuminate\Support\Collection $productos): \Illuminate\Support\Collection
    {
        return $productos
            ->sort(fn (self $a, self $b) => strcasecmp((string) $a->categoria?->nombre, (string) $b->categoria?->nombre)
                ?: strnatcasecmp((string) $a->codigo, (string) $b->codigo))
            ->values();
    }

    /**
     * El buscador del listado del admin (y el Excel que descarga lo mismo):
     * el texto en el código, nombre, descripción o categoría, con los códigos
     * que coinciden primero (exacto, empieza con, contiene).
     */
    public function scopeBusquedaAdmin($query, string $texto)
    {
        $texto = strtolower(trim($texto));
        if ($texto === '') {
            return $query;
        }
        $like = '%'.$texto.'%';

        return $query
            ->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(nombre) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(codigo) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(descripcion) LIKE ?', [$like])
                    ->orWhereHas('categoria', fn ($c) => $c->whereRaw('LOWER(nombre) LIKE ?', [$like]));
            })
            ->orderByRaw(
                'CASE WHEN LOWER(codigo) = ? THEN 0 WHEN LOWER(codigo) LIKE ? THEN 1 WHEN LOWER(codigo) LIKE ? THEN 2 ELSE 3 END',
                [$texto, $texto.'%', $like]
            );
    }

    /** Todo se asigna campo por campo en el admin: no hay asignación masiva. */
    protected $fillable = [];
    
    
    protected $table = 'productos';

    /** Ofertas de la zona pública (admin → Productos); independiente de `descuento`. */
    protected $casts = ['oferta' => 'boolean'];

    public function getNombreAttribute($value)
    {
        return ucfirst($value);
    }
    public function portada(){
        return Imagen::where('sector','producto')->where('tipo', 'portada')->where('producto_id', $this->id)->get()->first();
    }

    public function portadaImagen(): HasOne
    {
        return $this->hasOne(Imagen::class, 'producto_id')
            ->where('sector', 'producto')
            ->where('tipo', 'portada');
    }

    public function imagenesGaleria()
    {
        return $this->hasMany(Imagen::class, 'producto_id')
            ->where('sector', 'producto')
            ->orderByRaw("CASE WHEN tipo = 'portada' THEN 0 ELSE 1 END")
            ->orderBy('orden')
            ->orderBy('id');
    }

    /**
     * URLs validadas de la galería (portada + resto) listas para usar en la card.
     * Usa la relación ya cargada si existe para evitar N+1.
     * @return string[]
     */
    public function galeriaUrls(): array
    {
        $portadaUrl = $this->portadaUrl();
        if ($portadaUrl === self::imagenPredeterminadaUrl()) {
            return [$portadaUrl];
        }

        $imagenes = $this->relationLoaded('imagenesGaleria')
            ? $this->imagenesGaleria
            : $this->imagenesGaleria()->get();

        $urls = [$portadaUrl];
        foreach ($imagenes as $img) {
            if ($img->tipo === 'portada') continue;
            if (empty($img->path)) continue;
            $abs = public_path('imagenes/' . $img->path);
            if (is_file($abs)) {
                $urls[] = asset('imagenes/' . $img->path);
            }
        }
        $urls = array_values(array_unique($urls));
        return $urls;
    }

    /**
     * La imagen que muestra un producto sin portada: la que se cargó en
     * admin → Extras → Imagen por defecto, o si no hay, la de BMH.
     */
    public static function imagenPredeterminadaUrl(): string
    {
        return app(LogosSitio::class)->urlSiExiste(LogosSitio::PRODUCTO_DEFECTO)
            ?? self::imagenPredeterminadaBmhUrl();
    }

    /** El SVG con el logo del sitio que se usa cuando no se cargó una propia. */
    public static function imagenPredeterminadaBmhUrl(): string
    {
        return route('producto.placeholder');
    }

    /**
     * URL de la portada sólo si el archivo existe en disco. La base tiene
     * referencias a imágenes que no están en el filesystem (ver
     * docs/data-quality-report.md §6): devolvemos el placeholder en vez de un 404.
     */
    public function portadaUrl(): string
    {
        $path = $this->portadaImagen?->path;

        if ($path !== null && $path !== '' && is_file(public_path('imagenes/' . $path))) {
            return asset('imagenes/' . $path);
        }

        return self::imagenPredeterminadaUrl();
    }

    public function usos(){
        return $this->belongsToMany('App\Models\Uso');
    }

    public function medidas()
    {
        return $this->belongsToMany('App\Models\Medida');
    }


    public function repuestos()
    {
        return $this->belongsToMany('App\Models\Repuesto');
    }

    public function dimensiones(){
        return $this->hasMany('App\Models\Dimension');
    }

    /**
     * Todas las características con valor del producto (`caracteristica_producto.valor`), por el `orden` de
     * la característica. Incluye las que su categoría no declara: el admin las muestra para decidir qué
     * hacer con ellas. Se guardan con App\Actions\Caracteristicas\SincronizarCaracteristicasProducto.
     */
    public function caracteristicas(): BelongsToMany
    {
        return $this->belongsToMany(Caracteristica::class, 'caracteristica_producto')
            ->withPivot('valor')
            ->withTimestamps()
            ->ordenadas();
    }

    /**
     * Las que se muestran en el sitio: sólo las que la categoría del producto declara (admin → Categorías).
     * Un valor de una característica que la categoría no usa queda guardado pero no se ve, como antes con
     * las columnas: así un producto que cambió de categoría no muestra datos que no le corresponden.
     */
    public function caracteristicasVisibles(): BelongsToMany
    {
        return $this->caracteristicas()->whereExists(function ($query): void {
            $query->selectRaw('1')
                ->from('caracteristica_categoria as cc_visible')
                ->join('productos as p_visible', 'p_visible.categoria_id', '=', 'cc_visible.categoria_id')
                ->whereColumn('p_visible.id', 'caracteristica_producto.producto_id')
                ->whereColumn('cc_visible.caracteristica_id', 'caracteristica_producto.caracteristica_id');
        });
    }

    /**
     * Para las vistas: [nombre, valor] de las características visibles, en orden. Usa la relación ya
     * cargada si la hay (with('caracteristicasVisibles')).
     *
     * @return list<array{nombre: string, valor: string}>
     */
    public function fichaCaracteristicas(): array
    {
        $caracteristicas = $this->relationLoaded('caracteristicasVisibles')
            ? $this->caracteristicasVisibles
            : $this->caracteristicasVisibles()->get();

        return $caracteristicas
            ->filter(fn (Caracteristica $c) => trim((string) $c->pivot->valor) !== '')
            ->map(fn (Caracteristica $c) => ['nombre' => (string) $c->nombre, 'valor' => (string) $c->pivot->valor])
            ->values()
            ->all();
    }


    /**
     * Productos con un valor de esas características que cumple la condición. La condición recibe la
     * consulta sobre `caracteristica_producto as cpf` (el valor es `cpf.valor`).
     *
     * @param  int|list<int>  $caracteristicaIds
     * @param  \Closure(\Illuminate\Database\Query\Builder): mixed  $condicion
     */
    public function scopeConValorDe($query, int|array $caracteristicaIds, \Closure $condicion)
    {
        return $query->whereExists(function ($sub) use ($caracteristicaIds, $condicion): void {
            $sub->selectRaw('1')
                ->from('caracteristica_producto as cpf')
                ->whereColumn('cpf.producto_id', 'productos.id')
                ->whereIn('cpf.caracteristica_id', (array) $caracteristicaIds)
                ->where(fn ($w) => $condicion($w));
        });
    }

    public function categoria(){
        return $this->belongsTo('App\Models\Categoria');
    }

    public function subcategoria(){
        return $this->belongsTo('App\Models\Subcategoria');
    }


    public function precio()
    {
        $categoria = Categoria::find($this->categoria_id);  
        $basePrecio = $this->precio;
    
        // // Aplicar aumento de la categoría
        // if (!empty($categoria->aumento) && $categoria->aumento > 0) {
        //     $basePrecio *= (1 + ($categoria->aumento / 100));
        // } 
        // // Aplicar descuento de la categoría
        // elseif (!empty($categoria->descuento) && $categoria->descuento > 0) {
        //     $basePrecio *= (1 - ($categoria->descuento / 100));
        // }
    
        // // Aplicar aumento en el producto
        // if (!empty($this->aumento) && $this->aumento > 0) {
        //     $basePrecio *= (1 + ($this->aumento / 100));
        // }
        // // Aplicar descuento en el producto
        // elseif (!empty($this->descuento) && $this->descuento > 0) {
        //     $basePrecio *= (1 - ($this->descuento / 100));
        // }
    
        return $basePrecio;
    }
    
    
    public function precio_final()
    {
        $precioConPorcentaje = $this->precio();
    
        if ($this->descuento) {
            $precioConPorcentaje *= (1 - ($this->descuento / 100));
        }
    
        return $precioConPorcentaje;
    }
    

    public function precio_unitario_format(){
        return number_format($this->precio(), 2,',','.');
    }

    public function precio_unitario_descontado(){
        $precio = $this->precio() * (1 - (floatval($this->descuento) / 100));
        if(Auth::guard('web')->check()){
            $precio = $precio * (1 - (Auth::guard('web')->user()->descuento / 100));
        }
        return $precio;
    }

    public function precio_unitario_descontado_format(){


        if($this->descuento > 0){
            $precio_final = $this->precio() * (1 - ($this->descuento / 100));       
            
        }else{
            $precio_final = $this->precio();
        }


        
        if(Auth::guard('web')->user()->descuento > 0){
            
            $precio_final = $this->precio() * (1 - (Auth::guard('web')->user()->descuento / 100));       

        }
        

        return number_format($precio_final, 2, ',', '.');
    }


    public function precio_reventa(){

        $precio_neto = $this->precio() * (1 - (floatval($this->descuento) / 100));
        if(Auth::guard('web')->check()){
            $precio_neto = $precio_neto * (1 - (Auth::guard('web')->user()->descuento / 100));
        }


        $user = Auth::guard('web')->user();
        $margen = $user ? $user->margenReventaParaCategoria($this->categoria_id) : 0;
        $precio_reventa = $precio_neto * (1 + ($margen / 100));

      

        return number_format($precio_reventa, 2, ',', '.');
    }

    /**
     * Equivalencias del producto, ordenadas según el criterio elegido:
     * manual (campo orden) o alfabético (asc/desc) por nombre.
     */
    public function equivalencias()
    {
        $rel = $this->hasMany(Equivalencia::class);

        return match ($this->orden_equivalencias ?? self::ORDEN_MANUAL) {
            self::ORDEN_ALFA_ASC  => $rel->orderBy('nombre', 'asc'),
            self::ORDEN_ALFA_DESC => $rel->orderBy('nombre', 'desc'),
            default               => $rel->orderBy('orden', 'asc'),
        };
    }

    /**
     * Aplicaciones del producto, ordenadas igual que equivalencias.
     */
    public function aplicaciones()
    {
        $rel = $this->hasMany(Aplicacion::class);

        return match ($this->orden_aplicaciones ?? self::ORDEN_MANUAL) {
            self::ORDEN_ALFA_ASC  => $rel->orderBy('nombre', 'asc'),
            self::ORDEN_ALFA_DESC => $rel->orderBy('nombre', 'desc'),
            default               => $rel->orderBy('orden', 'asc'),
        };
    }

    /**
     * Partes relacionadas: otros productos del catálogo asociados a este.
     * El orden se define por el criterio elegido: manual (pivot `orden`) o
     * alfabético (asc/desc) por el nombre del producto relacionado.
     */
    public function partesRelacionadas(): BelongsToMany
    {
        $rel = $this->belongsToMany(
            Producto::class,
            'partes_relacionadas',
            'producto_id',
            'parte_id',
        )->withPivot('orden');

        return match ($this->orden_partes ?? self::ORDEN_MANUAL) {
            self::ORDEN_ALFA_ASC  => $rel->orderBy('productos.nombre', 'asc'),
            self::ORDEN_ALFA_DESC => $rel->orderBy('productos.nombre', 'desc'),
            default               => $rel->orderBy('partes_relacionadas.orden', 'asc'),
        };
    }
    
    public function precio_neto(){

        $precio_final = $this->precio() * (1 - ($this->descuento / 100));

        return number_format($precio_final, 2, ',', '.');
    }
    // public function precio_cliente(){
    //     $precio_final = $this->precio * (1 - ($this->descuento / 100));
    //     if(!$this->iva){ // Si no tiene iva incluido, incluir
    //         $precio_final = $precio_final * (1 + (Impuesto::find(1)->porcentaje / 100));
    //     }
    //     return $precio_final;
    // }

}
