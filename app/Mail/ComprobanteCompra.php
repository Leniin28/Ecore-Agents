<?php

namespace App\Mail;

use App\Models\Pago;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ComprobanteCompra extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Pago $pago) {}

    public function build(): static
    {
        return $this->subject('Comprobante de compra '.$this->pago->ordenEmpresarial->folio().' | ECore Agents')
            ->view('emails.comprobante-compra');
    }
}
