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
                        <input class="attendance__data__input {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" type="text" name="year" value="{{ $year }}" {{ !$isEditable ? 'disabled' : '' }}>&nbsp;&nbsp;
                        <input class="attendance__data__input {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" type="text" name="month_day" value="{{ $monthDay }}" {{ !$isEditable ? 'disabled' : '' }}>
                    </td>
                </tr>
                <tr>
                    <th class="attendance-detail_label">出勤・退勤</th>
                    <td class="attendance__data">
                        <input class="attendance__data__input {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" type="time" name="clock_in"
                            value="{{ old('clock_in', $attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '') }}" {{ !$isEditable ? 'disabled' : '' }}>～
                        <input class="attendance__data__input {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" type="time" name="clock_out"
                            value="{{ old('clock_out', $attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '') }}" {{ !$isEditable ? 'disabled' : '' }}>
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
                @foreach ($restsToDisplay as $index => $rest)
                    <tr>
                        @if ($index == 0)
                            <th class="attendance-detail_label">休憩</th>
                        @elseif ($index == 1)
                            <th class="attendance-detail_label">休憩2</th>
                        @endif
                        <td class="attendance__data">
                            <div class="attendance__data__rest">
                                <input class="attendance__data__input {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" type="time" name="rest_start[]"
                                    value="{{ old('rest_start.' . $index, $rest ? \Carbon\Carbon::parse($rest->rest_start)->format('H:i') : '') }}" {{ !$isEditable ? 'disabled' : '' }}>～
                                <input class="attendance__data__input {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" type="time" name="rest_end[]"
                                    value="{{ old('rest_end.' . $index, $rest ? \Carbon\Carbon::parse($rest->rest_end)->format('H:i') : '') }}" {{ !$isEditable ? 'disabled' : '' }}>
                            </div>
                            <div class="detail__error-message">
                                @if ($errors->has('rest_start.' . $index))
                                    <p class="detail__error-message-rest_start">{{ $errors->first('rest_start.' . $index) }}</p>
                                @endif
                                @if ($errors->has('rest_end.' . $index))
                                    <p class="detail__error-message-rest_end">{{ $errors->first('rest_end.' . $index) }}</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach

                {{-- 承認待ちで休憩が1つだけの場合、2つ目の入力欄を追加 --}}
                @if ($attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' && count($restsToDisplay) < 2)
                    <tr>
                        <th class="attendance-detail_label">休憩2</th>
                        <td class="attendance__data">
                            <div class="attendance__data__rest">
                                <input class="attendance__data__input {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" type="time" name="rest_start[]" value="{{ old('rest_start.1') }}" {{ !$isEditable ? 'disabled' : '' }}>～
                                <input class="attendance__data__input {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" type="time" name="rest_end[]" value="{{ old('rest_end.1') }}" {{ !$isEditable ? 'disabled' : '' }}>
                            </div>
                        </td>
                    </tr>
                @endif

                {{-- 休憩が全くない場合、ラベルのみ表示 --}}
                @if (collect($restsToDisplay)->isEmpty())
                    <tr>
                        <th class="attendance-detail_label">休憩</th>
                        <td class="attendance__data"></td>
                    </tr>
                    @if ($attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち')
                        <tr>
                            <th class="attendance-detail_label">休憩2</th>
                            <td class="attendance__data"></td>
                        </tr>
                    @endif
                @endif

                <tr>
                    <th class="attendance-detail_label">備考</th>
                    <td class="attendance__data">
                        <textarea class="attendance__data__text {{ $attendanceCorrectRequest && $attendanceCorrectRequest->status === '承認待ち' ? 'no-border' : '' }}" name="remarks" {{ !$isEditable ? 'disabled' : '' }}>{{ old('remarks',  $remarks) }}</textarea>
                        <p class="detail__error-message">
                            @error('remarks')
                                {{ $message }}
                            @enderror
                        </p>
                    </td>
                </tr>
            </table>

            @if($isEditable)
                <div class="revision">
                    <button class="revision__button" type="submit">修正</button>
                </div>
            @else
                <div class="revision__error">
                    <p class="detail__error-message">*承認待ちのため修正はできません。</p>
                </div>
            @endif
                </div>
    </form>
</div>
@endsection