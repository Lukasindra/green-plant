@extends('layouts.admin')

@section('judul', $produk ? 'Edit Produk' : 'Input Produk')

@section('isi')
    <div class="mx-auto max-w-3xl">
        @include('admin.produk._form')
    </div>
@endsection
