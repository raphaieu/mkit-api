<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CreatorLinkResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatorLinkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $links = $request->user()->creatorLinks()->orderBy('order')->get();

        return CreatorLinkResource::collection($links)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'emoji'       => ['nullable', 'string', 'max:10'],
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'url'         => ['required', 'url', 'max:500'],
            'order'       => ['nullable', 'integer', 'min:0'],
            'active'      => ['nullable', 'boolean'],
        ]);

        $link = $request->user()->creatorLinks()->create($data);

        return CreatorLinkResource::make($link)->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $link = $request->user()->creatorLinks()->findOrFail($id);

        $data = $request->validate([
            'emoji'       => ['nullable', 'string', 'max:10'],
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'url'         => ['sometimes', 'required', 'url', 'max:500'],
            'order'       => ['nullable', 'integer', 'min:0'],
            'active'      => ['nullable', 'boolean'],
        ]);

        $link->update($data);

        return CreatorLinkResource::make($link)->response();
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $user = $request->user();

        foreach ($data['ids'] as $index => $id) {
            $user->creatorLinks()->where('id', $id)->update(['order' => $index]);
        }

        return response()->json(null, 204);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->user()->creatorLinks()->findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
