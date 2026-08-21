<?php

namespace App\Http\Controllers;

use App\Models\Anuncio;
use Illuminate\Http\Request;

class AnuncioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $anuncio = Anuncio::find(1);
        return view('backend/dash-anuncio', compact('anuncio'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Anuncio $anuncio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Anuncio $anuncio)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Anuncio $anuncio)
    {
        $anuncio = Anuncio::find(1);
        $anuncio->contenido = $request->contenido;
        $anuncio->save();
        $request->session()->put('modal_abierto', 0);
        return redirect()->back()->with('success', 'Anuncio actualizado');
    }

    public function mostrar(Request $request){
        $anuncio = Anuncio::find(1);
        $anuncio->mostrar = !$anuncio->mostrar;
        $request->session()->put('modal_abierto', 0);
        $anuncio->save();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Anuncio $anuncio)
    {
        //
    }
}
