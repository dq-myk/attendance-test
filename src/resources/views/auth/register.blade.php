@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css')}}">
@endsection

@section('content')
<div class="register-form">
    <h2 class="register-form__heading content__heading">会員登録</h2>

    <div class="register-form__inner">
        <form class="register-form__form" action="/register" method="post">
        @csrf
        <div class="register-form__group">
            <label class="register-form__label" for="name">ユーザー名</label>
            <input class="register-form__input" type="text" name="name">
            <p class="register-form__error-message">
            @error('name')
            {{ $message }}
            @enderror
            </p>
        </div>
        <div class="register-form__group">
            <label class="register-form__label" for="email">メールアドレス</label>
            <input class="register-form__input" type="email" name="email">
            <p class="register-form__error-message">
            @error('email')
            {{ $message }}
            @enderror
            </p>
        </div>
        <div class="register-form__group">
            <label class="register-form__label" for="password">パスワード</label>
            <input class="register-form__input" type="password" name="password">
            <p class="register-form__error-message">
            @error('password')
            {{ $message }}
            @enderror
            </p>
        </div>

        <div class = "register-form__group">
            <label class = "register-form__label" for = "password">確認用パスワード</label>
            <input class = "register-form__input" type = "password" name = "password_confirmation">
            <p class = "register-form__error-message">
            @error('password')
            {{ $message }}
            @enderror
            </p>
        </div>

        <div>
            <button class="register-form__btn btn" type="submit">登録する</button>

            <div class = "login__link">
                <a class = "login__submit" href="/login">ログインはこちら</a>
            </div>
        </div>
        </form>
    </div>
</div>
@endsection('content')