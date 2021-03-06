<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Services\ImageService;
use App\Http\Traits\ResponseApi;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use ResponseApi;

    private ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }


    public function index()
    {
        return $this->success(User::all());
    }


    public function show(int $userId)
    {
        $authUser = Auth::user();
        $user = User::query()->withCount(['posts', 'followers', 'followed'])->find($userId);
//        $posts = Post::query()->where('author_id', '=', $userId)
//            ->withCount(['likes', 'comments'])->with('comments.author')->paginate(10);
        if($user){
            $user->setAttribute('isFollowed', $user->isFollowed($authUser->id));
//            foreach ($posts as $post){
//                $post->setAttribute('isLiked', $post->isLiked($user->id));
//            }
//            $user['posts'] = $posts;
            return $this->success($user);
        }
        return $this->failure('User not found', 404);
    }


    public function update(UpdateUserRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $response = [];
        if($data['avatar'] ?? false){
            if($user->cloud_id)
                $this->imageService->deleteImage($user->cloud_id);
            $cloudinaryResponse = $this->imageService->uploadImage($data['avatar']->getRealPath(), 'avatars');
            $user->avatar_url = $cloudinaryResponse['url'];
            $user->cloud_id = $cloudinaryResponse['cloud_id'];
            $response['avatar_url'] = $user->avatar_url;
        }
        if($data['name'] ?? false){
            $user->name = $data['name'];
            $response['name'] = $user->name;
        }
        $user->save();
        return $this->success($response);
    }


    public function destroy(int $userId)
    {
        $result = User::destroy($userId);
        if($result)
            return $this->success([],204);
        return $this->failure('User not found', 404);
    }


    public function follow(int $userId){
        $user = Auth::user();
        User::find($userId)->followers()->toggle($user->id);
    }

    public function createdPosts(int $userId){
        $result = Post::query()->where('author_id', '=', $userId)
            ->withCount(['likes', 'comments'])->with(['author', 'comments.author'])
            ->orderByDesc('created_at')->paginate(9);
        return $this->success($result);
    }


    public function likedPosts(int $userId){
        $result = User::query()->find($userId)->likedPosts;
        return $this->success($result);
    }


    public function followers(int $userId){
        $result = User::query()->find($userId)->followers;
        return $this->success($result);
    }


    public function followed(int $userId){
        $result = User::query()->find($userId)->followed;
        return $this->success($result);
    }
}
