@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_staff.css')}}">
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
<div class="attendance_staff__group">
    <h1>{{ $user->name }}の勤怠</h1>

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

    <table class="attendance_staff">
        <thead>
            <tr class = "attendance_staff__row">
                <th class = "attendance_staff_label">日付</th>
                <th class = "attendance_staff_label">出勤</th>
                <th class = "attendance_staff_label">退勤</th>
                <th class = "attendance_staff_label">休憩</th>
                <th class = "attendance_staff_label">合計</th>
                <th class = "attendance_staff_label">詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $attendance)
            <tr class = "attendance_staff__row">
                <td class = "attendance_staff__data">{{ $attendance->date ? \Carbon\Carbon::parse($attendance->date)->translatedFormat('m/d (D)') : '' }}</td>
                <td class = "attendance_staff__data">{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}</td>
                <td class = "attendance_staff__data">{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}</td>
                <td class="attendance_staff__data">
                    {{ ($attendance->totalRestTime ?? 0) > 0 ? gmdate("H:i", $attendance->totalRestTime * 60) : '' }}
                </td>
                <td class="attendance_staff__data">
                    {{ ($attendance->workTimeExcludingRest ?? 0) > 0 ? gmdate("H:i", $attendance->workTimeExcludingRest * 60) : '' }}
                </td>
                <td>
                    <a class = "attendance_staff__data detail" href="/admin/attendance/{{ $attendance->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection