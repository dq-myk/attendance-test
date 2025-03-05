@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_detail.css')}}">
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
<div class="attendance-detail__group">
    <h1>勤怠詳細</h1>
    <form action="/admin/attendance/{{ $attendance->id }}" method="POST">
        @csrf
        @method('PUT')
        <div class="attendance-detail__table">
            <table class="attendance-detail">
                <tr class="attendance-detail__row">
                    <th class="attendance-detail_label">名前</th>
                    <td class="attendance__data__name">{{ $attendance->user->name }}</td>
                </tr>
                <tr class="attendance-detail__row">
                    <th class="attendance-detail_label">日付</th>
                    <td class="attendance__data">
                        <input class="attendance__data__input" type="text" name="year" value="{{ $year }}" >&nbsp;&nbsp;
                        <input class="attendance__data__input" type="text" name="month_day" value="{{ $monthDay }}" >
                    </td>
                </tr>
                <tr>
                    <th class="attendance-detail_label">出勤・退勤</th>
                    <td class="attendance__data">
                        <input class="attendance__data__input" type="time" name="clock_in" value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}" >～
                        <input class="attendance__data__input" type="time" name="clock_out" value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}" >
                        <div class="detail__error-message">
                            @if ($errors->has('clock_in'))
                                <p class="detail__error-message-clock_in">{{ $errors->first('clock_in') }}</p>
                            @endif
                            @if ($errors->has('clock_out'))
                                <p class="detail__error-message-clock_out">{{ $errors->first('clock_out') }}</p>
                            @endif
                        </div>
                    </td>
                </tr>

                @foreach (range(0, 1) as $index)
                    @php
                        $rest = $rests[$index] ?? null;
                    @endphp
                    <tr>
                        <th class="attendance-detail_label">
                            @if ($index == 0)
                                休憩
                            @else
                                休憩{{ $index + 1 }}
                            @endif
                        </th>
                        <td class="attendance__data">
                            @if ($rest)
                                <div class="attendance__data__rest">
                                    <input class="attendance__data__input" type="time" name="rest_start[]"
                                        value="{{ old('rest_start.' . $index, $rest->rest_start ? \Carbon\Carbon::parse($rest->rest_start)->format('H:i') : '') }}" >～
                                    <input class="attendance__data__input" type="time" name="rest_end[]"
                                        value="{{ old('rest_end.' . $index, $rest->rest_end ? \Carbon\Carbon::parse($rest->rest_end)->format('H:i') : '') }}" >
                                </div>
                                <div class="detail__error-message">
                                    @if ($errors->has('rest_start.' . $index))
                                        <p class="detail__error-message-rest_start">{{ $errors->first('rest_start.' . $index) }}</p>
                                    @endif
                                    @if ($errors->has('rest_end.' . $index))
                                        <p class="detail__error-message-rest_end">{{ $errors->first('rest_end.' . $index) }}</p>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach

                <tr>
                    <th class="attendance-detail_label">備考</th>
                    <td class="attendance__data">
                        <textarea class="attendance__data__text" name="remarks" >{{ old('remarks', $attendance->remarks) }}</textarea>
                        <p class="detail__error-message">
                            @error('remarks')
                                {{ $message }}
                            @enderror
                        </p>
                    </td>
                </tr>
            </table>
            <div class="revision">
                <button class="revision__button" type="submit">修正</button>
            </div>
        </div>
    </form>
</div>
@endsection