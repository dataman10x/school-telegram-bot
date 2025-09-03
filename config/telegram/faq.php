<?php

$botCreator = AUTHOR_NAME;
$botName = env('TELEGRAM_BOT_NAME');
$botReleaseDate = env('TELEGRAM_BOT_RELEASE_DATE');

return [
    [
        'keywords' => ['about', 'info'],
        'value' => "$botName is your 24/7/ online assistant, that handles all concerns of your customers,
        and gives intelligent replies too."
    ],
    [
        'keywords' => ['what is', 'your name', 'bot name'],
        'value' => "I am $botName, an online business listing Bot, created by $botCreator,
        and was officially released on $botReleaseDate"
    ],
    [
        'keywords' => ['bot', 'ai'],
        'value' => "I am Bot. I don't sleep. This means that no customer will leave unattended to.
        I also grow in intelligence as my owner trains me."
    ],
    [
        'keywords' => ['contact', 'call', 'phone', 'mail', 'email', 'dm', 'message'],
        'value' => "My /contacts are available to easily reach me. I am ever ready to repond."
    ],
    [
        'keywords' => ['price', 'pricing', 'cost'],
        'value' => "Our pricing will be made available after we gather the info from the active /polls.
        We emplore you to vote in the billing opinion poll."
    ],
    [
        'keywords' => ['pay', 'payment'],
        'value' => "I can recieve payment on your behalf, and you get the credit immediately. This feature will be enabled in the next version.
        We are working towards providing a very secure and fast payment option."
    ],
    [
        'keywords' => ['emeka', 'ndefo', 'author', 'creator', 'dataman', 'created by', 'developed by', 'voidlord'],
        'value' => "$botCreator is a Software Engineer who has been the brain behind Creating Apps and Creating Bot Series."
    ],
    [
        'keywords' => ['subscribe', 'subscription'],
        'value' => "Our subscription model will be determined by the results of the published opinion polls, from time to time.
        You don't want to miss out from having a say in the way you are billed. Vote in the /polls today."
    ],
    [
        'keywords' => ['app', 'software', 'web'],
        'value' => "Creating Apps covers all your business needs in terms of owning a fast and secure website. We have best price plans for small scale businesses
        to large enterprises. Our designs match your brand any day any time."
    ],
    [
        'keywords' => ['cbt', 'app'],
        'value' => "Computer Based Test (CBT) apps give great edge to schools and institutions. Students learn better with CBT due to the added advantage of visualization of the tests.
        Our CBT app includes 3 test modes: \n<b>multichoice</b>\n<b>text</b>\n<b>media upload</b>
        The multichoice mode returns the result instantly. Each test can be locked. The record system included, discloses redeemable codes, to students
        that met the set pass mark."
    ],
    [
        'keywords' => ['photos deleted', 'deleted photos', 'deleted my photos', 'find my photos', 'photos are missing'],
        'value' => "Your business photos stay visible on Telegram as long as you didn't delete them from the chat.
        You need to keep your listing active. Telegram may delete data after 2 weeks of inactivity."
    ],
];
