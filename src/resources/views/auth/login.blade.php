@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css')}}">
@endsection

@section('content')
<div class="login-form">
    <h2 class="login-form__heading content__heading">ログイン</h2>
    <div class="login-form__inner">
        <form class="login-form__form" action="/login" method="post">
        @csrf
        <div class="login-form__group">
            <label class="login-form__label" for="email">メールアドレス</label>
            <input class="login-form__input" type="text" name="email">
            <p class="login-form__error-message">
            @error('email')
            {{ $message }}
            @enderror
            </p>
        </div>
        <div class="login-form__group">
            <label class="login-form__label" for="password">パスワード</label>
            <input class="login-form__input" type="password" name="password">
            <p class="login-form__error-message">
            @error('password')
            {{ $message }}
            @enderror
            </p>
        </div>

        <!-- ログインエラーのカスタムメッセージ表示 -->
            @if ($errors->has('login_error'))
                <p class="login-form__error-message">
                    {{ $errors->first('login_error') }}
                </p>
            @endif

        <div>
            <button class="login-form__btn btn" type="submit">ログインする</button>

            <div class = "register__link">
                    <a class = "register__submit" href="/register">会員登録はこちら</a>
            </div>
        </div>
        </form>
    </div>
</div>
@endsection('content')