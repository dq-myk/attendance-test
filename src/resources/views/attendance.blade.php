@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('link')
    <div class = "header-container">
        <nav class="header-nav">
            <a class="header__link" href="/attendance">勤怠</a>
            <a class="header__link" href="/attendance/list">勤怠一覧</a>
            <a class="header__link" href="/stamp_correction_request/list">申請</a>
            <form action="/logout" method="post">
            @csrf
                <input class="header__link" type="submit" value="ログアウト">
            </form>
        </nav>
    </div>
@endsection

@section('content')
<div class="attendance__group">
    <div class="status-label">{{ $status }}</div>
    <h1 class="date">{{ $currentDate }}</h1>
    <h2 class="time">{{ $currentTime }}</h2>
    <div class="button-container">
        @if ($status === '勤務外' && !$isAlreadyCheckedIn)
            <form action="/attendance/start" method="POST">
                @csrf
                <button class = "attendance__btn" type="submit" class="button">出勤</button>
            </form>
        @elseif ($status === '出勤中')
            <form action="/attendance/end" method="POST">
                @csrf
                <button class = "attendance__btn" type="submit" class="button">退勤</button>
            </form>
            <form class = "rest__btn" action="/attendance/rest-start" method="POST">
                @csrf
                <button type="submit" class="button">休憩入</button>
            </form>
        @elseif ($status === '休憩中')
            <form action="/attendance/rest-end" method="POST">
                @csrf
                <button class = "rest__btn" type="submit" class="button">休憩戻</button>
            </form>
        @endif
        @if (session('message'))
        <div class="alert">
            {{ session('message') }}
        </div>
        @endif
    </div>
</div>
@endsection

