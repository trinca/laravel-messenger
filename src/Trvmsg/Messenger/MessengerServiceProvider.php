<?php

namespace Trvmsg\Messenger;

use Trvmsg\Messenger\Models\EventMessage;
use Trvmsg\Messenger\Models\EventParticipant;
use Trvmsg\Messenger\Models\Message;
use Trvmsg\Messenger\Models\Models;
use Trvmsg\Messenger\Models\Participant;
use Trvmsg\Messenger\Models\Thread;
use Illuminate\Support\ServiceProvider;

class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            base_path('vendor/trvmsg/messenger/src/config/config.php') => config_path('messenger.php'),
            base_path('vendor/trvmsg/messenger/src/migrations') => base_path('database/migrations'),
        ]);

        $this->setMessengerModels();
        $this->setUserModel();
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            base_path('vendor/trvmsg/messenger/src/config/config.php'), 'messenger'
        );
    }

    private function setMessengerModels()
    {
        $config = $this->app->make('config');

        Models::setMessageModel($config->get('messenger.message_model', Message::class));
        Models::setThreadModel($config->get('messenger.thread_model', Thread::class));
        Models::setParticipantModel($config->get('messenger.participant_model', Participant::class));
        Models::setEventMessageModel($config->get('messenger.event_message_model', EventMessage::class));
        Models::setEventParticipantModel($config->get('messenger.event_participant_model', EventParticipant::class));

        Models::setTables([
            'messages' => $config->get('messenger.messages_table', Models::message()->getTable()),
            'participants' => $config->get('messenger.participants_table', Models::participant()->getTable()),
            'ev_messages' => $config->get('messenger.event_messages_table', Models::evMessage()->getTable()),
            'ev_participants' => $config->get('messenger.event_participants_table', Models::evParticipant()->getTable()),
            'threads' => $config->get('messenger.threads_table', Models::thread()->getTable()),
        ]);
    }

    private function setUserModel()
    {
        $config = $this->app->make('config');

        $model = $config->get('auth.providers.users.model', function () use ($config) {
            return $config->get('auth.model', $config->get('messenger.user_model'));
        });

        Models::setUserModel($model);

        Models::setTables([
            'users' => (new $model)->getTable(),
        ]);
    }
}