@extends('layouts.admin')

@section('judul', $jasa ? 'Edit Jasa' : 'Input Jasa')

@section('isi')
    <div class="mx-auto max-w-3xl">
        @include('admin.jasa._form')
    </div>
@endsection
