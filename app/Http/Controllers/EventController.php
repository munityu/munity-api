<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Comment;
use App\Models\Notification;
use App\Filters\EventFilters;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class EventController extends Controller
{
    private $error_404 = [['message' => 'Event not found'], 404];
    private $error_403 = [['message' => 'Permission denied'], 403];
    private $min_set = ['title', 'description', 'format', 'theme', 'date', 'price', 'address'];

    public function __construct()
    {
        $this->user = JWTAuth::user(JWTAuth::getToken());
        $this->admin = $this->user ? $this->user->role == 'admin' : false;
    }

    public function index(Request $request)
    {
        $filters = new EventFilters($request);
        $query =  $filters->apply(Event::query())->where('pub_date', '<=', Carbon::now());
        if ($filters->page_num === null)
            $result = $query->get($this->min_set);
        else
            $result = $query->paginate($filters->per_page_num, $this->min_set, 'page', $filters->page_num);
        return $result;
    }

    public function store(Request $request)
    {
        if ($this->user->role == 'user')
            return response(...$this->error_403);
        $data = $request->all();
        if ($location = $data['location'])
            $data['location'] = Event::raw("ST_GeomFromText('POINT($location[0] $location[1])')");
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

        if (!$event->location)
            unset($event->location);
        else
            $event->location = [
                $event->location->getLat(),
                $event->location->getLng()
            ];
        return $event;
    }

    public function update(Request $request, int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $event->organizer->first()->id != $this->user->id)
            return response(...$this->error_403);
        $data = $request->all();
        if ($location = $data['location'])
            $data['location'] = Event::raw("ST_GeomFromText('POINT($location[0] $location[1])')");
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
        if ($event->nv_notifications)
            Notification::create([
                'event_id' => $event->id,
                'content' => 'New member! Greetings to ' . $this->user->name
            ]);
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

    public function getMembers(int $id)
    {
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && !$event->isMember($this->user->id) && !$event->public_visitors)
            return response(...$this->error_403);
        return $event->members;
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
