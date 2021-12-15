<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UploadAvatarRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

class UserController extends Controller
{
    private $error404 = [['message' => 'User does not exist'], 404];
    private $error403 = [['message' => 'Permission denied'], 403];

    public function __construct()
    {
        $this->user = JWTAuth::user(JWTAuth::getToken());
        $this->admin = $this->user->role == 'admin';
    }

    public function index()
    {
        return User::all();
    }

    public function store(RegisterRequest $request)
    {
        return User::create($request->all());
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
        else {
            $user->location = [
                $user->location->getLat(),
                $user->location->getLng()
            ];
        }
        return $user;
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        if (!$user = User::find($id))
            return response(...$this->error404);

        if (!$this->admin && $this->user->id != $user->id)
            return response(...$this->error_403);

        $data = $request->all();
        if ($data['location']) {
            $data['location'] = new \Grimzy\LaravelMysqlSpatial\Types\Point($data['location'][0], $data['location'][1]);
        }

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

        $user->update($request->all());
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

    public function uploadAvatar(UploadAvatarRequest $request)
    {
        if ($request->file('image')) {
            $user = User::find($this->user->id);
            $uimage = substr($user->image, 46);

            if (\Illuminate\Support\Facades\Storage::disk('s3')->exists('weevely/' . $uimage) && !str_contains($uimage, 'weevely_H265P'))
                \Illuminate\Support\Facades\Storage::disk('s3')->delete('weevely/' . $uimage);

            $user->update([
                'image' => $image = "https://d3djy7pad2souj.cloudfront.net/weevely/" .
                    explode('/', $request->file('image')->storeAs('weevely', $user->id .
                        $request->file('image')->getClientOriginalName(), 's3'))[1]
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
}
