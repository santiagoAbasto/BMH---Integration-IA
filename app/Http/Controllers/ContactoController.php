<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contacto;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Mail\AndesMail;
use App\Mail\SolicitudPresupuesto;
use App\Models\Metadatos;

class ContactoController extends Controller
{
    public function index(Request $request){
        $contacto = Contacto::find(1);
        $ventana = 'contacto-nav';
        $producto = $request->input('producto');
        return view('frontend/contacto', compact('contacto', 'ventana', 'producto'));
    }

    public function update(Request $request){
        $contacto = Contacto::find(1);
        $contacto->direccion = $request->direccion == '' ? null : $request->direccion;
        $contacto->iframe = $request->iframe;
        $contacto->tel = $request->tel == '' ? null : $request->tel;
        $contacto->mail = $request->mail == '' ? null : $request->mail;;
        $contacto->whatsapp = $request->wpp == '' ? null : $request->wpp;
        $contacto->facebook = $request->fb == '' ? null : $request->fb;
        $contacto->instagram = $request->ig == '' ? null : $request->ig;
        $contacto->tiktok = $request->tiktok == '' ? null : $request->tiktok;

        $contacto->save();
        return redirect()->back()->with('success', 'Información de contacto actualizada');
    }

    public function mailContacto(Request $request){


        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => '6LfjNywqAAAAAON2XHxStkJuJ1bOPZ4MO39O60F7',
            'response' => $request->input('g-recaptcha-response')
        ])->object();

        if($response->success && $response->score >= 0.7){
            
            try {
                $contacto = Contacto::find(1);
                $mail_empresa = $contacto->mail;

                $email = new AndesMail($request);
                Mail::to($mail_empresa)->send($email);
                return redirect()->back()->with('success', '¡Solicitud enviada!');
            } catch (\Exception $e) {
                return redirect()->back()->with('warning', 'No se pudo enviar el formulario');
            }
            
        }  else {
            return redirect()->back()->with('warning', 'No se pudo enviar el formulario');
        }

    }

    public function mailSolicitud(Request $request){

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => '6LfjNywqAAAAAON2XHxStkJuJ1bOPZ4MO39O60F7',
            'response' => $request->input('g-recaptcha-response')
        ])->object();

        // if($response->success && $response->score >= 0.7){

            $contacto = Contacto::find(1);
            $mail_empresa = $contacto->mail;
            
            
            $email = new SolicitudPresupuesto($request);

            try { // ASI FUNCIONA EN EL SERVIDOR
                Mail::to($mail_empresa)->send($email);
                return redirect()->back()->with('success', '¡Solicitud enviada!');
            } catch (\Exception $e) {
                return redirect()->back()->with('warning', 'No se pudo enviar la solicitud');
            }
            
            
        // } else {
        //     return redirect()->back()->with('warning', 'No se pudo enviar la solicitud');
        // }
    }
}
