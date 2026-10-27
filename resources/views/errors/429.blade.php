@extends('errors.layout')

@section('code', '429')
@section('title', 'Terlalu banyak percobaan')
@section('message', 'Tunggu sekitar satu menit, lalu coba lagi.')
@section('actions')
    <button type="button" class="primary" onclick="location.reload()">Coba lagi</button>
@endsection
