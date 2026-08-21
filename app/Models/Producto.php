<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Image;
use Illuminate\Support\Facades\Auth;
use App\Models\Impuesto;

class Producto extends Model
{
    use HasFactory;

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
    public function getCategoriaAttribute($value)
    {
        return ucfirst($value);
    }

    public function portada(){
        return Imagen::where('sector','producto')->where('tipo', 'portada')->where('producto_id', $this->id)->get()->first();
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
        return $this->hasMany(ProductCaracteristica::class, 'producto_id');
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


        $precio_reventa = $precio_neto * (1 + (Auth::guard('web')->user()->reventa / 100));

      

        return number_format($precio_reventa, 2, ',', '.');
    }

    public function equivalencias()
    {
        return $this->hasMany(Equivalencia::class);
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
