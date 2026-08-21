<?php

namespace App\Http\Controllers;
use App\Models\Newsletter;
use App\Mail\NewsletterMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Models\User;

class NewsletterController extends Controller
{

    public function newsletter(){
        $newsletter = User::where('rol', 'cliente')->get();
        return view('backend/dash-newsletter', compact('newsletter'));
    }

    public function crear(Request $request){
        $nuevo_mail = new Newsletter();
        $nuevo_mail->mail = $request->mail;

        $mails = Newsletter::all();
        $flag = false;
        foreach($mails as $item){
            if($item->mail == $request->mail){
                $flag = true;
                break;
            }
        }

        if($flag == false){
            $nuevo_mail->save();
        }
        

    }

    public function delete(Request $request){
        $mail = Newsletter::find($request->id);
        $mail->delete();
        return redirect()->back();
    }

    public function enviar(Request $request){
        
        $email = new NewsletterMail($request);
        // $mails = Newsletter::pluck('mail')->toArray();
        $clientes = User::where('rol', 'cliente')->pluck('email')->toArray(); 
        // $destinatarios = array_merge($mails, $clientes);
        
        
        try {
            Mail::bcc($clientes)->send($email);
            return redirect()->back()->with('success', 'Mail enviado');
        } catch (\Exception $e) {
            return redirect()->back()->with('warning', 'No se pudo enviar el mail');
        }
    }
}
