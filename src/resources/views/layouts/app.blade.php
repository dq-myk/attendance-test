<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>COACHTECH</title>
    <link rel="stylesheet" href="https://unpkg.com/ress/dist/ress.min.css" />
    <link rel="stylesheet" href="{{ asset('css/common.css')}}">
    @yield('css')
</head>

<body>
    <div class="app">
        <header class="header">
        <img class="header__logo" src="/images/CoachTech_White 1.png" alt="Website Logo">
        @yield('link')
        </header>
        <div class="content">
        @yield('content')
        </div>
    </div>
</body>

</html>