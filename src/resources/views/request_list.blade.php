@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request_list.css')}}">
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
    <div class="tab__group">
        <h1>申請一覧</h1>
        <div class="request__tab-menu">
            <a href="/stamp_correction_request/list?tab=wait" class="request__tab request__tab__wait {{ $tab == 'wait' ? 'active' : '' }}">承認待ち</a>
            <a href="/stamp_correction_request/list?tab=complete" class="request__tab request__tab__complete {{ $tab == 'complete' ? 'active' : '' }}">承認済み</a>
        </div>
    </div>

    <div class="request__group">
        <table class="request_table">
            <thead>
                <tr class="request__row">
                    <th class="request_label">状態</th>
                    <th class="request_label">名前</th>
                    <th class="request_label">対象日時</th>
                    <th class="request_label">申請理由</th>
                    <th class="request_label">申請日時</th>
                    <th class="request_label">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $attendanceCorrectRequest)
                <tr class="request__row">
                    <td class="request__data">{{ $attendanceCorrectRequest->status }}</td>
                    <td class="request__data">{{ $attendanceCorrectRequest->user->name }}</td>
                    <td class="request__data">{{ \Carbon\Carbon::parse($attendanceCorrectRequest->date)->format('Y/m/d') }}</td>
                    <td class="request__data">{{ $attendanceCorrectRequest->remarks }}</td>
                    <td class="request__data">{{ \Carbon\Carbon::parse($attendanceCorrectRequest->created_at)->format('Y/m/d') }}</td>
                    <td>
                        <a class="request__data detail" href="/attendance/{{ $attendanceCorrectRequest->attendance_id }}">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection