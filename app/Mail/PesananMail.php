<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Email transaksional pesanan (dibuat, lunas, dikirim, dibatalkan). */
class PesananMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public $pesanan,
        public string $jenis,
        public string $tipe = 'produk',
    ) {}

    public function envelope(): Envelope
    {
        $judul = match ($this->jenis) {
            'dibuat' => 'Pesanan diterima',
            'lunas' => 'Pembayaran diterima',
            'dikirim' => 'Pesanan dikirim',
            'dibatalkan' => 'Pesanan dibatalkan',
            default => 'Informasi pesanan',
        };

        return new Envelope(
            subject: $judul.' - '.($this->pesanan->kode ?? 'Green Plant'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.pesanan',
            with: [
                'pesanan' => $this->pesanan,
                'jenis' => $this->jenis,
                'tipe' => $this->tipe,
            ],
        );
    }
}
