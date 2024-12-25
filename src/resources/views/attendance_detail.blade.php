@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css')}}">
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
<div class="attendance-detail__group">

    <h1>勤怠詳細</h1>

    <table class="attendance-detail">
        <tr class="attendance-detail__row">
            <th class="attendance-detail_label">名前</th>
            <td class="attendance__data">{{ $attendance->user->name ?? '' }}</td>
            <input type="hidden" name="name" value="{{ $attendance->user->name ?? '' }}">
        </tr>
        <tr class="attendance-detail__row">
            <th class="attendance-detail_label">日付</th>
            <td class="attendance__data">
                <input class="attendance__data__input" type="text" name="date" value="{{ old('date', $attendance->user->date->format('Y年') ?? '') }}">
            </td>
            <td class="attendance__data">
                <input class="attendance__data__input" type="text" name="date" value="{{ old('date', $attendance->user->date->format('m月d日') ?? '') }}">
            </td>
            <input type="hidden" name="date" value="{{ $attendance->user->date ?? '' }}">
        </tr>
        <tr>
            <th class="attendance-detail_label">出勤</th>
            <td class="attendance__data">
                <input class="attendance__data__input" type="text" name="clock_in" value="{{ old('clock_in', \Carbon\Carbon::parse($attendance->clock_in ?? '')->format('H:i')) }}">
                <input type="hidden" name="clock_in" value="{{ \Carbon\Carbon::parse($attendance->clock_in ?? '')->format('H:i') }}">
            </td>
            <td>～</td>
            <td class="attendance__data">
                <input class="attendance__data__input" type="text" name="clock_out" value="{{ old('clock_out', \Carbon\Carbon::parse($attendance->clock_out ?? '')->format('H:i')) }}">
                <input type="hidden" name="clock_out" value="{{ \Carbon\Carbon::parse($attendance->clock_out ?? '')->format('H:i') }}">
            </td>
        </tr>
        <tr>
            <th class="attendance-detail_label">休憩</th>
            <td class="attendance__data">
                <input class="attendance__data__input" type="text" name="rest_start" value="{{ old('rest_start', \Carbon\Carbon::parse($rest->rest_start ?? '')->format('H:i')) }}">
                <input type="hidden" name="rest_start" value="{{ \Carbon\Carbon::parse($rest->rest_start ?? '')->format('H:i') }}">
            </td>
            <td>～</td>
            <td class="attendance__data">
                <input class="attendance__data__input" type="text" name="rest_end" value="{{ old('rest_end', \Carbon\Carbon::parse($rest->rest_end ?? '')->format('H:i')) }}">
                <input type="hidden" name="rest_end" value="{{ \Carbon\Carbon::parse($rest->rest_end ?? '')->format('H:i') }}">
            </td>
        </tr>
        <tr>
            <th class="attendance-detail_label">備考</th>
            <textarea name="remarks"></textarea>
        </tr>
    </table>
</div>
@endsection