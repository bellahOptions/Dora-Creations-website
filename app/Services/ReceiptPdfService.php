<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;

class ReceiptPdfService
{
    public function forOrder(Order $order): PdfDocument
    {
        $order->loadMissing('items');

        return Pdf::loadView('pdf.receipt', ['order' => $order])
            ->setPaper('a4', 'portrait');
    }

    public function filename(Order $order): string
    {
        return "receipt-{$order->order_number}.pdf";
    }
}
