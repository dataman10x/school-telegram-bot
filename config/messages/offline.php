<?php

$botName = env('TELEGRAM_BOT_NAME');
$botLabel = env('TELEGRAM_BOT_LABEL');
$botCreatorName = AUTHOR_NAME;
$botCreatorNickname = AUTHOR_NICKNAME;
$botCreatorEmail = AUTHOR_EMAIL;
$botCreatorMobile = AUTHOR_MOBILE;
$botReleaseDate = env('TELEGRAM_BOT_RELEASE_DATE');

return 
"<b>OFF FOR MAINTENANCE</b>

<blockquote>I $botLabel  will miss your company at this time.
It is crucial I take a little Bot nap.</blockquote>

Still want to reach me? Send an email to $botCreatorEmail OR call $botCreatorMobile.
";