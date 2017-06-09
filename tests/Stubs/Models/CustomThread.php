<?php

namespace Trvmsg\Messenger\Test\Stubs\Models;

use Trvmsg\Messenger\Models\Message;

class CustomThread extends Message
{
    protected $table = 'custom_threads';
}
