<?php

namespace App\Http\Controllers;

use App\Http\Traits\ResponseApi;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;

class SearchController
{
    use ResponseApi;

    public function __invoke(Request $request)
    {
        $response = [];
        if($request->has('post')){
            $postsIds = Post::search($request->query('post'))->get()->pluck('id');
            $response['posts'] = Post::query()->whereIn('id', $postsIds)->with('author')
                ->get();
        }
        if($request->has('user'))
            $response['users']  = User::search($request->query('user'))->get();
        return $this->success($response);
    }
}
