<?php

$botLabel = env('TELEGRAM_BOT_LABEL');
$botCreatorName = AUTHOR_NAME;
$botCreatorNickname = AUTHOR_NICKNAME;
$botReleaseDate = env('TELEGRAM_BOT_RELEASE_DATE');

return "I am <b>$botLabel</b> saddled with the task to help students update their admission number to database to enable registration; send assessment reports to parent; and recieve photo uploads from teachers for school magazine.

A brief history about myself:
<blockquote><b>$botCreatorName ($botCreatorNickname @MeetDatamanBot)</b> created me, and was officially released to the public on $botReleaseDate.</blockquote>
";