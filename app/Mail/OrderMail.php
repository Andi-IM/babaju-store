<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new message instance.
     * MEMINTA DATA ORDER
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // KIRIM EMAIL DENGAN SUBJECT BERIKUT
        // TEMPLATE YANG DIGUNAKAN ADALAH order.blade.php YANG ADA DI FOLDER EMAILS
        // DAN PASSING DATA ORDER KE FILE order.blade.php
        return $this->subject('Pesanan Anda dikirim ' . $this->order->invoice)
            ->view('emails.order')
            ->with([
                'order' => $this->order
            ]);

    }
}
