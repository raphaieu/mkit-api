<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('instagramProfile');

        return UserResource::make($user)->response();
    }

    public function update(Request $request): JsonResponse
    {
        // TODO: implement — PUT /api/me
        abort(501);
    }
}
