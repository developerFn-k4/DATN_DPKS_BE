<?php

namespace App\Mail;

use App\Models\Booking;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Xac nhan thanh toan dat phong #' . $this->booking->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-paid',
            with: [
                'booking' => $this->booking,
            ]
        );
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.booking-invoice', [
            'booking' => $this->booking,
        ]);

        return [
            Attachment::fromData(fn () => $pdf->output(), 'invoice-booking-' . $this->booking->id . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
