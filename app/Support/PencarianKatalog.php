<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Pencarian katalog yang konsisten untuk produk dan jasa.
 *
 * PostgreSQL membuat LIKE peka huruf besar/kecil (produksi memakai PostgreSQL),
 * sedangkan SQLite/MySQL tidak. Karena itu operator dipilih sesuai driver.
 */
trait PencarianKatalog
{
    protected function terapkanPencarian(Builder $query, string $q): Builder
    {
        if ($q === '') {
            return $query;
        }

        $operator = $query->getModel()->getConnection()->getDriverName() === 'pgsql'
            ? 'ilike'
            : 'like';

        $pola = "%{$q}%";

        return $query->where(function (Builder $cari) use ($operator, $pola): void {
            $cari->where('nama', $operator, $pola)
                ->orWhere('deskripsi', $operator, $pola)
                ->orWhereHas('kategori', fn (Builder $kategori) => $kategori->where('nama', $operator, $pola));
        });
    }
}
