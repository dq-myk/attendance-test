@extends('layouts.app')

<link rel="stylesheet" href="{{ asset('css/verify.css') }}">

@section('content')
    <div class="verification-container">
        <h1 class="verification-title">メール認証</h1>
        <p class="verification-text">メールアドレスを確認するためのリンクを送信しました。</p>
        <p class="verification-text">メールを確認し、リンクをクリックしてください。</p>

        @if (session('message'))
            <p class="verification-message">{{ session('message') }}</p>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="verification-button">再送信する</button>
        </form>
    </div>
@endsection