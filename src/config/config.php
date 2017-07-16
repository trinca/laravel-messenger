<?php

return [
    'user_model' => 'App\User',

    'message_model' => 'Trvmsg\Messenger\Models\Message',

    'participant_model' => 'Trvmsg\Messenger\Models\Participant',

    'thread_model' => 'Trvmsg\Messenger\Models\Thread',
    
     /**
     * Define custom database table names.
     */
    'messages_table' => null,
    
    'participants_table' => null,
    
    'event_messages_table' => null,
    
    'event_participants_table' => null,
    
    'threads_table' => null,
];
