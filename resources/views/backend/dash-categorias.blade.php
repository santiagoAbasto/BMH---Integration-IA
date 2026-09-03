@extends('layouts.plantilla-back')

@section('content')
    <h1 class='mb-4'>Categorías</h1>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-danger">
            {{ session('warning') }}
        </div>
    @endif

    <div class="flex">
        {{-- 1. En el header de la tarjeta, junto al botón CREAR categoría --}}
        <div class="card-header d-flex align-items-center gap-2">
            <button data-bs-toggle="modal" data-bs-target="#crear" type="button" class="btn btn-success">
                <i class="fa-solid fa-plus"></i> CREAR CATEGORÍA
            </button>

            <!-- Nuevo botón para crear equivalencia -->
            {{-- <button data-bs-toggle="modal" data-bs-target="#crearEquivalencia" type="button" class="btn btn-warning">
                <i class="fa-solid fa-flask"></i> CREAR EQUIVALENCIA
            </button> --}}
        </div>

        <div class="row">
            <div class="col-lg-6">
                <form action="{{ route('categoria.actualizarPorcentaje') }}" method="POST" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="categoria_select" class="form-label">Selecciona Categoría</label>
                        <select name="categoria_id" id="categoria_select" class="form-control" required>
                            <option value="todos">Todos</option>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de ajuste</label>
                        <select name="tipo" id="tipo" class="form-control" required>
                            <option value="aumentar">Aumentar</option>
                            <option value="descontar">Descontar</option>
                            <option value="limpiar">Limpiar</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Estado</label>
                        <select name="estado" id="estado" class="form-control">
                            <option value="0">General</option>
                            <option value="1">Nuevo</option>
                            <option value="2">Reconstruido</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="porcentaje" class="form-label">Porcentaje</label>
                        <input type="number" name="porcentaje" id="porcentaje" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </form>
            </div>
            <div class="col-lg-6">

                <form action="{{ route('producto.actualizarPorcentaje') }}" method="POST" class="mb-4">
                    @csrf
                    <div class="mb-3">
                        <label for="producto_select" class="form-label">Buscar Producto</label>
                        <select name="producto_id" id="producto_select" class="form-control" required>
                            <option value="">Escribe el código del producto...</option>
                            @foreach ($productos as $producto)
                                <option value="{{ $producto->id }}">{{ $producto->codigo }} - {{ $producto->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de ajuste</label>
                        <select name="tipo" id="tipo" class="form-control" required>
                            <option value="aumentar">Aumentar</option>
                            <option value="descontar">Descontar</option>
                        </select>
                    </div>



                    <div class="mb-3">
                        <label for="porcentaje" class="form-label">Porcentaje</label>
                        <input type="number" name="porcentaje" id="porcentaje" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Actualizar</button>
                </form>
            </div>

        </div>

    </div>
    
    
    








    <div class="card">
        <div class="card-header">
            {{-- <button data-bs-toggle="modal" data-bs-target="#crear" type="button" class="btn btn-success"><i
                    class="fa-solid fa-plus"></i> CREAR</button> --}}

            {{-- MODAL CREAR --}}
            <div class="modal fade" id="crear" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            Crear categoría
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form class='pb-3' id='formulario-crear' action="{{ route('categoria.store') }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class='row'>
                                    <div class="col-2 mb-3">
                                        <label for="orden" class="form-label">Orden</label>
                                        <input type="text" class="form-control" name='orden' value='aa'
                                            required>
                                    </div>
                                    <div class="col-10 mb-3">
                                        <label for="nombre" class="form-label">Nombre</label>
                                        <input type="text" class="form-control" name='nombre' required>
                                    </div>

                                    <h3>Seleccionar atributos</h3>
                                    <div class="checkbox-group">
                                        <label><input type="checkbox" name="columna_1" value="VOLTAJE"> VOLTAJE</label>
                                        <label><input type="checkbox" name="columna_2" value="DIAMETRO"> DIAMETRO</label>
                                        <label><input type="checkbox" name="columna_3" value="ESCOBILLAS">
                                            ESCOBILLAS</label>
                                        <label><input type="checkbox" name="columna_4" value="MASA POLAR"> MASA
                                            POLAR</label>
                                        <label><input type="checkbox" name="columna_5" value="GIRO"> GIRO</label>

                                        <label><input type="checkbox" name="columna_6" value="TIPO">TIPO</label>
                                        <label><input type="checkbox" name="columna_7" value="CIRCUITO TIPO">CIRCUITO
                                            TIPO</label>

                                        <label><input type="checkbox" name="columna_8" value="LARGO"> LARGO</label>
                                        <label><input type="checkbox" name="columna_9" value="ANCHO"> ANCHO</label>
                                        <label><input type="checkbox" name="columna_10" value="AMPERES"> AMPERES</label>
                                        <label><input type="checkbox" name="columna_11" value="TERMINACION">
                                            TERMINACION</label>

                                        <label><input type="checkbox" name="columna_12" value="ARO COLECTOR">ARO
                                            COLECTOR</label>

                                        <label><input type="checkbox" name="columna_13" value="DIENTES"> DIENTES</label>
                                        <label><input type="checkbox" name="columna_14" value="ESTRIAS"> ESTRIAS</label>
                                        <label><input type="checkbox" name="columna_15" value="RANURAS"> RANURAS</label>
                                        <label><input type="checkbox" name="columna_16" value="BUJE LADO COLECTOR"> BUJE
                                            LADO COLECTOR</label>
                                        <label><input type="checkbox" name="columna_17" value="BUJE LADO BENDIX"> BUJE
                                            LADO
                                            BENDIX</label>
                                        <label><input type="checkbox" name="columna_18" value="DIAMETRO FIJACION">
                                            DIAMETRO
                                            FIJACION</label>
                                        <label><input type="checkbox" name="columna_21" value="RODAMIENTO COLECTOR">
                                            RODAMIENTO COLECTOR</label>
                                        <label><input type="checkbox" name="columna_22" value="RODAMIENTO POLEA">
                                            RODAMIENTO POLEA</label>

                                        <label><input type="checkbox" name="columna_24"
                                                value="DISTANCIA ENTRE ORIFICIOS DE MONTAJE">DISTANCIA ENTRE ORIFICIOS DE
                                            MONTAJE</label>

                                        <label><input type="checkbox" name="columna_25"
                                                value="TERMINALES">TERMINALES</label>

                                        <label><input type="checkbox" name="columna_26" value="ROSCAS"> ROSCAS</label>
                                        <label><input type="checkbox" name="columna_27" value="APLICACIÓN">
                                            APLICACIÓN</label>
                                        <label><input type="checkbox" name="columna_28" value="ALTURA"> ALTURA</label>
                                        <label><input type="checkbox" name="columna_29" value="TIPO SERIE"> TIPO
                                            SERIE</label>
                                        <label><input type="checkbox" name="columna_30" value="CIRCUITO">
                                            CIRCUITO</label>
                                        <label><input type="checkbox" name="columna_31" value="DISTANCIA">
                                            DISTANCIA</label>
                                        <label><input type="checkbox" name="columna_32" value="PINES"> PINES</label>
                                        <label><input type="checkbox" name="columna_33" value="TOLERANCIA">
                                            TOLERANCIA</label>
                                        <label><input type="checkbox" name="columna_34" value="BLINDAJE">
                                            BLINDAJE</label>
                                        <label><input type="checkbox" name="columna_35" value="DIAMETRO INTERNO">
                                            DIAMETRO INTERNO</label>
                                        <label><input type="checkbox" name="columna_36" value="DIAMETRO EXTERNO">
                                            DIAMETRO EXTERNO</label>
                                        <label><input type="checkbox" name="columna_37" value="TERMINACION">
                                            TERMINACION</label>
                                        <label><input type="checkbox" name="columna_38" value="PESO"> PESO</label>
                                        <label><input type="checkbox" name="columna_39" value="TERMINALES">
                                            TERMINALES</label>
                                        <label><input type="checkbox" name="columna_40" value="CANTIDAD">
                                            CANTIDAD</label>
                                        <label><input type="checkbox" name="columna_41" value="FUNCION"> FUNCION</label>
                                        <label><input type="checkbox" name="columna_42" value="EQUIVALENCIA UNIPOINT">
                                            EQUIVALENCIA UNIPOINT</label>
                                        <label><input type="checkbox" name="columna_43" value="EQUIVALENCIA TAMATEL">
                                            EQUIVALENCIA TAMATEL</label>
                                        <label><input type="checkbox" name="columna_44" value="EQUIVALENCIA NOSSO">
                                            EQUIVALENCIA NOSSO</label>
                                        <label><input type="checkbox" name="columna_45" value="FICHA"> FICHA</label>
                                        <label><input type="checkbox" name="columna_46" value="ANCHO DE BANDA"> ANCHO DE
                                            BANDA</label>
                                        <label><input type="checkbox" name="columna_47" value="DIAMETRO VENTILADOR">
                                            DIAMETRO VENTILADOR</label>
                                        <label><input type="checkbox" name="columna_48" value="LARGO VENTILADOR"> LARGO
                                            VENTILADOR</label>
                                        <label><input type="checkbox" name="columna_49" value="LARGO TOTAL"> LARGO
                                            TOTAL</label>
                                        <label><input type="checkbox" name="columna_50" value="MARCA"> MARCA</label>
                                        <label><input type="checkbox" name="columna_51" value="EQUIVALENCIA">
                                            EQUIVALENCIA</label>
                                        <label><input type="checkbox" name="columna_52" value="CODIGO PH"> CODIGO
                                            PH</label>
                                        <label><input type="checkbox" name="columna_53" value="CANTIDAD DE DIODOS">
                                            CANTIDAD DE DIODOS</label>
                                        <label><input type="checkbox" name="columna_54" value="TIPO DE DIODOS"> TIPO DE
                                            DIODOS</label>
                                        <label><input type="checkbox" name="columna_55" value="AMPERAJE DE DIODOS">
                                            AMPERAJE DE DIODOS</label>
                                        <label><input type="checkbox" name="columna_56" value="TERMINALES CANTIDAD">
                                            TERMINALES CANTIDAD</label>
                                        <label><input type="checkbox" name="columna_57" value="TERMINALES DESCRIPCION">
                                            TERMINALES DESCRIPCION</label>
                                        <label><input type="checkbox" name="columna_58" value="DIODO ADICIONAL"> DIODO
                                            ADICIONAL</label>
                                        <label><input type="checkbox" name="columna_59" value="DIAMETRO EXTERNO PIÑON">
                                            DIAMETRO EXTERNO PIÑON</label>
                                        <label><input type="checkbox" name="columna_60" value="CODIGO ZEN"> CODIGO
                                            ZEN</label>
                                        <label><input type="checkbox" name="columna_61" value="CODIGO GV"> CODIGO
                                            GV</label>
                                        <label><input type="checkbox" name="columna_62" value="CODIGO PH"> CODIGO
                                            PH</label>
                                        <label><input type="checkbox" name="columna_63" value="CODIGO DIPRA"> CODIGO
                                            DIPRA</label>
                                        <label><input type="checkbox" name="columna_64" value="MEDIDAS"> MEDIDAS</label>
                                        <label><input type="checkbox" name="columna_65" value="RODAMIENTO LADO COLECTOR">
                                            RODAMIENTO LADO COLECTOR</label>
                                        <label><input type="checkbox" name="columna_66" value="RODAMIENTO LADO POLEA">
                                            RODAMIENTO LADO POLEA</label>
                                        <label><input type="checkbox" name="columna_67" value="CODIGO ZM"> CODIGO
                                            ZM</label>

                                        <label><input type="checkbox" name="columna_68"
                                                value="DIAMETRO DE ORIFICIOS DE MONTAJE">DIAMETRO DE ORIFICIOS DE
                                            MONTAJE</label>
                                        <label><input type="checkbox" name="columna_69" value="CARBON">CARBON</label>
                                        <label><input type="checkbox" name="columna_70"
                                                value="EQUIVALENCIA">EQUIVALENCIA</label>
                                        <label><input type="checkbox" name="columna_71" value="PARS">PARS</label>
                                        <label><input type="checkbox" name="columna_72"
                                                value="EQUIVALENCIA BMH">EQUIVALENCIA BMH</label>
                                        <label><input type="checkbox" name="columna_73"
                                                value="EQUIVALENCIA NF">EQUIVALENCIA NF</label>
                                        <label><input type="checkbox" name="columna_74" value="SALIDAS">SALIDAS</label>
                                        <label><input type="checkbox" name="columna_75"
                                                value="EQUIVALENCIA INDUCIDO NUEVO"> EQUIVALENCIA INDUCIDO NUEVO</label>
                                        <label><input type="checkbox" name="columna_76"
                                                value="EQUIVALENCIA ESTATOR NUEVO"> EQUIVALENCIA ESTATOR NUEVO</label>
                                        <label><input type="checkbox" name="columna_77" value="EQUIVALENCIA ROTOR NUEVO">
                                            EQUIVALENCIA ROTOR NUEVO</label>
                                        <label><input type="checkbox" name="columna_78"
                                                value="EQUIVALENCIA SOLENOIDE NUEVO"> EQUIVALENCIA SOLENOIDE NUEVO</label>


                                                <div class="row">
                                                    @foreach ($caracteristicas as $caracteristica)
                                                        <div class="col-md-4">
                                                            <label>
                                                                <input type="checkbox" name="caracteristicas[]" value="{{ $caracteristica->id }}">
                                                                {{ $caracteristica->nombre }}
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                
                                    </div>



                                    <div class="mb-3">
                                        <label for="portada" class="form-label">Imagen <span
                                                class='recomendada'>(recomendada 260x260 px)</span></label>
                                        <input class="form-control preview" type="file" id="imagen" name='portada'
                                            accept="image/*" required>
                                    </div>

                                </div>

                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button data-form-id="formulario-crear" type="submit"
                                class="btn btn-primary submit">Agregar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">

            <table class="table table-striped" style='border: 1px solid #dddddd;'>
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Imagen</th>
                        <th>Nombre</th>
                        {{-- <th>Descuento</th> --}}
                        <th>Destacada</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>

                    @foreach ($categorias as $categoria)
                        <tr>
                            <td>{{ $categoria->orden }}</td>
                            <td><img src="{{ asset('imagenes/' . $categoria->portada) }}" class="img-thumbnail"
                                    style="max-width: 100px; height:70px;"></td>
                            <td>{{ ucfirst($categoria->nombre) }}</td>
                            {{-- <td>{{$categoria->descuento.'%'}}</td> --}}
                            <td><input class='categoriaCheckbox form-check-input' style='cursor: pointer;'
                                    type="checkbox" data-id="{{ $categoria->id }}" id="categoria{{ $categoria->id }}"
                                    name="destacada" value="1" {{ $categoria->destacada ? 'checked' : '' }}></td>
                            <td>
                                <div class='d-flex'>
                                    <button type="button" class="btn btn-primary btn-sm me-1" data-bs-toggle="modal"
                                        data-bs-target="{{ '#editar' . $categoria->id }}"><i
                                            class="fa-regular fa-pen-to-square"></i></button>
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#{{ 'eliminar' . $categoria->id }}"><i
                                            class="fa-solid fa-trash-can"></i></button>
                                </div>

                            </td>
                        </tr>
                    @endforeach

                </tbody>
            </table>

        </div>
    </div>

    @foreach ($categorias as $categoria)
        {{-- MODAL EDITAR --}}
        <div class="modal fade" id="editar{{ $categoria->id }}" tabindex="-1" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        Editar categoria
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id='agregar{{ $categoria->id }}' class='mb-4 loading'
                            action="{{ route('categoria.update', ['id' => $categoria->id]) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('put')

                            <div class="row g-3 align-items-center">
                                <div class="col-2 ">
                                    <label for="orden" class="form-label">Orden</label>
                                    <input type="text" class="form-control" name='orden'
                                        value='{{ $categoria->orden }}' required>
                                </div>
                                <div class="col-10">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" name='nombre'
                                        value='{{ $categoria->nombre }}' required>
                                </div>
                                {{-- <div class="col-3">
                <label for="descuento" class="form-label">Descuento (%)</label>
                <input type="number" class="form-control" name='descuento' value={{$categoria->descuento}} required>
              </div> --}}

                                <div class="col-lg-12">

                                    <div class="checkbox-group">
                                        <label><input type="checkbox" name="columna_1" value="VOLTAJE"
                                                @if ($categoria->columna_1) checked @endif> VOLTAJE</label>
                                        <label><input type="checkbox" name="columna_2" value="DIAMETRO"
                                                @if ($categoria->columna_2) checked @endif> DIAMETRO</label>
                                        <label><input type="checkbox" name="columna_3" value="ESCOBILLAS"
                                                @if ($categoria->columna_3) checked @endif> ESCOBILLAS</label>
                                        <label><input type="checkbox" name="columna_4" value="MASA POLAR"
                                                @if ($categoria->columna_4) checked @endif> MASA POLAR</label>
                                        <label><input type="checkbox" name="columna_5" value="GIRO"
                                                @if ($categoria->columna_5) checked @endif> GIRO</label>

                                        <label><input type="checkbox" name="columna_6" value="TIPO"
                                                @if ($categoria->columna_6) checked @endif>TIPO</label>
                                        <label><input type="checkbox" name="columna_7" value="CIRCUITO TIPO"
                                                @if ($categoria->columna_7) checked @endif>CIRCUITO TIPO </label>

                                        <label><input type="checkbox" name="columna_8" value="LARGO"
                                                @if ($categoria->columna_8) checked @endif> LARGO</label>
                                        <label><input type="checkbox" name="columna_9" value="ANCHO"
                                                @if ($categoria->columna_9) checked @endif> ANCHO</label>
                                        <label><input type="checkbox" name="columna_10" value="AMPERES"
                                                @if ($categoria->columna_10) checked @endif> AMPERES</label>
                                        <label><input type="checkbox" name="columna_11" value="TERMINACION"
                                                @if ($categoria->columna_11) checked @endif> TERMINACION</label>

                                        <label><input type="checkbox" name="columna_12" value="ARO COLECTOR"
                                                @if ($categoria->columna_12) checked @endif>ARO COLECTOR</label>

                                        <label><input type="checkbox" name="columna_13" value="DIENTES"
                                                @if ($categoria->columna_13) checked @endif> DIENTES</label>
                                        <label><input type="checkbox" name="columna_14" value="ESTRIAS"
                                                @if ($categoria->columna_14) checked @endif> ESTRIAS</label>
                                        <label><input type="checkbox" name="columna_15" value="RANURAS"
                                                @if ($categoria->columna_15) checked @endif> RANURAS</label>
                                        <label><input type="checkbox" name="columna_16" value="BUJE LADO COLECTOR"
                                                @if ($categoria->columna_16) checked @endif> BUJE LADO
                                            COLECTOR</label>
                                        <label><input type="checkbox" name="columna_17" value="BUJE LADO BENDIX"
                                                @if ($categoria->columna_17) checked @endif> BUJE LADO BENDIX</label>
                                        <label><input type="checkbox" name="columna_18" value="DIAMETRO FIJACION"
                                                @if ($categoria->columna_18) checked @endif> DIAMETRO
                                            FIJACION</label>
                                        <label><input type="checkbox" name="columna_21" value="RODAMIENTO COLECTOR"
                                                @if ($categoria->columna_21) checked @endif> RODAMIENTO
                                            COLECTOR</label>
                                        <label><input type="checkbox" name="columna_22" value="RODAMIENTO POLEA"
                                                @if ($categoria->columna_22) checked @endif> RODAMIENTO POLEA</label>

                                        <label><input type="checkbox" name="columna_24"
                                                value="DISTANCIA ENTRE ORIFICIOS DE MONTAJE"
                                                @if ($categoria->columna_24) checked @endif>DISTANCIA ENTRE ORIFICIOS
                                            DE MONTAJE</label>
                                        <label><input type="checkbox" name="columna_25" value="TERMINALES"
                                                @if ($categoria->columna_25) checked @endif>TERMINALES</label>

                                        <label><input type="checkbox" name="columna_26" value="ROSCAS"
                                                @if ($categoria->columna_26) checked @endif> ROSCAS</label>
                                        <label><input type="checkbox" name="columna_27" value="APLICACIÓN"
                                                @if ($categoria->columna_27) checked @endif> APLICACIÓN</label>
                                        <label><input type="checkbox" name="columna_28" value="ALTURA"
                                                @if ($categoria->columna_28) checked @endif> ALTURA</label>
                                        <label><input type="checkbox" name="columna_29" value="TIPO SERIE"
                                                @if ($categoria->columna_29) checked @endif> TIPO SERIE</label>
                                        <label><input type="checkbox" name="columna_30" value="CIRCUITO"
                                                @if ($categoria->columna_30) checked @endif> CIRCUITO</label>
                                        <label><input type="checkbox" name="columna_31" value="DISTANCIA"
                                                @if ($categoria->columna_31) checked @endif> DISTANCIA</label>
                                        <label><input type="checkbox" name="columna_32" value="PINES"
                                                @if ($categoria->columna_32) checked @endif> PINES</label>
                                        <label><input type="checkbox" name="columna_33" value="TOLERANCIA"
                                                @if ($categoria->columna_33) checked @endif> TOLERANCIA</label>
                                        <label><input type="checkbox" name="columna_34" value="BLINDAJE"
                                                @if ($categoria->columna_34) checked @endif> BLINDAJE</label>
                                        <label><input type="checkbox" name="columna_35" value="DIAMETRO INTERNO"
                                                @if ($categoria->columna_35) checked @endif> DIAMETRO INTERNO</label>
                                        <label><input type="checkbox" name="columna_36" value="DIAMETRO EXTERNO"
                                                @if ($categoria->columna_36) checked @endif> DIAMETRO EXTERNO</label>
                                        <label><input type="checkbox" name="columna_37" value="TERMINACION"
                                                @if ($categoria->columna_37) checked @endif> TERMINACION</label>
                                        <label><input type="checkbox" name="columna_38" value="PESO"
                                                @if ($categoria->columna_38) checked @endif> PESO</label>
                                        <label><input type="checkbox" name="columna_39" value="TERMINALES"
                                                @if ($categoria->columna_39) checked @endif> TERMINALES</label>
                                        <label><input type="checkbox" name="columna_40" value="CANTIDAD"
                                                @if ($categoria->columna_40) checked @endif> CANTIDAD</label>
                                        <label><input type="checkbox" name="columna_41" value="FUNCION"
                                                @if ($categoria->columna_41) checked @endif> FUNCION</label>
                                        <label><input type="checkbox" name="columna_42" value="EQUIVALENCIA UNIPOINT"
                                                @if ($categoria->columna_42) checked @endif> EQUIVALENCIA
                                            UNIPOINT</label>
                                        <label><input type="checkbox" name="columna_43" value="EQUIVALENCIA TAMATEL"
                                                @if ($categoria->columna_43) checked @endif> EQUIVALENCIA
                                            TAMATEL</label>
                                        <label><input type="checkbox" name="columna_44" value="EQUIVALENCIA NOSSO"
                                                @if ($categoria->columna_44) checked @endif> EQUIVALENCIA
                                            NOSSO</label>
                                        <label><input type="checkbox" name="columna_45" value="FICHA"
                                                @if ($categoria->columna_45) checked @endif> FICHA</label>
                                        <label><input type="checkbox" name="columna_46" value="ANCHO DE BANDA"
                                                @if ($categoria->columna_46) checked @endif> ANCHO DE BANDA</label>
                                        <label><input type="checkbox" name="columna_47" value="DIAMETRO VENTILADOR"
                                                @if ($categoria->columna_47) checked @endif> DIAMETRO
                                            VENTILADOR</label>
                                        <label><input type="checkbox" name="columna_48" value="LARGO VENTILADOR"
                                                @if ($categoria->columna_48) checked @endif> LARGO VENTILADOR</label>
                                        <label><input type="checkbox" name="columna_49" value="LARGO TOTAL"
                                                @if ($categoria->columna_49) checked @endif> LARGO TOTAL</label>
                                        <label><input type="checkbox" name="columna_50" value="MARCA"
                                                @if ($categoria->columna_50) checked @endif> MARCA</label>
                                        <label><input type="checkbox" name="columna_51" value="EQUIVALENCIA"
                                                @if ($categoria->columna_51) checked @endif> EQUIVALENCIA</label>
                                        <label><input type="checkbox" name="columna_52" value="CODIGO PH"
                                                @if ($categoria->columna_52) checked @endif> CODIGO PH</label>
                                        <label><input type="checkbox" name="columna_53" value="CANTIDAD DE DIODOS"
                                                @if ($categoria->columna_53) checked @endif> CANTIDAD DE
                                            DIODOS</label>
                                        <label><input type="checkbox" name="columna_54" value="TIPO DE DIODOS"
                                                @if ($categoria->columna_54) checked @endif> TIPO DE DIODOS</label>
                                        <label><input type="checkbox" name="columna_55" value="AMPERAJE DE DIODOS"
                                                @if ($categoria->columna_55) checked @endif> AMPERAJE DE
                                            DIODOS</label>
                                        <label><input type="checkbox" name="columna_56" value="TERMINALES CANTIDAD"
                                                @if ($categoria->columna_56) checked @endif> TERMINALES
                                            CANTIDAD</label>
                                        <label><input type="checkbox" name="columna_57" value="TERMINALES DESCRIPCION"
                                                @if ($categoria->columna_57) checked @endif> TERMINALES
                                            DESCRIPCION</label>
                                        <label><input type="checkbox" name="columna_58" value="DIODO ADICIONAL"
                                                @if ($categoria->columna_58) checked @endif> DIODO ADICIONAL</label>
                                        <label><input type="checkbox" name="columna_59" value="DIAMETRO EXTERNO PIÑON"
                                                @if ($categoria->columna_59) checked @endif> DIAMETRO EXTERNO
                                            PIÑON</label>
                                        <label><input type="checkbox" name="columna_60" value="CODIGO ZEN"
                                                @if ($categoria->columna_60) checked @endif> CODIGO ZEN</label>
                                        <label><input type="checkbox" name="columna_61" value="CODIGO GV"
                                                @if ($categoria->columna_61) checked @endif> CODIGO GV</label>
                                        <label><input type="checkbox" name="columna_62" value="CODIGO PH"
                                                @if ($categoria->columna_62) checked @endif> CODIGO PH</label>
                                        <label><input type="checkbox" name="columna_63" value="CODIGO DIPRA"
                                                @if ($categoria->columna_63) checked @endif> CODIGO DIPRA</label>
                                        <label><input type="checkbox" name="columna_64" value="MEDIDAS"
                                                @if ($categoria->columna_64) checked @endif> MEDIDAS</label>
                                        <label><input type="checkbox" name="columna_65" value="RODAMIENTO LADO COLECTOR"
                                                @if ($categoria->columna_65) checked @endif> RODAMIENTO LADO
                                            COLECTOR</label>
                                        <label><input type="checkbox" name="columna_66" value="RODAMIENTO LADO POLEA"
                                                @if ($categoria->columna_66) checked @endif> RODAMIENTO LADO
                                            POLEA</label>
                                        <label><input type="checkbox" name="columna_67" value="CODIGO ZM"
                                                @if ($categoria->columna_67) checked @endif> CODIGO ZM</label>

                                        <label><input type="checkbox" name="columna_68"
                                                value="DIAMETRO DE ORIFICIOS DE MONTAJE"
                                                @if ($categoria->columna_68) checked @endif> DIAMETRO DE ORIFICIOS DE
                                            MONTAJE</label>

                                        <label><input type="checkbox" name="columna_69" value="CARBON"
                                                @if ($categoria->columna_69) checked @endif> CARBON</label>

                                        <label><input type="checkbox" name="columna_70" value="EQUIVALENCIA"
                                                @if ($categoria->columna_70) checked @endif> EQUIVALENCIA</label>

                                        <label><input type="checkbox" name="columna_71" value="PARS"
                                                @if ($categoria->columna_71) checked @endif> PARS</label>

                                        <label><input type="checkbox" name="columna_72" value="EQUIVALENCIA BMH"
                                                @if ($categoria->columna_72) checked @endif> EQUIVALENCIA BMH</label>

                                        <label><input type="checkbox" name="columna_73" value="EQUIVALENCIA NF"
                                                @if ($categoria->columna_73) checked @endif> EQUIVALENCIA NF</label>

                                        <label><input type="checkbox" name="columna_74" value="SALIDAS"
                                                @if ($categoria->columna_74) checked @endif>SALIDAS</label>
                                        <label><input type="checkbox" name="columna_75"
                                                value="EQUIVALENCIA INDUCIDO NUEVO"
                                                @if ($categoria->columna_75) checked @endif> EQUIVALENCIA INDUCIDO
                                            NUEVO</label>
                                        <label><input type="checkbox" name="columna_76"
                                                value="EQUIVALENCIA ESTATOR NUEVO"
                                                @if ($categoria->columna_76) checked @endif> EQUIVALENCIA ESTATOR
                                            NUEVO</label>
                                        <label><input type="checkbox" name="columna_77" value="EQUIVALENCIA ROTOR NUEVO"
                                                @if ($categoria->columna_77) checked @endif> EQUIVALENCIA ROTOR
                                            NUEVO</label>
                                        <label>


                                            <input type="checkbox" name="columna_78" value="EQUIVALENCIA SOLENOIDE NUEVO"
                                            @if ($categoria->columna_78) checked @endif> EQUIVALENCIA SOLENOIDE NUEVO</label>


                                            <div class="row">
                                                @foreach($caracteristicas as $caracteristica)
                                                    <div class="col-md-4">
                                                        <label>
                                                            <input
                                                                type="checkbox"
                                                                name="caracteristicas[]"
                                                                value="{{ $caracteristica->id }}"
                                                                @if($categoria->caracteristicas->contains($caracteristica->id)) checked @endif
                                                            >
                                                            {{ $caracteristica->nombre }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            

                                            


                                    </div>
                                </div>
                                <div class="my-3">
                                    <label for="portada" class="form-label">Imagen <span
                                            class='recomendada'>(recomendada 260x260 px)</span></label>
                                    <input class="form-control preview" data-form-id="{{ 'imagen' . $categoria->id }}"
                                        type="file" id="imagen" name='portada' accept="image/*">
                                </div>
                                <div class='d-flex justify-content-center' style='max-height:50vh;'>
                                    <img id="{{ 'imagen' . $categoria->id }}"
                                        src="{{ asset('imagenes/' . $categoria->portada) }}"
                                        alt="Vista previa de la imagen" style="max-width: 100%; object-fit: contain;">
                                </div>


                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button data-form-id="agregar{{ $categoria->id }}" type="submit"
                            class="btn btn-primary submit">Actualizar</button>
                    </div>
                </div>
            </div>
        </div>
       

        {{-- MODAL ELIMINAR --}}
        <div class="modal fade" id="{{ 'eliminar' . $categoria->id }}" tabindex="-1"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body fs-4 d-flex justify-content-center" style='text-align:center;'>
                        ¿Desea eliminar el categoria {{ ucfirst($categoria->nombre) }}?<br>
                    </div>
                    <div class="modal-footer d-flex justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="{{ route('categoria.delete', ['id' => $categoria->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#{{ 'eliminar' . $categoria->id }}">Eliminar</button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@section('script')
    <script>
        // CARACTERÍSTICAS
        $(document).ready(function() {
            $('.categoriaCheckbox').click(function() {

                var productoID = $(this).data('id');
                var estadoDestacada = $(this).is(':checked');
                $.ajax({
                    url: "{{ route('categoria.destacada') }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        producto_id: productoID,
                        destacada: estadoDestacada
                    },
                    success: function(response) {
                        // Manejo de respuesta exitosa
                        console.log(response.mensaje);
                    },
                    error: function(xhr) {
                        // Manejo de error
                        console.error(xhr.responseText);
                    }
                });
            });

        });
    </script>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('#producto_select').select2({
                placeholder: "Escribe el código del producto...",
                allowClear: true
            });
        });
    </script>
@endpush
