@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css')}}">
@endsection

@section('link')
    <div class = "header-container">
        <nav class="header-nav">
            <a class="header__link" href="/attendance">勤怠</a>
            <a class="header__link" href="/attendance/list">勤怠一覧</a>
            <a class="header__link" href="/a/stamp_correction_request/list">申請</a>
            <form action="/logout" method="post">
            @csrf
                <input class="header__link" type="submit" value="ログアウト">
            </form>
        </nav>
    </div>
@endsection

@section('content')
<div class = "attendance-group">
    <div class="status-label">{{ $status }}</div>
    <h1 class="date">{{ $currentDate }}</h1>
    <h2 class="time">{{ $currentTime }}</h2>
    <div class="button-container">
        @if ($status === '勤務外')
            <form action="/attendance/start" method="POST">
                @csrf
                <button type="submit" class="button">出勤</button>
            </form>
        @elseif ($status === '勤務中')
            <form action="/attendance/end" method="POST">
                @csrf
                <button type="submit" class="button">退勤</button>
            </form>
            <form action="/attendance/rest-start" method="POST">
                @csrf
                <button type="submit" class="button">休憩入</button>
            </form>
        @elseif ($status === '休憩中')
            <form action="/attendance/rest-end" method="POST">
                @csrf
                <button type="submit" class="button">休憩戻</button>
            </form>
        @endif
    </div>
</div>
@endsection