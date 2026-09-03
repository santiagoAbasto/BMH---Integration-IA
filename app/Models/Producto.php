<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Image;
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

    protected $fillable = [
        'columna_1', 'columna_2', 'columna_3', 'columna_4', 'columna_5', 'columna_6', 
        'columna_7', 'columna_8', 'columna_9', 'columna_10', 'columna_11', 'columna_12',
        'columna_13', 'columna_14', 'columna_15', 'columna_16', 'columna_17', 'columna_18', 
        'columna_19', 'columna_20', 'columna_21', 'columna_22', 'columna_23', 'columna_24',
        'columna_25', 'columna_26', 'columna_27', 'columna_28', 'columna_29', 'columna_30',
        'columna_31', 'columna_32', 'columna_33', 'columna_34', 'columna_35', 'columna_36', 
        'columna_37', 'columna_38', 'columna_39', 'columna_40', 'columna_41', 'columna_42',
        'columna_43', 'columna_44', 'columna_45', 'columna_46', 'columna_47', 'columna_48',
        'columna_49', 'columna_50', 'columna_51', 'columna_52', 'columna_53', 'columna_54',
        'columna_55', 'columna_56', 'columna_57', 'columna_58', 'columna_59', 'columna_60',
        'columna_61', 'columna_62', 'columna_63', 'columna_64', 'columna_65', 'columna_66',
        'columna_67', 'columna_68','columna_69', 'columna_70', 'columna_71', 'columna_72', 'columna_73', 'columna_74',
        'columna_75', 'columna_76', 'columna_77', 'columna_78'

    ];
    
    
    protected $table = 'productos';

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
        $imagenes = $this->relationLoaded('imagenesGaleria')
            ? $this->imagenesGaleria
            : $this->imagenesGaleria()->get();

        $urls = [];
        foreach ($imagenes as $img) {
            if (empty($img->path)) continue;
            $abs = public_path('imagenes/' . $img->path);
            if (is_file($abs)) {
                $urls[] = asset('imagenes/' . $img->path);
            }
        }
        $urls = array_values(array_unique($urls));
        if (empty($urls)) {
            $placeholder = asset('imagenes/WhatsApp-Image-2020-11-11-at-15.25.09.jpeg');
            // Incluir placeholder como única imagen si no hay válidas
            $urls = [$placeholder];
        }
        return $urls;
    }

    /**
     * URL de la portada sólo si el archivo existe en disco. La base tiene
     * referencias a imágenes que no están en el filesystem (ver
     * docs/data-quality-report.md §6): preferimos el placeholder a un 404.
     */
    public function portadaUrl(): ?string
    {
        $path = $this->portadaImagen?->path;

        if ($path !== null && $path !== '' && is_file(public_path('imagenes/' . $path))) {
            return asset('imagenes/' . $path);
        }

        return null;
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

    // public function caracteristicas(){
    //     return $this->belongsToMany('App\Models\Caracteristica');
    // }
    public function productCaracteristicas()
    {
        return $this->hasMany(ProductCaracteristica::class, 'producto_id')
            ->whereNull('producto_caracteristica.deleted_at')
            // En datos históricos puede haber más de una fila para el mismo
            // producto/característica. Exponer sólo la última evita duplicar
            // el valor mientras la migración corrige esas filas.
            ->whereIn('producto_caracteristica.id', function ($query) {
                $query->selectRaw('MAX(pc_latest.id)')
                    ->from('producto_caracteristica as pc_latest')
                    ->whereNull('pc_latest.deleted_at')
                    ->whereColumn('pc_latest.producto_id', 'producto_caracteristica.producto_id')
                    ->whereColumn('pc_latest.caracteristica_id', 'producto_caracteristica.caracteristica_id')
                    ->groupBy('pc_latest.producto_id', 'pc_latest.caracteristica_id');
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
