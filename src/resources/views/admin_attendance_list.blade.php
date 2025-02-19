@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css')}}">
@endsection

@section('link')
    <div class = "header-container">
        <nav class="header-nav">
            <a class="header__link" href="/admin/attendance/list">勤怠一覧</a>
            <a class="header__link" href="/admin/staff/list">スタッフ一覧</a>
            <a class="header__link" href="/stamp_correction_request/list">申請一覧</a>
            <form action="/logout" method="post">
            @csrf
                <input class="header__link" type="submit" value="ログアウト">
            </form>
        </nav>
    </div>
@endsection

@section('content')
<div class="attendance-list__group">
    <h1>{{ \Carbon\Carbon::parse($date)->isoFormat('YYYY年MM月DD日') }}の勤怠</h1>
    <div class="date-select">
        <div class="day-before__group">
            <img class="before-icon" src="/images/矢印.png" alt="左矢印">
            <a class="day-before" href="?date={{ \Carbon\Carbon::parse($date)->subDay()->toDateString() }}">前日</a>
        </div>
        <div class="day_group">
            <img class="calendar-icon" src="/images/calendar_icon.png" alt="カレンダーアイコン">
            <span>{{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}</span>
        </div>
        <div class="next-day_group">
            <a class="next-day" href="?date={{ \Carbon\Carbon::parse($date)->addDay()->toDateString() }}">翌日</a>
            <img class="next-icon" src="/images/矢印.png" alt="右矢印">
        </div>
    </div>

    <table class="attendance-list">
        <thead>
            <tr class = "attendance-list__row">
                <th class = "attendance-list_label">名前</th>
                <th class = "attendance-list_label">出勤</th>
                <th class = "attendance-list_label">退勤</th>
                <th class = "attendance-list_label">休憩</th>
                <th class = "attendance-list_label">合計</th>
                <th class = "attendance-list_label">詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr class = "attendance-list__row">
                <td class = "attendance__data">{{ $attendance->user->name }}</td>
                <td class = "attendance__data">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}</td>
                <td class = "attendance__data">{{ \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') }}</td>
                <td class="attendance__data">
                    {{ gmdate("H:i", $attendance->totalRestTime * 60) }}
                </td>
                <td class="attendance__data">
                    {{ gmdate("H:i", $attendance->workTimeExcludingRest * 60) }}
                </td>
                <td>
                    <a class = "attendance__data detail" href="/admin/attendance/{{ $attendance->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection