<?php

namespace App\Exceptions;

use DomainException;

// Dilempar saat stok produk tidak cukup ketika pembayaran pesanan dikonfirmasi.
class StokTidakCukupException extends DomainException
{
}
