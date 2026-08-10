<?php

$legacyMaxMediaSizeMb = (int) env('ADVERTISEMENT_MAX_MEDIA_SIZE_MB', 50);

return [
    'max_image_size_mb' => (int) env('ADVERTISEMENT_MAX_IMAGE_SIZE_MB', $legacyMaxMediaSizeMb),
    'max_video_size_mb' => (int) env('ADVERTISEMENT_MAX_VIDEO_SIZE_MB', 500),
];
