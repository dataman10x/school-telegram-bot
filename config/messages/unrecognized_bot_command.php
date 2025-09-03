<?php
$botLabel = env('TELEGRAM_BOT_LABEL');

return "<b>Unrecognized Bot Command</b>

<blockquote>$botLabel haven't listed your command: '%s' in our command set yet.</blockquote>

You may see our /help section for available commands.
";