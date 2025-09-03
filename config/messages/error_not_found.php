<?php
$botLabel = env('TELEGRAM_BOT_LABEL');

return "<b>Oops!</b>

<blockquote>$botLabel could not find the requested data. It may not exist.</blockquote>

If you feel this is a bug, do report it to us by specifying the exact action(s) that prompted it.
";