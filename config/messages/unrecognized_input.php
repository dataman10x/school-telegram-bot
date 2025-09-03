<?php
$botLabel = env('TELEGRAM_BOT_LABEL');

return "<b>Unrecognized Input</b>

<blockquote>$botLabel couldn't match your command: '%s' with our existing commands and in the available algorithm.</blockquote>

You may see our /help section for available commands.
";