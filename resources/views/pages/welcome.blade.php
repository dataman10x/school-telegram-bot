@extends('layouts.main', ['title' => 'Bot Test', 'description' => 'Bot Test'])

@php
    $rootUrl = env('ROOT_URL');
    $telegramToken = config('botman.config.telegram_bot_id');
    $frameEndpoint = "http://localhost/testbot/botman/chat";
    $chatServer = "http://localhost/testbot/botman";
@endphp

@section('content')
        <script>
        var botmanWidget = {
            frameEndpoint: "{{$frameEndpoint}}",
            chatServer: "{{$chatServer}}",
            title: 'Test Bot',
            aboutText: 'Testing Bot',
            introMessage: "✋ Hi! I'm from Creat.i.ng. Type something to get response.",
            placeholderText: "Send a message",
            mainColor: '#aad6ec',
            bubbleBackground: '#000080',
            aboutText: 'Powered by Creating',
            aboutLink: 'https://creat.i.ng',
            timeFormat: 'm/d/yy HH:MM:s',
            bubbleAvatarUrl: "{{$rootUrl}}/bootstrap/img/cbt-mini.jpg"
        };
        </script>
        <script src='https://cdn.jsdelivr.net/npm/botman-web-widget@0/build/js/widget.js'></script>

@endsection
