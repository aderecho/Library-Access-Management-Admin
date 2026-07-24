<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;

class AdvertisementController extends Controller
{
    public function index()
    {
        $advertisements = Advertisement::query()
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Advertisement $advertisement) => [
                'id' => $advertisement->id,
                'title' => $advertisement->title,
                'description' => $advertisement->description,
                'imageUrl' => asset('storage/'.$advertisement->image_path),
                'startsAt' => $advertisement->starts_at?->toIso8601String(),
                'endsAt' => $advertisement->ends_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $advertisements,
            'refreshAfterSeconds' => 60,
        ]);
    }
}
