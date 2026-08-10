@extends('layouts.admin', ['heading' => 'Advertisements', 'title' => 'Advertisements | UP Cebu RFID'])

@section('content')
<div class="advertisement-workspace">
    @if(auth()->user()->hasPermission('advertisements.create'))
        <section class="advertisement-composer" aria-labelledby="add-advertisement-heading">
            <div class="advertisement-section-heading">
                <h2 id="add-advertisement-heading">Publish advertisement</h2>
                <p>Create an image or video announcement and choose when it should be displayed.</p>
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

                <label class="advertisement-image-label">Advertisement media</label>
                <label class="advertisement-dropzone" data-ad-dropzone>
                    <input class="sr-only" type="file" name="media"
                           accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                           data-max-image-bytes="{{ $maxImageSizeMb * 1024 * 1024 }}"
                           data-max-video-bytes="{{ $maxVideoSizeMb * 1024 * 1024 }}"
                           data-ad-media-input required>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 15v4h14v-4"/></svg>
                    <strong data-ad-file-label>Drop an image or video here, or browse</strong>
                    <span>JPG, PNG or WebP up to {{ $maxImageSizeMb }} MB · MP4 or WebM up to {{ $maxVideoSizeMb }} MB</span>
                    <span class="advertisement-browse">Browse media</span>
                </label>
                <p class="advertisement-media-warning" data-ad-media-warning role="alert" hidden></p>

                <div class="advertisement-preview" data-ad-preview-wrap hidden>
                    <span>Media preview</span>
                    <img data-ad-image-preview alt="Selected advertisement preview" hidden>
                    <video data-ad-video-preview controls muted playsinline hidden></video>
                </div>

                <button class="advertisement-publish" type="submit">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 18-8-8 18-2-8-8-2Zm8 2 4-4"/></svg>
                    Publish advertisement
                </button>
            </form>
        </section>
    @endif

    <section class="advertisement-library" aria-labelledby="advertisement-library-heading">
        <div class="advertisement-section-heading">
            <h2 id="advertisement-library-heading">Advertisement library</h2>
            <p>Review advertisements by their display lifecycle.</p>
        </div>

        <nav class="advertisement-tabs" aria-label="Advertisement status">
            @foreach(['published' => 'Published', 'scheduled' => 'Scheduled', 'expired' => 'Expired'] as $tab => $label)
                <a
                    href="{{ route('admin.advertisements.index', ['status' => $tab]) }}"
                    class="advertisement-tab {{ $status === $tab ? 'active' : '' }}"
                    @if($status === $tab) aria-current="page" @endif
                >
                    <span>{{ $label }}</span>
                    <strong>{{ number_format($statusCounts[$tab]) }}</strong>
                </a>
            @endforeach
        </nav>

        <div class="advertisement-library-summary">
            <h3>{{ ucfirst($status) }} advertisements</h3>
            <span>{{ $advertisements->total() }} {{ Str::plural('item', $advertisements->total()) }}</span>
        </div>

        <div class="advertisement-list">
            @forelse($advertisements as $advertisement)
                @php($displayStatus = $advertisement->displayStatus())
                <article class="advertisement-item">
                    @if($advertisement->media_type === 'video')
                        <video src="{{ asset('storage/'.$advertisement->image_path) }}" controls muted preload="metadata" playsinline></video>
                    @else
                        <img src="{{ asset('storage/'.$advertisement->image_path) }}" alt="{{ $advertisement->title }}">
                    @endif
                    <div class="advertisement-item-content">
                        <div class="advertisement-title-line">
                            <h3>{{ $advertisement->title }}</h3>
                            <div class="advertisement-card-actions">
                                <span class="advertisement-status {{ strtolower($displayStatus) }}">{{ $displayStatus }}</span>
                                @if(auth()->user()->hasPermission('advertisements.create'))
                                    <button
                                        type="button"
                                        class="advertisement-edit-button"
                                        data-edit-advertisement
                                        data-update-url="{{ route('admin.advertisements.update', $advertisement) }}"
                                        data-delete-url="{{ route('admin.advertisements.destroy', $advertisement) }}"
                                        data-title="{{ $advertisement->title }}"
                                        data-description="{{ $advertisement->description }}"
                                        data-starts-at="{{ $advertisement->starts_at?->format('Y-m-d\TH:i') }}"
                                        data-ends-at="{{ $advertisement->ends_at?->format('Y-m-d\TH:i') }}"
                                        data-media-type="{{ $advertisement->media_type }}"
                                        data-media-url="{{ asset('storage/'.$advertisement->image_path) }}"
                                    >Edit</button>
                                @endif
                            </div>
                        </div>
                        @if($advertisement->description)<p>{{ $advertisement->description }}</p>@endif
                        <dl class="advertisement-schedule">
                            <div><dt>Display from</dt><dd>{{ $advertisement->starts_at?->format('M j, Y · g:i A') ?: 'Immediately' }}</dd></div>
                            <div><dt>Display until</dt><dd>{{ $advertisement->ends_at?->format('M j, Y · g:i A') ?: 'No end date' }}</dd></div>
                        </dl>
                        <small>Created {{ $advertisement->created_at?->diffForHumans() }}@if($advertisement->creator) by {{ $advertisement->creator->name }}@endif</small>
                    </div>
                </article>
            @empty
                <div class="advertisement-empty">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4zM8 14l2-2 2 2 3-3 3 3"/></svg>
                    <h3>No {{ $status }} advertisements</h3>
                    <p>{{ $status === 'published' ? 'Publish a new advertisement or check the other lifecycle groups.' : 'There are no advertisements in this lifecycle group.' }}</p>
                </div>
            @endforelse
        </div>

        {{ $advertisements->links() }}
    </section>
</div>

@if(auth()->user()->hasPermission('advertisements.create'))
    <dialog class="advertisement-edit-dialog" data-ad-edit-dialog>
        <form method="post" enctype="multipart/form-data" class="advertisement-edit-form" data-ad-edit-form>
            @csrf
            @method('put')
            <input type="hidden" name="return_status" value="{{ $status }}">

            <div class="advertisement-edit-heading">
                <div>
                    <span>Edit advertisement</span>
                    <h2 data-ad-edit-heading>Advertisement details</h2>
                </div>
                <button type="button" class="advertisement-edit-close" data-ad-edit-close aria-label="Close edit advertisement">×</button>
            </div>

            <div class="advertisement-edit-fields">
                <label>Title
                    <input type="text" name="title" maxlength="120" required data-ad-edit-title>
                </label>
                <label>Description
                    <textarea name="description" maxlength="500" rows="3" data-ad-edit-description></textarea>
                </label>
                <div class="advertisement-dates">
                    <label>Display from<input type="datetime-local" name="starts_at" data-ad-edit-starts-at></label>
                    <label>Display until<input type="datetime-local" name="ends_at" data-ad-edit-ends-at></label>
                </div>
                <label>Replace media <small>Optional · leave empty to keep the current image or video</small>
                    <input type="file" name="media" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                           data-max-image-bytes="{{ $maxImageSizeMb * 1024 * 1024 }}"
                           data-max-video-bytes="{{ $maxVideoSizeMb * 1024 * 1024 }}" data-ad-edit-media>
                </label>
                <p class="advertisement-media-warning" data-ad-edit-media-warning role="alert" hidden></p>
                <div class="advertisement-edit-preview">
                    <span>Current media</span>
                    <img data-ad-edit-image-preview alt="Advertisement media preview" hidden>
                    <video data-ad-edit-video-preview controls muted playsinline hidden></video>
                </div>
            </div>

            <div class="advertisement-edit-actions">
                <button type="button" class="advertisement-edit-delete" data-ad-edit-delete>Delete advertisement</button>
                <div class="advertisement-edit-actions-primary">
                    <button type="button" class="advertisement-edit-cancel" data-ad-edit-cancel>Cancel</button>
                    <button type="submit" class="advertisement-edit-save">Save changes</button>
                </div>
            </div>
        </form>

    </dialog>

    <dialog class="advertisement-delete-dialog" data-ad-delete-dialog aria-labelledby="advertisement-delete-title" aria-describedby="advertisement-delete-description">
        <form method="post" class="advertisement-delete-form" data-ad-delete-form>
            @csrf
            @method('delete')
            <input type="hidden" name="return_status" value="{{ $status }}">

            <div class="advertisement-delete-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5"/></svg>
            </div>
            <div class="advertisement-delete-copy">
                <span>Delete advertisement</span>
                <h2 id="advertisement-delete-title">Are you sure?</h2>
                <p id="advertisement-delete-description">
                    <strong data-ad-delete-name></strong> will be permanently removed, including its uploaded media.
                </p>
            </div>
            <div class="advertisement-delete-actions">
                <button type="button" class="advertisement-delete-keep" data-ad-delete-cancel>Keep advertisement</button>
                <button type="submit" class="advertisement-delete-confirm">Yes, delete</button>
            </div>
        </form>
    </dialog>
@endif
@endsection
