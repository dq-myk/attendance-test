@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css')}}">
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
<div class="attendance-detail__group">
    <h1>勤怠詳細</h1>
    <form action="/attendance/{{ $attendance->id }}" method="POST">
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
                        <input class="attendance__data__input" type="text" name="year" value="{{ $year }}">&nbsp;&nbsp;
                        <input class="attendance__data__input" type="text" name="month_day" value="{{ $monthDay }}">
                    </td>
                </tr>
                <tr>
                    <th class="attendance-detail_label">出勤・退勤</th>
                    <td class="attendance__data">
                        <input class="attendance__data__input" type="time" name="clock_in" value="{{ $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '' }}">～
                        <input class="attendance__data__input" type="time" name="clock_out" value="{{ $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '' }}">
                            @if ($errors->has('clock_in'))
                                <p class="detail__error-message-clock_in">{{$errors->first('clock_in')}}</p>
                            @endif
                            @if ($errors->has('clock_out'))
                                <p class="detail__error-message-clock_out">{{$errors->first('clock_out')}}</p>
                            @endif
                        </div>
                    </td>
                </tr>
                @foreach ($rests as $index => $rest)
                    <tr>
                        @if ($index == 0)
                            <th class="attendance-detail_label">休憩</th>
                        @else
                            <th class="attendance-detail_label">休憩{{ $index + 1 }}</th>
                        @endif
                        <td class="attendance__data">
                            <div class="attendance__data__rest">
                                <input class="attendance__data__input" type="time" name="rest_start[]" value="{{ $rest->rest_start ? \Carbon\Carbon::parse($rest->rest_start)->format('H:i') : '' }}">～
                                <input class="attendance__data__input" type="time" name="rest_end[]" value="{{ $rest->rest_end ? \Carbon\Carbon::parse($rest->rest_end)->format('H:i') : '' }}">
                            </div>
                            <div class="detail__error-message">
                                @if ($errors->has('rest_start.' . $index))
                                    <p class="detail__error-message-rest_start">{{$errors->first('rest_start.' . $index)}}</p>
                                @endif
                                @if ($errors->has('rest_end.' . $index))
                                    <p class="detail__error-message-rest_end">{{$errors->first('rest_end.' . $index)}}</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach

                {{-- 休憩がない場合の空の入力欄 --}}
                @if ($rests->isEmpty())
                    <tr>
                        <th class="attendance-detail_label">休憩</th>
                        <td class="attendance__data">
                            <div class="attendance__data__rest">
                                <input class="attendance__data__input" type="time" name="rest_start[]" value="">～
                                <input class="attendance__data__input" type="time" name="rest_end[]" value="">
                            </div>
                        </td>
                    </tr>
                @endif

                <tr>
                    <th class="attendance-detail_label">備考</th>
                    <td class="attendance__data">
                        <textarea class="attendance__data__text" name="remarks">{{ old('remarks', $attendance->remarks) }}</textarea>
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