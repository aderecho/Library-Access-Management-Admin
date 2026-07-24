@extends('layouts.admin', ['heading' => 'Advertisements', 'title' => 'Advertisements | UP Cebu RFID'])

@section('content')
<div class="advertisement-workspace">
    @if(auth()->user()->hasPermission('advertisements.create'))
        <section class="advertisement-composer" aria-labelledby="add-advertisement-heading">
            <div class="advertisement-section-heading">
                <h2 id="add-advertisement-heading">Add Advertisement</h2>
                <p>Create an image announcement and choose when it should be displayed.</p>
            </div>

            <form method="post" action="{{ route('admin.advertisements.store') }}" enctype="multipart/form-data" class="advertisement-form">
                @csrf
                <label>Title
                    <input type="text" name="title" value="{{ old('title') }}" maxlength="120" placeholder="Enter advertisement title" required>
                </label>
                <label>Description
                    <textarea name="description" maxlength="500" rows="3" placeholder="Enter a short description">{{ old('description') }}</textarea>
                </label>
                <div class="advertisement-dates">
                    <label>Display from<input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"></label>
                    <label>Display until<input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"></label>
                </div>

                <label class="advertisement-image-label">Advertisement image</label>
                <label class="advertisement-dropzone" data-ad-dropzone>
                    <input class="sr-only" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-ad-image-input required>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 15v4h14v-4"/></svg>
                    <strong data-ad-file-label>Drop an image here or browse</strong>
                    <span>JPG, PNG or WebP · Maximum 5 MB</span>
                    <span class="advertisement-browse">Browse image</span>
                </label>

                <div class="advertisement-preview" data-ad-preview-wrap hidden>
                    <span>Image preview</span>
                    <img data-ad-preview alt="Selected advertisement preview">
                </div>

                <button class="advertisement-publish" type="submit">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 18-8-8 18-2-8-8-2Zm8 2 4-4"/></svg>
                    Publish advertisement
                </button>
            </form>
        </section>
    @endif

    <section class="advertisement-library" aria-labelledby="published-advertisements-heading">
        <div class="advertisement-section-heading">
            <h2 id="published-advertisements-heading">Published Advertisements</h2>
            <p>{{ $advertisements->total() }} {{ Str::plural('advertisement', $advertisements->total()) }}</p>
        </div>

        <div class="advertisement-list">
            @forelse($advertisements as $advertisement)
                @php($status = $advertisement->displayStatus())
                <article class="advertisement-item">
                    <img src="{{ asset('storage/'.$advertisement->image_path) }}" alt="{{ $advertisement->title }}">
                    <div class="advertisement-item-content">
                        <div class="advertisement-title-line">
                            <h3>{{ $advertisement->title }}</h3>
                            <span class="advertisement-status {{ strtolower($status) }}">{{ $status }}</span>
                        </div>
                        @if($advertisement->description)<p>{{ $advertisement->description }}</p>@endif
                        <dl class="advertisement-schedule">
                            <div><dt>Display from</dt><dd>{{ $advertisement->starts_at?->format('M j, Y · g:i A') ?: 'Immediately' }}</dd></div>
                            <div><dt>Display until</dt><dd>{{ $advertisement->ends_at?->format('M j, Y · g:i A') ?: 'No end date' }}</dd></div>
                        </dl>
                        <small>Published {{ $advertisement->created_at?->diffForHumans() }}@if($advertisement->creator) by {{ $advertisement->creator->name }}@endif</small>
                    </div>
                </article>
            @empty
                <div class="advertisement-empty">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM8 14l2-2 2 2 3-3 3 3"/></svg>
                    <h3>No advertisements yet</h3>
                    <p>Upload the first image advertisement using the form.</p>
                </div>
            @endforelse
        </div>

        {{ $advertisements->links() }}
    </section>
</div>
@endsection
