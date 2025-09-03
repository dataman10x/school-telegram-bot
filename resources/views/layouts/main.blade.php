@extends('layouts.root')

@section('head')
    @include('includes.head')
    @stack('css-top')
    @stack('script-top')
@endsection('head')


@section('topbar')

@endsection('topbar')


@section('menubar')

@endsection('menubar')


@section('main')
    <div class="min-vh-100">
        <div class="min-vw-100 min-vh-100">
            @yield('content')
        </div>
    </div>

@endsection('main')


@section('footer')
    @if(1==1)
        <footer class="row min-vw-100 py-3 py-lg-4">
            @include('includes.footer')
        </footer>
    @endif
        @include('includes.footer_scripts')
        @stack('script-bottom')
@endsection('footer')
