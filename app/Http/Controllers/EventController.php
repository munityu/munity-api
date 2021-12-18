<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EventController extends Controller
{
    private $error_404 = [['message' => 'Event not found'], 404];
    private $error_403 = [['message' => 'Permission denied'], 403];
    private $error_400 = [['message' => 'Image file required'], 400];

    public function __construct()
    {
        $this->user = JWTAuth::user(JWTAuth::getToken());
        $this->admin = $this->user ? $this->user->role == 'admin' : false;
    }

    public function index(Request $request)
    {
        $filters = new \App\Filters\EventFilters($request);
        $query =  $filters->apply(Event::query())->with(['members', 'comments', 'organizer'])->where('pub_date', '<=', \Carbon\Carbon::now()->addHour())->where('date', '>=', \Carbon\Carbon::now()->addHour());
        if ($filters->page_num === null)
            $result = $query->get();
        else
            $result = $query->paginate($filters->per_page_num, ['*'], 'page', $filters->page_num);
        return $result;
    }

    public function store(Request $request)
    {
        $data = $request->all();
        if ($location = $data['location'])
            $data['location'] = Event::raw("ST_GeomFromText('POINT($location[0] $location[1])')");
        $data['date'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $data['date']);

        $event = Event::create($data);
        $event->members()->attach($this->user->id, ['organizer' => true]);
        return $event;
    }

    public function show(int $id)
    {
        if (!$event = Event::with(['comments', 'members', 'organizer'])->find($id))
            return response(...$this->error_404);
        foreach ($event->comments as $comment)
            $comment->user = \App\Models\User::find($comment->user_id);

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

    public function uploadPoster(\App\Http\Requests\UploadImageRequest $request, int $id)
    {
        if (!$request->file('image'))
            return response(...$this->error_400);
        if (!$event = Event::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $event->organizer->first()->id != $this->user->id)
            return response(...$this->error_403);
        $pimage = $event->poster ? substr($event->poster, 53) : null;

        if ($pimage && \Illuminate\Support\Facades\Storage::disk('s3')->exists('munity/posters/' . $pimage))
            \Illuminate\Support\Facades\Storage::disk('s3')->delete('munity/posters/' . $pimage);

        $posterUrl = "https://d3djy7pad2souj.cloudfront.net/munity/posters/" .
            explode('/', $request->file('image')->storeAs('munity/posters', $event->id .
                $request->file('image')->getClientOriginalName(), 's3'))[2];

        $event->update([
            'poster' => $posterUrl
        ]);

        return response(['message' => 'Poster successfully uploaded!', 'poster' => $posterUrl]);
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
        if ($event->isMember($this->user->id))
            return response(...$this->error_403);
        $event->members()->attach($this->user->id);
        if ($event->nv_notifications)
            \App\Models\Notification::create([
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
        $comment = \App\Models\Comment::create($data);
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
        $notify = \App\Models\Notification::create($data);
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
