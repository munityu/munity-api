<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UploadImageRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

class UserController extends Controller
{
    private $error404 = [['message' => 'User does not exist'], 404];
    private $error403 = [['message' => 'Permission denied'], 403];

    public function __construct()
    {
        $this->user = JWTAuth::user(JWTAuth::getToken());
        $this->admin = $this->user ? $this->user->role == 'admin' : false;
    }

    public function index()
    {
        return User::all();
    }

    public function store(RegisterRequest $request)
    {
        $data = $request->all();
        if ($location = $data['location'])
            $data['location'] = User::raw("ST_GeomFromText('POINT($location[0] $location[1])')");
        return User::create($data);
    }

    public function show(int|string $id)
    {
        $user = is_numeric($id) ? User::find($id) : User::where('name', $id)->first();
        if (!$user)
            return response(...$this->error404);

        return $user;
    }

    public function me()
    {
        if (!$user = User::find($this->user->id))
            return response(...$this->error404);

        if (!$user->location)
            unset($user->location);
        else
            $user->location = [
                $user->location->getLat(),
                $user->location->getLng()
            ];
        return $user;
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        if (!$user = User::find($id))
            return response(...$this->error404);

        if (!$this->admin && $this->user->id != $user->id)
            return response(...$this->error_403);

        $data = $request->all();
        if ($location = $data['location'])
            $data['location'] = User::raw("ST_GeomFromText('POINT($location[0] $location[1])')");

        return $user->update($data);
    }

    public function destroy(int $id)
    {
        if (!$user = User::find($id))
            return response(...$this->error404);

        if (!$this->admin && $this->user->id != $user->id)
            return response(...$this->error403);

        return $user->delete($id);
    }

    public function updateMe(UpdateUserRequest $request)
    {
        if (!$user = User::find($this->user->id))
            return response(...$this->error404);

        $data = $request->all();
        if ($location = $data['location'])
            $data['location'] = User::raw("ST_GeomFromText('POINT($location[0] $location[1])')");
        $user->update($data);
        return response([
            "message" => "Successfully updated.",
            'cookie' => json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $user->image,
                'token' => $request->header('Authorization'),
                'ttl' => JWTAuth::factory()->getTTL() * 60
            ])
        ])->withCookie(cookie('user', json_encode([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'image' => $user->image,
            'token' => $request->header('Authorization')
        ]), JWTAuth::factory()->getTTL()));
    }

    public function uploadAvatar(UploadImageRequest $request)
    {
        if ($request->file('image')) {
            $user = User::find($this->user->id);
            $uimage = substr($user->image, 53);

            if (\Illuminate\Support\Facades\Storage::disk('s3')->exists('munity/' . $uimage) && !str_contains($uimage, 'munity_H265P'))
                \Illuminate\Support\Facades\Storage::disk('s3')->delete('munity/' . $uimage);

            $user->update([
                'image' => $image = "https://d3djy7pad2souj.cloudfront.net/munity/avatars/" .
                    explode('/', $request->file('image')->storeAs('munity/avatars', $user->id .
                        $request->file('image')->getClientOriginalName(), 's3'))[2]
            ]);

            return response([
                "message" => "Your avatar was uploaded.",
                'cookie' => json_encode([
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'image' => $image,
                    'token' => $request->header('Authorization'),
                    'ttl' => JWTAuth::factory()->getTTL() * 60
                ])
            ], 201)->withCookie(cookie('user', json_encode([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'image' => $image,
                'token' => $request->header('Authorization')
            ]), JWTAuth::factory()->getTTL()));
        }
    }

    public function getEvents(int $id)
    {
        if (!$user = User::find($id))
            return response(...$this->error404);
        if (!$this->admin && $this->user->id != $user->id)
            return response(...$this->error403);
        return $user->events;
    }
}
