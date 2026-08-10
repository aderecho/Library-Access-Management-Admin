# Advertisement upload limits

The application accepts advertisement images up to 50 MB and MP4/WebM videos up to 500 MB by default.

Set the Laravel limits in `.env`:

```dotenv
ADVERTISEMENT_MAX_IMAGE_SIZE_MB=50
ADVERTISEMENT_MAX_VIDEO_SIZE_MB=500
```

Laravel cannot receive a 500 MB upload unless PHP-FPM and Nginx allow the request first.

For PHP 8.3 FPM, create `/etc/php/8.3/fpm/conf.d/99-up-cebu-rfid-uploads.ini`:

```ini
upload_max_filesize=500M
post_max_size=525M
max_execution_time=300
max_input_time=300
```

In the application's Nginx `server` block, set:

```nginx
client_max_body_size 525M;
```

Apply and verify the production settings:

```bash
sudo systemctl restart php8.3-fpm
sudo nginx -t
sudo systemctl reload nginx
php artisan optimize:clear
php -i | grep -E 'upload_max_filesize|post_max_size'
sudo nginx -T | grep client_max_body_size
```
