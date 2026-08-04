<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;

class AdvertisementController extends Controller
{
    public function index()
    {
        $advertisements = Advertisement::query()
            ->published()
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (Advertisement $advertisement) => [
                'id' => $advertisement->id,
                'title' => $advertisement->title,
                'description' => $advertisement->description,
                'mediaType' => $advertisement->media_type,
                'mediaUrl' => asset('storage/'.$advertisement->image_path),
                'imageUrl' => $advertisement->media_type === 'image'
                    ? asset('storage/'.$advertisement->image_path)
                    : null,
                'startsAt' => $advertisement->starts_at?->toIso8601String(),
                'endsAt' => $advertisement->ends_at?->toIso8601String(),
            ]);

        return response()->json([
            'data' => $advertisements,
            'refreshAfterSeconds' => 60,
        ]);
    }
}
