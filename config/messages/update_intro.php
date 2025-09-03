<?php

$botLabel = env('TELEGRAM_BOT_LABEL');

return 
"Choose what you wish to do:

* Update Admin No with own phone - the device is owned by the student

* Update Admin No with Parent's phone - the device is owned by the parent of the student

* Update Admin with other phone - the phone is neither the student's nor the parent's

* Upload Photo for magazine - only for teachers to send photos

* Request Student's Reports - yet to be enabled
";