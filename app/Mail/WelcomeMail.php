<?php

namespace App\Mail;

use App\Models\Assinatura;
use App\Models\Empresa;
use App\Models\Plano;
use App\Models\Usuario;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Usuario $usuario;
    public Empresa $empresa;
    public Plano $plano;
    public Assinatura $assinatura;

    public function __construct(Usuario $usuario, Empresa $empresa, Plano $plano, Assinatura $assinatura)
    {
        $this->usuario = $usuario;
        $this->empresa = $empresa;
        $this->plano = $plano;
        $this->assinatura = $assinatura;
    }

    public function build()
    {
        return $this->subject('Bem-vindo ao ClickResto')
            ->view('emails.welcome');
    }
}
