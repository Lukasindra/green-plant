<?php

namespace App\Exceptions;

use RuntimeException;

/** Stok produk tidak mencukupi saat checkout (termasuk stok yang sedang direservasi). */
class StokTidakCukup extends RuntimeException {}
