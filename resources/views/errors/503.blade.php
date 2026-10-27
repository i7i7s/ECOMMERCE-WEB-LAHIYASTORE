@extends('errors.layout')

@section('code', '503')
@section('title', 'Sedang dalam perawatan')
@section('message', 'Lahiya Store sedang diperbarui dan akan kembali dalam beberapa menit. Pesanan dan datamu aman.')
@section('actions')
    <button type="button" class="primary" onclick="location.reload()">Coba lagi</button>
@endsection
