@extends('errors.layout')

@section('code', '419')
@section('title', 'Sesi halaman sudah habis')
@section('message', 'Halaman ini terlalu lama dibuka, jadi demi keamanan sesinya berakhir. Muat ulang halaman, lalu ulangi langkah terakhirmu.')
@section('actions')
    <button type="button" class="primary" onclick="history.length > 1 ? history.back() : location.reload()">Kembali dan muat ulang</button>
@endsection
