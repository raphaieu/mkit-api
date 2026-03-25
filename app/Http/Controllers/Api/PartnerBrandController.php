<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PartnerBrandResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerBrandController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $brands = $request->user()->partnerBrands()->orderBy('order')->get();

        return PartnerBrandResource::collection($brands)->response();
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'emoji'    => ['nullable', 'string', 'max:10'],
            'order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $brand = $request->user()->partnerBrands()->create($data);

        return PartnerBrandResource::make($brand)->response()->setStatusCode(201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $brand = $request->user()->partnerBrands()->findOrFail($id);

        $data = $request->validate([
            'name'     => ['sometimes', 'required', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'emoji'    => ['nullable', 'string', 'max:10'],
            'order'    => ['nullable', 'integer', 'min:0'],
        ]);

        $brand->update($data);

        return PartnerBrandResource::make($brand)->response();
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $user = $request->user();

        foreach ($data['ids'] as $index => $id) {
            $user->partnerBrands()->where('id', $id)->update(['order' => $index]);
        }

        return response()->json(null, 204);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $request->user()->partnerBrands()->findOrFail($id)->delete();

        return response()->json(null, 204);
    }
}
