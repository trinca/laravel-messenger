<?php

namespace Trvmsg\Messenger\Test\Stubs\Models;

use Trvmsg\Messenger\Models\Message;

class CustomMessage extends Message
{
    protected $table = 'custom_messages';
}
