<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use App\Models\Comment;
use App\Models\Notification;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EventController extends Controller
{
    private $error_404 = [['message' => 'Event not found'], 404];
    private $error_403 = [['message' => 'Permission denied'], 403];

    public function __construct()
    {
        $this->user = JWTAuth::user(JWTAuth::getToken());
        $this->admin = $this->user->role == 'admin';
    }

    public function index()
    {
        return Event::all();
    }

    public function store(Request $request)
    {
        if ($this->user->role == 'user')
            return response(...$this->error_403);
        $data = $request->all();
        if (isset($data['location']) && $location = $data['location'])
            $data['location'] = User::raw("ST_GeomFromText('POINT($location)')");
        $event = Event::create($data);
        $event->members()->attach($this->user->id, ['organizer' => true]);
        return $event;
    }

    public function show(int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && !$event->isMember($this->user->id))
            return response(...$this->error_403);
        // FIXME Array to string conversion exception
        $event->location = unpack('x/x/x/x/corder/Ltype/dlat/dlon', $event->location);
        return $event;
    }

    public function update(Request $request, int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $event->organizer->first()->id != $this->user->id)
            return response(...$this->error_403);
        $data = $request->all();
        if (isset($data['location']) && $location = $data['location'])
            $data['location'] = User::raw("ST_GeomFromText('POINT($location)')");
        return $event->update($data);
    }

    public function destroy(int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $event->organizer->first()->id != $this->user->id)
            return response(...$this->error_403);
        return $event->delete($id);
    }

    public function subscribe(int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$event->isMember($this->user->id))
            return response(...$this->error_403);
        $event->members()->attach($this->user->id);
        return $event;
    }

    public function createComment(Request $request, int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$event->isMember($this->user->id))
            return response(...$this->error_403);
        $data = $request->all();
        $data['user_id'] = $this->user->id;
        $data['event_id'] = $id;
        $comment = Comment::create($data);
        return $comment;
    }

    public function createNotification(Request $request, int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && !$event->organizer()->first()->id != $this->user->id)
            return response(...$this->error_403);
        $data = $request->all();
        $data['event_id'] = $id;
        $notify = Notification::create($data);
        return $notify;
    }

    public function getComments(int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && !$event->isMember($this->user->id))
            return response(...$this->error_403);
        return $event->comments;
    }

    public function getNotifications(int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && !$event->isMember($this->user->id))
            return response(...$this->error_403);
        return $event->notifications;
    }
}
