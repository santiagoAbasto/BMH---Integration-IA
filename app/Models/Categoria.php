<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Subcategoria;

class Categoria extends Model
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


    protected $table = 'categorias';

    // public function caracteristicas(){
    //     return $this->belongsToMany('App\Models\Caracteristica', 'caracteristica_categoria');
    // }

    public function caracteristicas()
{
    return $this->belongsToMany(Caracteristica::class, 'categoria_caracteristica');
}



    public function subcategorias(){
        return Subcategoria::where('categoria_id', $this->id)->get();
    }

    public function productos(){
        return $this->hasMany('App\Models\Producto');
    }

}
