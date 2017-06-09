<?php

namespace Trvmsg\Messenger\Models;

use App\User;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventParticipant extends Eloquent
{
    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'ev_participants';

    /**
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $fillable = ['thread_id', 'company_id', 'responsibility_id', 'simulation_year_id', 'last_read', 'deleted_by_user'];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at', 'updated_at', 'last_read', 'deleted_by_user'];

    /**
     * {@inheritDoc}
     */
    public function __construct(array $attributes = [])
    {
        $this->table = Models::table('ev_participants');

        parent::__construct($attributes);
    }

    /**
     * Thread relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function thread()
    {
        return $this->belongsTo(Models::classname(Thread::class), 'thread_id', 'id');
    }

    /**
     * Company relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function company()
    {
        return $this->belongsTo('App\Models\Sim\Company', 'company_id');
    }

    /**
     * Responsibility relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function responsability()
    {
        return $this->belongsTo('App\Models\Sim\Responsibility', 'responsibility_id');
    }

    /**
     * Simulation year relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function simulationYear()
    {
        return $this->belongsTo('App\Models\Sim\SimulationYear', 'company_id');
    }

    public function scopeThreadCategory($query, $categoryId)
    {
        return $query->join('sim_messenger_threads' , 'sim_messenger_threads.id', 'sim_messenger_ev_participants.thread_id')
            ->where('sim_messenger_threads.threads_category_id', $categoryId);
    }

}