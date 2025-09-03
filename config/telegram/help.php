<?php
    $helpCommands = [
        config('telegram.commands_bot.start'),
        config('telegram.commands_bot.menu'),
        config('telegram.commands_bot.contacts'),
        config('telegram.commands_bot.about'),
        config('telegram.commands_bot.stats'),
        config('telegram.commands_bot.search'),
        config('telegram.commands_bot.help'),
        config('telegram.commands_bot.polls'),
        config('telegram.commands_bot.socials'),
        config('telegram.commands_bot.dm')
    ];

    $getCmds = implode('
    ', $helpCommands);

return [
    'commands' => "<b>Quick Commands</b>\n$getCmds",
    'find_biz' => "<b>Finding Businesses</b>
Business search are in the following order of fastness:
1. Type the Business ID in the input field
2. Prefix your search term with 'biz'
3. Use the List view in the /businesses section
4. Scroll through the listing in Single view",
    'dm' => "<b>View Sent Direct Messages</b>
This is helpful when you want to view the replies to your sent direct messages.
Simply prefix any of the following with 'dm'
* all: to view all your sent messages
* last: to view your last message
* unreplied: to view unreplied messages
* replied: to view replied messages"
];
