@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_approve.css')}}">
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
<div class="approve__group">
    <h1>勤怠詳細</h1>
    <form action="/stamp_correction_request/approve/{{ $attendance_correct_request->id }}" method="POST">
    @csrf
        <div class="approve__table">
            <table class="approve">
                <tr class="approve__row">
                    <th class="approve_label">名前</th>
                    <td class="approve__data__name">{{ $attendance->user->name }}</td>
                </tr>
                <tr class="approve__row">
                    <th class="approve_label">日付</th>
                    <td class="approve__data">
                        <input class="approve__data__input" type="text" name="year" value="{{ $year }}" disabled>&nbsp;&nbsp;
                        <input class="approve__data__input" type="text" name="month_day" value="{{ $monthDay }}" disabled>
                    </td>
                </tr>
                <tr>
                    <th class="approve_label">出勤・退勤</th>
                    <td class="approve__data">
                        <input class="approve__data__input" type="time" name="clock_in" value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}" disabled>～
                        <input class="approve__data__input" type="time" name="clock_out" value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}" disabled>
                    </td>
                </tr>
                @foreach ($rests as $index => $rest)
                    <tr>
                        @if ($index == 0)
                            <th class="approve_label">休憩</th>
                        @else
                            <th class="approve_label">休憩{{ $index + 1 }}</th>
                        @endif
                        <td class="attendance__data">
                            <div class="approve__data__rest">
                                <input class="approve__data__input" type="time" name="rest_start[]" value="{{ old('rest_start.' . $index, $rest->rest_start ? \Carbon\Carbon::parse($rest->rest_start)->format('H:i') : '') }}" disabled>～
                                <input class="approve__data__input" type="time" name="rest_end[]" value="{{ old('rest_end.' . $index, $rest->rest_end ? \Carbon\Carbon::parse($rest->rest_end)->format('H:i') : '') }}" disabled>
                            </div>
                        </td>
                    </tr>
                @endforeach
                {{-- 休憩がない場合の空の入力欄 --}}
                @if ($rests->isEmpty())
                    <tr>
                        <th class="approve_label">休憩</th>
                        <td class="approve__data">
                            <div class="approve__data__rest">
                                <input class="approve__data__input" type="time" name="rest_start[]" value="" disabled>～
                                <input class="approve__data__input" type="time" name="rest_end[]" value="" disabled>
                            </div>
                        </td>
                    </tr>
                @endif
                <tr>
                    <th class="approve_label">備考</th>
                    <td class="approve__data">
                        <textarea class="approve__data__text" name="remarks" disabled>{{ old('remarks', $attendance->remarks) }}</textarea>
                    </td>
                </tr>
            </table>
            <div class="approval">
                <button class="approval__button" type="submit">承認</button>
            </div>
        </div>
    </form>
</div>
@endsection