@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css')}}">
@endsection

@section('link')
    <div class = "header-container">
        <nav class="header-nav">
            <a class="header__link" href="/attendance">勤怠</a>
            <a class="header__link" href="/attendance/list">勤怠一覧</a>
            <a class="header__link" href="/request/list">申請</a>
            <form action="/logout" method="post">
            @csrf
                <input class="header__link" type="submit" value="ログアウト">
            </form>
        </nav>
    </div>
@endsection

@section('content')
<div class="attendance-list__group">
    <h1>勤怠一覧</h1>

    <div class="date-select">
        <div class="month-before__group">
            <img class="before-icon" src="/images/矢印.png" alt="左矢印">
            <a class="month-before"
                href="?month={{ \Carbon\Carbon::createFromDate($year, $month)->subMonth()->month }}
                        &year={{ \Carbon\Carbon::createFromDate($year, $month)->subMonth()->year }}">
                前月
            </a>
        </div>
        <div class="month_group">
            <img class="calendar-icon" src="/images/calendar_icon.png" alt="カレンダーアイコン">
            <span>{{ sprintf('%04d/%02d', $year, $month) }}</span>
        </div>
        <div class="next-month_group">
            <a class="next-month"
                href="?month={{ \Carbon\Carbon::createFromDate($year, $month)->addMonth()->month }}
                        &year={{ \Carbon\Carbon::createFromDate($year, $month)->addMonth()->year }}">
                翌月
            </a>
            <img class="next-icon" src="/images/矢印.png" alt="右矢印">
        </div>
    </div>

    <table class="attendance-list">
        <thead>
            <tr class = "attendance-list__row">
                <th class = "attendance-list_label">日付</th>
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
                <td class = "attendance__data">{{ \Carbon\Carbon::parse($attendance->date)->translatedFormat('m/d (D)') }}</td>
                <td class = "attendance__data">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') }}</td>
                <td class = "attendance__data">{{ \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') }}</td>
                <td class="attendance__data">
                    {{ gmdate("H:i", $attendance->totalRestTime * 60) }}
                </td>
                <td class="attendance__data">
                    {{ gmdate("H:i", $attendance->workTimeExcludingRest * 60) }}
                </td>
                <td>
                    <a class = "attendance__data detail" href="/attendance/{{ $attendance->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection