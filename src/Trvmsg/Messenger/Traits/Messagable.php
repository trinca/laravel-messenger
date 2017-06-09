<?php

namespace Trvmsg\Messenger\Traits;

use App\Helpers\Helper;
use Trvmsg\Messenger\Models\EventParticipant;
use Trvmsg\Messenger\Models\Message;
use Trvmsg\Messenger\Models\Models;
use Trvmsg\Messenger\Models\Participant;
use Trvmsg\Messenger\Models\Thread;
use Illuminate\Support\Facades\Auth;

trait Messagable
{
    /**
     * Message relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany(Models::classname(Message::class));
    }

    /**
     * Participants relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function participants()
    {
        return $this->hasMany(Models::classname(Participant::class));
    }

    /**
     * Thread relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function threads()
    {
        return $this->belongsToMany(
            Models::classname(Thread::class),
            Models::table('participants'),
            'user_id',
            'thread_id'
        )
            ->whereNull(Models::table('participants').'.deleted_by_user');
    }

    public function userThreads()
    {
        return $this->hasMany(Models::classname(Thread::class), 'created_by')
            ->whereNull('sim_messenger_threads.deleted_by_user');
    }

    public function trashThreads()
    {
        return $this->belongsToMany(
            Models::classname(Thread::class),
            Models::table('participants'),
            'user_id',
            'thread_id'
        )->whereNotNull('sim_messenger_threads.deleted_by_user');
        //->whereNotNull(Models::table('threads').'.deleted_by_user');
    }

    /**
     * Returns the new messages count for user.
     *
     * @return int
     */
    public function newThreadsCount()
    {
        return count($this->threadsWithNewMessages(1));
    }

    /**
     * Returns the new notification messages count for user.
     *
     * @return int
     */
    public function newEventsThreadsCount()
    {
        return count($this->eventThreadsWithNewMessages(2));
    }

    /**
     * Returns all threads with new messages.
     *
     * @return array
     */
    public function threadsWithNewMessages($catId)
    {
        $threadsWithNewMessages = [];

        $participants = Models::participant()->where('user_id', $this->id)->whereNull('sim_messenger_participants.deleted_by_user')->where('sim_messenger_threads.created_by', '<>', Auth::user()->id)->threadCategory($catId)->pluck('last_read', 'thread_id');

        /**
         * @todo: see if we can fix this more in the future.
         * Illuminate\Foundation is not available through composer, only in laravel/framework which
         * I don't want to include as a dependency for this package...it's overkill. So let's
         * exclude this check in the testing environment.
         */
        if (getenv('APP_ENV') == 'testing' || !str_contains(\Illuminate\Foundation\Application::VERSION, '5.0')) {
            $participants = $participants->all();
        }

        if ($participants) {
            $threads = Models::thread()->whereIn('id', array_keys($participants))->get();

            foreach ($threads as $thread) {
                if ($thread->updated_at > $participants[$thread->id]) {
                    $threadsWithNewMessages[] = $thread->id;
                }
            }
        }

        return $threadsWithNewMessages;
    }

    /**
     * Returns all threads with new messages.
     *
     * @return array
     */
    public function eventThreadsWithNewMessages($catId)
    {
        $threadsWithNewMessages = [];

        $participants = Models::evParticipant()->where('company_id', $this->company_id)
            ->whereIn('responsibility_id', array_pluck($this->responsibility, 'id'))
            ->whereIn('simulation_year_id', array_pluck(Helper::currentSimulation()->allFinishedYearsWithActiveYear()->toArray(), 'id'))
            ->whereNull('sim_messenger_ev_participants.deleted_by_user')
            ->threadCategory($catId)
            ->pluck('last_read', 'thread_id');

        /**
         * @todo: see if we can fix this more in the future.
         * Illuminate\Foundation is not available through composer, only in laravel/framework which
         * I don't want to include as a dependency for this package...it's overkill. So let's
         * exclude this check in the testing environment.
         */
        if (getenv('APP_ENV') == 'testing' || !str_contains(\Illuminate\Foundation\Application::VERSION, '5.0')) {
            $participants = $participants->all();
        }

        if ($participants) {
            $threads = Models::thread()->whereIn('id', array_keys($participants))->get();

            foreach ($threads as $thread) {
                if ($thread->updated_at > $participants[$thread->id]) {
                    $threadsWithNewMessages[] = $thread->id;
                }
            }
        }

        return $threadsWithNewMessages;
    }

    public function getEventParticipants()
    {
        return EventParticipant::where('company_id', $this->company_id)
            ->whereIn('responsibility_id', array_pluck($this->responsibility, 'id'))
            ->whereIn('simulation_year_id', array_pluck(Helper::currentSimulation()->allFinishedYearsWithActiveYear()->toArray(), 'id'))
            ->threadCategory('2')->orderBy('sim_messenger_ev_participants.id', 'desc');


    }

}