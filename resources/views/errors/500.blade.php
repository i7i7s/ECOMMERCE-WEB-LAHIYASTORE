@extends('errors.layout')

@section('code', '500')
@section('title', 'Gagal memuat halaman')
@section('message', 'Ada gangguan di server kami. Pesanan dan datamu aman. Coba lagi sebentar lagi; kalau masih gagal, hubungi admin.')
@section('actions')
    <button type="button" class="primary" onclick="location.reload()">Coba lagi</button>
    <a href="{{ url('/') }}" class="link">Kembali ke beranda</a>
@endsection
