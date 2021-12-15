<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\UploadAvatarRequest;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

class UserController extends Controller
{
    private $error_404 = [['message' => 'User does not exist'], 404];
    private $error_403 = [['message' => 'Permission denied'], 403];

    public function __construct()
    {
        $this->user = auth()->user();
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
            return response(...$this->error_404);
        return $user;
    }

    public function me()
    {
        $data = $this->user;
        // FIXME Array to string conversion exception
        $data->location = unpack('x/x/x/x/corder/Ltype/dlat/dlon', $data->location);
        return $data;
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        if (!$user = User::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $this->user->id != $user->id)
            return response(...$this->error_403);
        $data = $request->all();
        if (isset($data['location']) && $location = $data['location'])
            $data['location'] = User::raw("ST_GeomFromText('POINT($location)')");
        return $user->update($data);
    }

    public function destroy(int $id)
    {
        if (!$user = User::find($id))
            return response(...$this->error_404);
        if (!$this->admin && $this->user->id != $user->id)
            return response(...$this->error_403);
        return $user->delete($id);
    }

    public function updateMe(UpdateUserRequest $request)
    {
        if (!$user = $this->user->id)
            return response(...$this->error_404);
        $data = $request->all();
        if (isset($data['location']) && $location = $data['location'])
            $data['location'] = User::raw("ST_GeomFromText('POINT($location)')");
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

    public function uploadAvatar(UploadAvatarRequest $request)
    {
        if ($request->file('image')) {
            $user = $this->user->id;
            $uimage = substr($user->image, 46);

            if (Storage::disk('s3')->exists('uevent/' . $uimage) && !str_contains($uimage, 'uevent_H265P'))
                Storage::disk('s3')->delete('uevent/' . $uimage);

            $user->update([
                'image' => $image = "https://d3djy7pad2souj.cloudfront.net/uevent/" .
                    explode('/', $request->file('image')->storeAs('uevent', $user->id .
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
