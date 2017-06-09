<?php

namespace Trvmsg\Messenger\Test;

use Carbon\Carbon;
use Trvmsg\Messenger\Models\Thread;
use Trvmsg\Messenger\Traits\Messagable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class MessagableTraitTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
    }

    /** @test */
    public function it_should_get_all_threads_with_new_messages()
    {
        $user = User::create(
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'notify' => 'y',
            ]
        );

        $thread = $this->faktory->create('thread');
        $user_1 = $this->faktory->build('participant', ['user_id' => $user->id, 'last_read' => Carbon::yesterday()]);
        $user_2 = $this->faktory->build('participant', ['user_id' => 2]);
        $thread->participants()->saveMany([$user_1, $user_2]);

        $message_1 = $this->faktory->build('message', ['user_id' => 2]);
        $thread->messages()->saveMany([$message_1]);

        $thread2 = $this->faktory->create('thread');
        $user_1b = $this->faktory->build('participant', ['user_id' => 3, 'last_read' => Carbon::yesterday()]);
        $user_2b = $this->faktory->build('participant', ['user_id' => 2]);
        $thread2->participants()->saveMany([$user_1b, $user_2b]);

        $message_1b = $this->faktory->build('message', ['user_id' => 2]);
        $thread2->messages()->saveMany([$message_1b]);

        $threads = $user->threadsWithNewMessages();
        $this->assertEquals(1, $threads->first()->id);

        $this->assertEquals(1, $user->newThreadsCount());
    }

    /** @test */
    public function it_should_get_participant_threads()
    {
        $user = User::create(
            [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ]
        );
        $thread = $this->faktory->create('thread');
        $user_1 = $this->faktory->build('participant', ['user_id' => $user->id]);
        $user_2 = $this->faktory->build('participant', ['user_id' => 2]);
        $thread->participants()->saveMany([$user_1, $user_2]);

        $firstThread = $user->threads->first();
        $this->assertInstanceOf(Thread::class, $firstThread);
    }
}

class User extends Eloquent
{
    use Messagable;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'notify'];
}
