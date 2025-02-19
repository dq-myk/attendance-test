@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_staff_list.css')}}">
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
<div class="staff-list__group">
    <h1>スタッフ一覧</h1>
    <table class="staff-list">
        <thead>
            <tr class = "staff-list__row">
                <th class = "staff-list_label">名前</th>
                <th class = "staff-list_label">メールアドレス</th>
                <th class = "staff-list_label">月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach($users as $user)
            <tr class = "staff-list__row">
                <td class = "staff__data">{{ $user->name }}</td>
                <td class = "staff__data">{{ $user->email }}</td>
                <td>
                    <a class = "staff__data detail" href="/admin/attendance/staff/{{ $user->id }}">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection