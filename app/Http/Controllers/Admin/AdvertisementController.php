<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdvertisementController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->string('status', 'published')->lower()->toString();
        $status = in_array($status, ['published', 'scheduled', 'expired'], true)
            ? $status
            : 'published';

        $statusCounts = [
            'published' => Advertisement::published()->count(),
            'scheduled' => Advertisement::scheduled()->count(),
            'expired' => Advertisement::expired()->count(),
        ];

        $advertisements = match ($status) {
            'scheduled' => Advertisement::scheduled(),
            'expired' => Advertisement::expired(),
            default => Advertisement::published(),
        };

        $advertisements = $advertisements->with('creator')
            ->latest()
            ->paginate(6)
            ->withQueryString();

        return view('admin.advertisements.index', compact('advertisements', 'status', 'statusCounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->advertisementRules(mediaRequired: true));

        $validated['created_by'] = $request->user()->id;
        $validated['media_type'] = str_starts_with($request->file('media')->getMimeType(), 'video/')
            ? 'video'
            : 'image';
        $validated['image_path'] = $request->file('media')->store('advertisements', 'public');
        unset($validated['media']);

        Advertisement::create($validated);

        return redirect()->route('admin.advertisements.index')
            ->with('success', 'Advertisement published successfully.');
    }

    public function update(Request $request, Advertisement $advertisement)
    {
        $validated = $request->validate($this->advertisementRules(mediaRequired: false));
        $oldMediaPath = null;

        if ($request->hasFile('media')) {
            $oldMediaPath = $advertisement->image_path;
            $validated['media_type'] = str_starts_with($request->file('media')->getMimeType(), 'video/')
                ? 'video'
                : 'image';
            $validated['image_path'] = $request->file('media')->store('advertisements', 'public');
        }

        unset($validated['media']);
        $advertisement->update($validated);

        if ($oldMediaPath && $oldMediaPath !== $advertisement->image_path) {
            Storage::disk('public')->delete($oldMediaPath);
        }

        $status = in_array($request->string('return_status')->toString(), ['published', 'scheduled', 'expired'], true)
            ? $request->string('return_status')->toString()
            : 'published';

        return redirect()->route('admin.advertisements.index', ['status' => $status])
            ->with('success', 'Advertisement updated successfully.');
    }

    private function advertisementRules(bool $mediaRequired): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'media' => [
                $mediaRequired ? 'required' : 'nullable',
                'file',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm',
                'max:51200',
            ],
        ];
    }
}
