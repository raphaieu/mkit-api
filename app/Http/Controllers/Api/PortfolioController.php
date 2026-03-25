<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfolioPostResource;
use App\Models\PortfolioPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $posts = $request->user()
            ->portfolioPosts()
            ->orderBy('order')
            ->get();

        return PortfolioPostResource::collection($posts)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'                => ['required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'image_url'            => ['nullable', 'url', 'max:500'],
            'partner_name'         => ['nullable', 'string', 'max:255'],
            'collaboration_type'   => ['nullable', 'string', 'max:100'],
            'reach'                => ['nullable', 'string', 'max:50'],
            'engagement_rate_text' => ['nullable', 'string', 'max:50'],
            'deliverables'         => ['nullable', 'string', 'max:255'],
            'published_at'         => ['nullable', 'date'],
            'order'                => ['nullable', 'integer', 'min:0'],
        ]);

        $post = $request->user()->portfolioPosts()->create($data);

        return PortfolioPostResource::make($post)->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $post = $request->user()->portfolioPosts()->findOrFail($id);

        $data = $request->validate([
            'title'                => ['sometimes', 'required', 'string', 'max:255'],
            'description'          => ['nullable', 'string'],
            'image_url'            => ['nullable', 'url', 'max:500'],
            'partner_name'         => ['nullable', 'string', 'max:255'],
            'collaboration_type'   => ['nullable', 'string', 'max:100'],
            'reach'                => ['nullable', 'string', 'max:50'],
            'engagement_rate_text' => ['nullable', 'string', 'max:50'],
            'deliverables'         => ['nullable', 'string', 'max:255'],
            'published_at'         => ['nullable', 'date'],
            'order'                => ['nullable', 'integer', 'min:0'],
        ]);

        $post->update($data);

        return PortfolioPostResource::make($post)->response();
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->user()->portfolioPosts()->findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
