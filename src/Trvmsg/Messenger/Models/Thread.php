<?php

namespace Trvmsg\Messenger\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;

class Thread extends Eloquent
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'threads';

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['threads_category_id', 'subject', 'created_by', 'deleted_by_user'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'deleted_by_user'];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Models::table('threads');

        parent::__construct($attributes);
    }

    /**
     * Messages relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages()
    {
        return $this->hasMany(Models::classname(Message::class), 'thread_id', 'id');
    }

    /**
     * Messages relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventMessages()
    {
        return $this->hasMany(Models::classname(EventMessage::class), 'thread_id', 'id');
    }

    /**
     * Returns the latest message from a thread.
     *
     * @return \Trvmsg\Messenger\Models\Message
     */
    public function getLatestMessageAttribute()
    {
        return $this->messages()->latest()->first();
    }

    /**
     * Returns the latest message from a thread.
     *
     * @return \Trvmsg\Messenger\Models\Message
     */
    public function getLatestEventMessageAttribute()
    {
        return $this->eventMessages()->latest()->first();
    }

    /**
     * Participants relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function participants()
    {
        return $this->hasMany(Models::classname(Participant::class), 'thread_id', 'id');
    }

    /**
     * Participants relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function eventParticipants()
    {
        return $this->hasMany(Models::classname(EventParticipant::class), 'thread_id', 'id');
    }

    /**
     * Returns the user object that created the thread.
     *
     * @return mixed
     */
    public function creator()
    {
        return $this->messages()->oldest()->first()->user;
    }

    /**
     * Returns the user object that created the thread.
     *
     * @return mixed
     */
    public function eventCreator()
    {
        return $this->eventMessages()->oldest()->first()->user;
    }

    /**
     * Returns all of the latest threads by updated_at date.
     *
     * @return mixed
     */
    public static function getAllLatest()
    {
        return self::latest('updated_at');
    }

    /**
     * Returns all threads by subject.
     *
     * @return mixed
     */
    public static function getBySubject($subjectQuery)
    {
        return self::where('subject', 'like', $subjectQuery)->get();
    }

    /**
     * Returns an array of user ids that are associated with the thread.
     *
     * @param null $userId
     *
     * @return array
     */
    public function participantsUserIds($userId = null)
    {
        $users = $this->participants()->withTrashed()->pluck('user_id');

        if ($userId) {
            $users[] = $userId;
        }

        return $users;
    }

    /**
     * Returns threads that the user is associated with.
     *
     * @param $query
     * @param $userId
     *
     * @return mixed
     */
    public function scopeForUser($query, $userId)
    {
        $participantsTable = Models::table('participants');
        $threadsTable = Models::table('threads');

        return $query->join($participantsTable, $this->getQualifiedKeyName(), '=', $participantsTable . '.thread_id')
            ->where($participantsTable . '.user_id', $userId)
            ->where($participantsTable . '.deleted_at', null)
            ->select($threadsTable . '.*');
    }

    /**
     * Returns threads with new messages that the user is associated with.
     *
     * @param $query
     * @param $userId
     *
     * @return mixed
     */
    public function scopeForUserWithNewMessages($query, $userId)
    {
        $participantTable = Models::table('participants');
        $threadsTable = Models::table('threads');

        return $query->join($participantTable, $this->getQualifiedKeyName(), '=', $participantTable . '.thread_id')
            ->where($participantTable . '.user_id', $userId)
            ->whereNull($participantTable . '.deleted_at')
            ->where(function ($query) use ($participantTable, $threadsTable) {
                $query->where($threadsTable . '.updated_at', '>', $this->getConnection()->raw($this->getConnection()->getTablePrefix() . $participantTable . '.last_read'))
                    ->orWhereNull($participantTable . '.last_read');
            })
            ->select($threadsTable . '.*');
    }

    /**
     * Returns threads between given user ids.
     *
     * @param $query
     * @param $participants
     *
     * @return mixed
     */
    public function scopeBetween($query, array $participants)
    {
        $query->whereHas('participants', function ($query) use ($participants) {
            $query->whereIn('user_id', $participants)
                ->groupBy('thread_id')
                ->havingRaw('COUNT(thread_id)=' . count($participants));
        });
    }

    /**
     * Adds users to this thread.
     *
     * @param array $participants list of all participants
     */
    public function addParticipants(array $participants)
    {
        if (count($participants)) {
            foreach ($participants as $user_id) {
                Models::participant()->firstOrCreate([
                    'user_id' => $user_id,
                    'thread_id' => $this->id,
                ]);
            }
        }
    }

    /**
     * Mark a thread as read for a user.
     *
     * @param int $userId
     */
    public function markAsRead($userId)
    {
        try {
            $participant = $this->getParticipantFromUser($userId);
            $participant->last_read = new Carbon();
            $participant->save();
        } catch (ModelNotFoundException $e) {
            // do nothing
        }
    }

    /**
     * Mark a thread as read for a user.
     *
     * @param int $userId
     */
    public function markEventAsRead($participant)
    {
        try {
            $participant->last_read = new Carbon();
            $participant->save();
        } catch (ModelNotFoundException $e) {
            // do nothing
        }
    }

    /**
     * See if the current thread is unread by the user.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function isUnread($userId)
    {
        try {
            $participant = $this->getParticipantFromUser($userId);
            if ($this->updated_at > $participant->last_read) {
                return true;
            }
        } catch (ModelNotFoundException $e) {
            // do nothing
        }

        return false;
    }

    /**
     * See if the current thread is unread by the user.
     *
     * @param int $userId
     *
     * @return bool
     */
    public function isEventUnread($participant)
    {
        try {
            if ($this->updated_at > $participant->last_read) {
                return true;
            }
        } catch (ModelNotFoundException $e) {
            // do nothing
        }

        return false;
    }

    /**
     * Finds the participant record from a user id.
     *
     * @param $userId
     *
     * @return mixed
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getParticipantFromUser($userId)
    {
        return $this->participants()->where('user_id', $userId)->firstOrFail();
    }

    /**
     * Restores all participants within a thread that has a new message.
     */
    public function activateAllParticipants()
    {
        $participants = $this->participants()->withTrashed()->get();
        foreach ($participants as $participant) {
            $participant->restore();
        }
    }

    /**
     * Generates a string of participant information.
     *
     * @param null  $userId
     * @param array $columns
     *
     * @return string
     */
    public function participantsString($userId = null, $columns = ['first_name', 'last_name'])
    {
        $participantsTable = Models::table('participants');
        $usersTable = Models::table('users');

        $selectString = $this->createSelectString($columns);

        $participantNames = $this->getConnection()->table($usersTable)
            ->join($participantsTable, $usersTable . '.id', '=', $participantsTable . '.user_id')
            ->where($participantsTable . '.thread_id', $this->id)
            ->select($this->getConnection()->raw($selectString));

        if ($userId != null) {
            $participantNames->where($usersTable . '.id', '!=', $userId);
        }
        //dd($participantNames->get());
        $userNames = $participantNames->pluck($usersTable . '.name');
        //$userNames = $participantNames->pluck($usersTable . '.first_name' . ' ' . $usersTable . '.last_name');

        //return implode(', ', $userNames);
        return $userNames;
    }

    /**
     * Checks to see if a user is a current participant of the thread.
     *
     * @param $userId
     *
     * @return bool
     */
    public function hasParticipant($userId)
    {
        $participants = $this->participants()->where('user_id', '=', $userId);
        if ($participants->count() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Generates a select string used in participantsString().
     *
     * @param $columns
     *
     * @return string
     */
    protected function createSelectString($columns)
    {
        $dbDriver = $this->getConnection()->getDriverName();
        $tablePrefix = $this->getConnection()->getTablePrefix();
        $usersTable = Models::table('users');

        switch ($dbDriver) {
            case 'pgsql':
            case 'sqlite':
                $columnString = implode(" || ' ' || " . $tablePrefix . $usersTable . '.', $columns);
                $selectString = '(' . $tablePrefix . $usersTable . '.' . $columnString . ') as name';
                break;
            case 'sqlsrv':
                $columnString = implode(" + ' ' + " . $tablePrefix . $usersTable . '.', $columns);
                $selectString = '(' . $tablePrefix . $usersTable . '.' . $columnString . ') as name';
                break;
            default:
                $columnString = implode(", ' ', " . $tablePrefix . $usersTable . '.', $columns);
                $selectString = 'concat(' . $tablePrefix . $usersTable . '.' . $columnString . ') as name';
        }

        return $selectString;
    }
    /**
     * Returns array of unread messages in thread for given user.
     *
     * @param $userId
     *
     * @return \Illuminate\Support\Collection
     */
    public function userUnreadMessages($userId)
    {
        $messages = $this->messages()->get();
        $participant = $this->getParticipantFromUser($userId);
        if (!$participant) {
            return collect();
        }
        if (!$participant->last_read) {
            return collect($messages);
        }
        $unread = [];
        $i = count($messages) - 1;
        while ($i) {
            if ($messages[$i]->updated_at->gt($participant->last_read)) {
                array_push($unread, $messages[$i]);
            } else {
                break;
            }
            --$i;
        }

        return collect($unread);
    }

    /**
     * Returns count of unread messages in thread for given user.
     *
     * @param $userId
     *
     * @return int
     */
    public function userUnreadMessagesCount($userId)
    {
        return $this->userUnreadMessages($userId)->count();
    }

    public function userDraftMessages($userId = null)
    {
        if($userId == null){
            return $this->messages()->where('draft', 1)->get();
        }else{
            return $this->messages()->where('user_id', $userId)->where('draft', 1)->get();
        }
    }

    public function userDraftMessagesCount($userId)
    {
        return $this->userDraftMessages($userId)->count();
    }

    public function scopeCategory($query, $categoryId)
    {
        return $query->where('threads_category_id', $categoryId);
    }
}