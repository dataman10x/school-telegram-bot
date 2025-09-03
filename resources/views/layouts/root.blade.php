
<!doctype html>
<html data-bs-theme="{{$appMode??'light'}}">
<head>
   @yield('head')
</head>
<body>
    @yield('topbar')

    @yield('menubar')

    @yield('main')

    @yield('footer')
</body>
</html>
