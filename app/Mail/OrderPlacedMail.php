<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Preference;
use App\Models\Shop;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $shop;
    public $preference;

    /**
     * Create a new message instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->shop = Shop::find($order->shop_id);
        $this->preference = Preference::find($order->shop_id);
    }

    /**
     * Get the message envelope.
     */
//    public function envelope(): Envelope
//    {
//        return new Envelope(
//            subject: 'Order Placed Mail',
//        );
//    }

    /**
     * Get the message content definition.
     */
//    public function content(): Content
//    {
//        return new Content(
//            view: 'emails.orders.order-placed',
//        );
//    }

//    /**
//     * Get the attachments for the message.
//     *
//     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
//     */
//    public function attachments(): array
//    {
//        return [];
//    }

    public function build()
    {
        return $this
            ->subject($this->shop->shop_name.' Your Order/Invoice #' . $this->order->order_number)
            ->view('emails.orders.order-placed')
            ->with(['order' => $this->order,]);
    }
}
