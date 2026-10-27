@extends('errors.layout')

@section('code', $exception->getStatusCode())
@section('title', 'Permintaan tidak bisa diproses')
@section('message', 'Ada yang tidak beres dengan permintaan ini. Kembali ke halaman sebelumnya lalu coba lagi.')
