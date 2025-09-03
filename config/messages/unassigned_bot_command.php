<?php
$botLabel = env('TELEGRAM_BOT_LABEL');

return "<b>Unassigned Bot Command</b>

<blockquote>$botLabel haven't linked this command: '%s' with a function on our system yet. This means that we recognize your command but it is not in use at this moment.</blockquote>

You may see our /help section for available commands.
";