from create_deployment_guide import (
    Document, Inches, Pt, WD_ALIGN_PARAGRAPH, OxmlElement, qn,
    BLUE, DARK_BLUE, INK, MUTED, LIGHT_GRAY,
    set_font, add_code, add_note, add_bullet, add_check, add_step,
    start_step_sequence, add_table, add_field,
)
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "docs" / "UP_Cebu_RFID_Admin_Ubuntu_Server_Deployment_Guide.docx"

doc = Document()
section = doc.sections[0]
for attr in ("top_margin", "bottom_margin", "left_margin", "right_margin"):
    setattr(section, attr, Inches(1))
section.header_distance = Inches(0.492)
section.footer_distance = Inches(0.492)

normal = doc.styles["Normal"]
normal.font.name = "Calibri"
normal._element.rPr.rFonts.set(qn("w:ascii"), "Calibri")
normal._element.rPr.rFonts.set(qn("w:hAnsi"), "Calibri")
normal.font.size = Pt(11)
normal.paragraph_format.space_after = Pt(6)
normal.paragraph_format.line_spacing = 1.25
for name, size, color, before, after in (
    ("Heading 1", 16, BLUE, 18, 10),
    ("Heading 2", 13, BLUE, 14, 7),
    ("Heading 3", 12, DARK_BLUE, 10, 5),
):
    s = doc.styles[name]
    s.font.name = "Calibri"
    s.font.size = Pt(size)
    s.font.bold = True
    s.font.color.rgb = color
    s.paragraph_format.space_before = Pt(before)
    s.paragraph_format.space_after = Pt(after)
    s.paragraph_format.keep_with_next = True
for name in ("List Bullet", "List Bullet 2", "List Number"):
    s = doc.styles[name]
    s.font.name = "Calibri"
    s.font.size = Pt(11)
    s.paragraph_format.space_after = Pt(4)
    s.paragraph_format.line_spacing = 1.25
checklist = doc.styles.add_style("Checklist", 1)
checklist.font.name = "Calibri"
checklist.font.size = Pt(11)
checklist.paragraph_format.left_indent = Inches(0.375)
checklist.paragraph_format.first_line_indent = Inches(-0.188)
checklist.paragraph_format.space_after = Pt(4)
code_style = doc.styles.add_style("Code Block", 1)
code_style.font.name = "Menlo"
code_style.font.size = Pt(8.5)
code_style.paragraph_format.left_indent = Inches(0.18)
code_style.paragraph_format.right_indent = Inches(0.18)
code_style.paragraph_format.space_before = Pt(4)
code_style.paragraph_format.space_after = Pt(8)
code_style.paragraph_format.line_spacing = 1.1
shd = OxmlElement("w:shd")
shd.set(qn("w:fill"), LIGHT_GRAY)
code_style._element.get_or_add_pPr().append(shd)

hp = section.header.paragraphs[0]
hp.text = "UP Cebu RFID Admin  |  Ubuntu Server Deployment"
hp.alignment = WD_ALIGN_PARAGRAPH.RIGHT
set_font(hp.runs[0], size=9, color=MUTED)
fp = section.footer.paragraphs[0]
fp.alignment = WD_ALIGN_PARAGRAPH.RIGHT
set_font(fp.add_run("Page "), size=9, color=MUTED)
add_field(fp, "PAGE")

for _ in range(4):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(12)
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
set_font(p.add_run("PRODUCTION DEPLOYMENT GUIDE"), size=11, bold=True, color=BLUE)
p.paragraph_format.space_after = Pt(16)
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
set_font(p.add_run("UP Cebu RFID Admin"), size=28, bold=True, color=INK)
p.paragraph_format.space_after = Pt(8)
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
set_font(p.add_run("Step-by-step deployment to Ubuntu Server"), size=15, color=DARK_BLUE)
p.paragraph_format.space_after = Pt(20)
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
set_font(p.add_run("Ubuntu 24.04 LTS • Nginx • PHP-FPM • PostgreSQL • Redis • systemd"), size=10.5, color=MUTED)
p = doc.add_paragraph()
p.alignment = WD_ALIGN_PARAGRAPH.CENTER
set_font(p.add_run("Production translation of the repository’s start.sh workflow"), size=10.5, italic=True, color=MUTED)

doc.add_page_break()
doc.add_heading("1. Scope and assumptions", level=1)
doc.add_paragraph(
    "This guide deploys the Laravel 12 application to a single Ubuntu 24.04 LTS server. It preserves the responsibilities of start.sh while replacing development-only commands with production services."
)
add_table(doc, ["start.sh responsibility", "Ubuntu production replacement"], [
    ("brew services start postgresql@14", "Ubuntu PostgreSQL systemd service"),
    ("brew services start redis", "Ubuntu redis-server systemd service"),
    ("php artisan serve", "Nginx and PHP 8.3-FPM"),
    ("npm run dev", "npm ci and npm run build; Nginx serves built assets"),
    ("queue:work", "Persistent systemd queue-worker unit"),
    ("artisan pail", "systemd journal and Laravel log files"),
    ("reverb:start", "Private Reverb listener managed by systemd and proxied by Nginx"),
    ("rfid:sync-redis", "Persistent systemd RFID synchronization unit"),
    ("migrate --force", "Explicit, backed-up release step"),
], [3100, 6260])
add_note(doc, "Replace placeholders", "Before running commands, replace rfid.example.edu, repository URL, passwords, email address, and any values enclosed in angle brackets.", warning=True)

doc.add_heading("2. Required information", level=1)
for item in (
    "Ubuntu 24.04 LTS server IP address and a sudo-enabled SSH account.",
    "DNS A/AAAA record for the application domain, for example rfid.example.edu.",
    "Repository clone URL and credentials or deploy key.",
    "A strong PostgreSQL password and unique Reverb credentials.",
    "Outbound Internet access for Ubuntu packages, Composer, npm, and TLS issuance.",
    "Firewall or cloud security-group access for SSH (22), HTTP (80), and HTTPS (443).",
):
    add_check(doc, "☐ " + item)

doc.add_heading("3. Prepare the Ubuntu server", level=1)
start_step_sequence(doc)
add_step(doc, "Connect through SSH")
add_code(doc, "ssh <admin-user>@<server-ip>")
add_step(doc, "Update Ubuntu packages")
add_code(doc, "sudo apt update\nsudo apt full-upgrade -y")
add_step(doc, "Install the production packages")
add_code(doc,
    "sudo apt install -y nginx postgresql redis-server git unzip curl ca-certificates \\\n"
    "  composer php8.3-fpm php8.3-cli php8.3-pgsql php8.3-redis php8.3-mbstring \\\n"
    "  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl"
)
add_step(doc, "Enable and start core services")
add_code(doc, "sudo systemctl enable --now nginx php8.3-fpm postgresql redis-server")
add_step(doc, "Verify versions and service health")
add_code(doc,
    "php -v\ncomposer --version\nnginx -v\n"
    "sudo systemctl --no-pager --full status php8.3-fpm postgresql redis-server nginx\n"
    "redis-cli ping"
)
add_note(doc, "Expected Redis result", "redis-cli ping must return PONG. Keep PostgreSQL and Redis bound to the local server unless the architecture explicitly requires remote access.")

doc.add_heading("4. Configure the firewall", level=1)
start_step_sequence(doc)
add_step(doc, "Allow SSH before enabling UFW")
add_code(doc, "sudo ufw allow OpenSSH")
add_step(doc, "Allow Nginx HTTP and HTTPS")
add_code(doc, "sudo ufw allow 'Nginx Full'")
add_step(doc, "Enable and inspect the firewall")
add_code(doc, "sudo ufw enable\nsudo ufw status verbose")
add_note(doc, "Do not expose internal ports", "Do not open PostgreSQL 5432, Redis 6379, PHP-FPM, or Reverb 8080 to the Internet. Nginx is the public entry point.", warning=True)

doc.add_page_break()
doc.add_heading("5. Create the database", level=1)
start_step_sequence(doc)
add_step(doc, "Open the PostgreSQL console")
add_code(doc, "sudo -u postgres psql")
add_step(doc, "Create the application role and database", "Run the following SQL, using a strong password.")
add_code(doc,
    "CREATE ROLE up_cebu_rfid WITH LOGIN PASSWORD '<strong-database-password>';\n"
    "CREATE DATABASE library_rfid OWNER up_cebu_rfid;\n"
    "\\q"
)
add_step(doc, "Test the database login")
add_code(doc, "psql -h 127.0.0.1 -U up_cebu_rfid -d library_rfid -c 'select 1;'")

doc.add_heading("6. Deploy the application source", level=1)
start_step_sequence(doc)
add_step(doc, "Create the application directory")
add_code(doc, "sudo mkdir -p /var/www/up-cebu-rfid-admin\nsudo chown -R \"$USER\":www-data /var/www/up-cebu-rfid-admin")
add_step(doc, "Clone the repository")
add_code(doc,
    "git clone <repository-url> /var/www/up-cebu-rfid-admin\n"
    "cd /var/www/up-cebu-rfid-admin"
)
add_step(doc, "Install optimized PHP dependencies")
add_code(doc, "composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader")
add_step(doc, "Create the production environment file")
add_code(doc, "cp .env.example .env\nnano .env")

doc.add_heading("7. Configure .env for production", level=1)
doc.add_paragraph("Use the following as a production baseline. Do not paste real secrets into documentation or commit .env.")
add_code(doc,
    "APP_NAME=\"UP Cebu RFID Admin\"\n"
    "APP_ENV=production\nAPP_KEY=\nAPP_DEBUG=false\nAPP_URL=https://rfid.example.edu\n\n"
    "LOG_CHANNEL=stack\nLOG_LEVEL=warning\n\n"
    "DB_CONNECTION=pgsql\nDB_HOST=127.0.0.1\nDB_PORT=5432\n"
    "DB_DATABASE=library_rfid\nDB_USERNAME=up_cebu_rfid\nDB_PASSWORD=<strong-database-password>\n\n"
    "SESSION_DRIVER=redis\nQUEUE_CONNECTION=redis\nCACHE_STORE=redis\n"
    "REDIS_CLIENT=predis\nREDIS_HOST=127.0.0.1\nREDIS_PORT=6379\n"
    "REDIS_DB=0\nREDIS_CACHE_DB=1\nRFID_TRANSACTION_READ_MODEL=redis\n\n"
    "BROADCAST_CONNECTION=reverb\n"
    "REVERB_APP_ID=<unique-app-id>\nREVERB_APP_KEY=<random-app-key>\n"
    "REVERB_APP_SECRET=<random-app-secret>\n"
    "REVERB_SERVER_HOST=127.0.0.1\nREVERB_SERVER_PORT=8080\n"
    "REVERB_HOST=rfid.example.edu\nREVERB_PORT=443\nREVERB_SCHEME=https\n\n"
    "VITE_REVERB_APP_KEY=\"${REVERB_APP_KEY}\"\n"
    "VITE_REVERB_HOST=\"${REVERB_HOST}\"\nVITE_REVERB_PORT=\"${REVERB_PORT}\"\n"
    "VITE_REVERB_SCHEME=\"${REVERB_SCHEME}\""
)
start_step_sequence(doc)
add_step(doc, "Generate APP_KEY")
add_code(doc, "php artisan key:generate --force")
add_step(doc, "Protect the environment file")
add_code(doc, "sudo chown \"$USER\":www-data .env\nsudo chmod 640 .env")
add_note(doc, "Reverb security", "config/reverb.php currently permits allowed_origins=['*']. For production, change it to the exact application domain before deployment.", warning=True)

doc.add_page_break()
doc.add_heading("8. Build frontend assets", level=1)
doc.add_paragraph(
    "The project uses Vite 7. The build host must use Node.js 20.19+ or 22.12+. Node.js is required only to build assets; it is not needed by Nginx at runtime."
)
start_step_sequence(doc)
add_step(doc, "Install an approved compatible Node.js release", "Use the organization’s approved Node 20.19+ or Node 22.12+ installation method.")
add_step(doc, "Verify the Node.js version")
add_code(doc, "node --version\nnpm --version")
add_step(doc, "Install locked dependencies and build")
add_code(doc, "cd /var/www/up-cebu-rfid-admin\nnpm ci\nnpm run build")
add_step(doc, "Confirm the Vite manifest exists")
add_code(doc, "test -f public/build/manifest.json && echo 'Vite build OK'")
add_note(doc, "Build-time variables", "VITE_REVERB_* values are embedded during npm run build. Rebuild assets after changing those values.")

doc.add_heading("9. Set Laravel permissions and optimize", level=1)
start_step_sequence(doc)
add_step(doc, "Set application ownership and safe base permissions")
add_code(doc,
    "sudo chown -R \"$USER\":www-data /var/www/up-cebu-rfid-admin\n"
    "sudo find /var/www/up-cebu-rfid-admin -type d -exec chmod 755 {} \\;\n"
    "sudo find /var/www/up-cebu-rfid-admin -type f -exec chmod 644 {} \\;"
)
add_step(doc, "Grant write access only where Laravel requires it")
add_code(doc,
    "sudo chown -R www-data:www-data storage bootstrap/cache\n"
    "sudo find storage bootstrap/cache -type d -exec chmod 775 {} \\;\n"
    "sudo find storage bootstrap/cache -type f -exec chmod 664 {} \\;"
)
add_step(doc, "Clear and rebuild production caches")
add_code(doc,
    "php artisan optimize:clear\n"
    "php artisan config:cache\nphp artisan event:cache\nphp artisan route:cache\nphp artisan view:cache"
)

doc.add_heading("10. Run database migrations", level=1)
start_step_sequence(doc)
add_step(doc, "Back up the database before schema changes")
add_code(doc, "sudo -u postgres pg_dump -Fc library_rfid > \"$HOME/library_rfid-before-deploy.dump\"")
add_step(doc, "Review pending migrations")
add_code(doc, "php artisan migrate:status")
add_step(doc, "Apply migrations")
add_code(doc, "php artisan migrate --force")
add_note(doc, "Seeder boundary", "start.sh does not seed data. Run php artisan db:seed only after reviewing the seeders and confirming that production should receive those records.", warning=True)

doc.add_page_break()
doc.add_heading("11. Configure Nginx", level=1)
doc.add_paragraph("Create /etc/nginx/sites-available/up-cebu-rfid-admin with the following configuration:")
add_code(doc,
    "server {\n"
    "    listen 80;\n"
    "    listen [::]:80;\n"
    "    server_name rfid.example.edu;\n"
    "    root /var/www/up-cebu-rfid-admin/public;\n"
    "    index index.php;\n"
    "    charset utf-8;\n\n"
    "    location / { try_files $uri $uri/ /index.php?$query_string; }\n\n"
    "    location ~ \\.php$ {\n"
    "        include snippets/fastcgi-php.conf;\n"
    "        fastcgi_pass unix:/run/php/php8.3-fpm.sock;\n"
    "    }\n\n"
    "    location /app {\n"
    "        proxy_http_version 1.1;\n"
    "        proxy_set_header Host $host;\n"
    "        proxy_set_header Upgrade $http_upgrade;\n"
    "        proxy_set_header Connection \"upgrade\";\n"
    "        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\n"
    "        proxy_set_header X-Forwarded-Proto $scheme;\n"
    "        proxy_pass http://127.0.0.1:8080;\n"
    "        proxy_read_timeout 60s;\n"
    "    }\n\n"
    "    location /apps {\n"
    "        proxy_http_version 1.1;\n"
    "        proxy_set_header Host $host;\n"
    "        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\n"
    "        proxy_set_header X-Forwarded-Proto $scheme;\n"
    "        proxy_pass http://127.0.0.1:8080;\n"
    "    }\n\n"
    "    location ~ /\\. { deny all; }\n"
    "    client_max_body_size 55M;\n"
    "}"
)
start_step_sequence(doc)
add_step(doc, "Enable the site and remove the default site")
add_code(doc,
    "sudo ln -s /etc/nginx/sites-available/up-cebu-rfid-admin /etc/nginx/sites-enabled/up-cebu-rfid-admin\n"
    "sudo unlink /etc/nginx/sites-enabled/default"
)
add_step(doc, "Validate and reload Nginx")
add_code(doc, "sudo nginx -t\nsudo systemctl reload nginx")

doc.add_heading("12. Create persistent systemd services", level=1)
doc.add_paragraph("These services replace the foreground processes started by composer run dev.")

doc.add_heading("12.1 Queue worker", level=2)
add_code(doc,
    "# /etc/systemd/system/up-cebu-queue.service\n"
    "[Unit]\nDescription=UP Cebu RFID Laravel Queue Worker\nAfter=network.target redis-server.service postgresql.service\n\n"
    "[Service]\nUser=www-data\nGroup=www-data\nWorkingDirectory=/var/www/up-cebu-rfid-admin\n"
    "ExecStart=/usr/bin/php artisan queue:work redis --sleep=1 --tries=3 --timeout=60 --max-time=3600\n"
    "Restart=always\nRestartSec=5\nTimeoutStopSec=3600\n\n[Install]\nWantedBy=multi-user.target"
)
doc.add_heading("12.2 Reverb WebSocket server", level=2)
add_code(doc,
    "# /etc/systemd/system/up-cebu-reverb.service\n"
    "[Unit]\nDescription=UP Cebu RFID Laravel Reverb\nAfter=network.target redis-server.service\n\n"
    "[Service]\nUser=www-data\nGroup=www-data\nWorkingDirectory=/var/www/up-cebu-rfid-admin\n"
    "ExecStart=/usr/bin/php artisan reverb:start --host=127.0.0.1 --port=8080\n"
    "Restart=always\nRestartSec=5\nLimitNOFILE=10000\n\n[Install]\nWantedBy=multi-user.target"
)
doc.add_heading("12.3 RFID Redis synchronization", level=2)
add_code(doc,
    "# /etc/systemd/system/up-cebu-rfid-sync.service\n"
    "[Unit]\nDescription=UP Cebu RFID Transaction Redis Sync\nAfter=network.target redis-server.service postgresql.service\n\n"
    "[Service]\nUser=www-data\nGroup=www-data\nWorkingDirectory=/var/www/up-cebu-rfid-admin\n"
    "ExecStart=/usr/bin/php artisan rfid:sync-redis\n"
    "Restart=always\nRestartSec=5\n\n[Install]\nWantedBy=multi-user.target"
)
start_step_sequence(doc)
add_step(doc, "Reload systemd and start all application services")
add_code(doc,
    "sudo systemctl daemon-reload\n"
    "sudo systemctl enable --now up-cebu-queue up-cebu-reverb up-cebu-rfid-sync"
)
add_step(doc, "Inspect service status and logs")
add_code(doc,
    "sudo systemctl --no-pager --full status up-cebu-queue up-cebu-reverb up-cebu-rfid-sync\n"
    "sudo journalctl -u up-cebu-queue -u up-cebu-reverb -u up-cebu-rfid-sync -n 100 --no-pager"
)

doc.add_page_break()
doc.add_heading("13. Enable HTTPS", level=1)
doc.add_paragraph("Point DNS to the server and confirm the HTTP site loads before requesting a certificate.")
start_step_sequence(doc)
add_step(doc, "Install Certbot from Snap")
add_code(doc,
    "sudo snap install --classic certbot\n"
    "sudo ln -s /snap/bin/certbot /usr/local/bin/certbot"
)
add_step(doc, "Request and install the Nginx certificate")
add_code(doc, "sudo certbot --nginx -d rfid.example.edu")
add_step(doc, "Test automatic renewal")
add_code(doc, "sudo certbot renew --dry-run")
add_step(doc, "Rebuild assets if the public Reverb scheme changed to HTTPS")
add_code(doc, "npm run build\nphp artisan optimize:clear\nphp artisan config:cache")
add_step(doc, "Restart long-running processes")
add_code(doc,
    "sudo systemctl restart php8.3-fpm up-cebu-queue up-cebu-rfid-sync\n"
    "php artisan reverb:restart\nsudo systemctl restart up-cebu-reverb\nsudo systemctl reload nginx"
)

doc.add_heading("14. Verify the production deployment", level=1)
add_code(doc,
    "curl -I https://rfid.example.edu\n"
    "sudo nginx -t\n"
    "redis-cli ping\n"
    "php artisan migrate:status\n"
    "sudo ss -lntp | grep -E ':80|:443|:8080'\n"
    "sudo systemctl is-active nginx php8.3-fpm postgresql redis-server \\\n"
    "  up-cebu-queue up-cebu-reverb up-cebu-rfid-sync"
)
for item in (
    "HTTPS page loads without a certificate warning.",
    "Application login and authorized admin pages work.",
    "All migrations report Ran.",
    "Nginx, PHP-FPM, PostgreSQL, Redis, queue, Reverb, and RFID sync are active.",
    "Browser developer tools show a successful secure WebSocket connection.",
    "A test RFID transaction appears and updates in real time.",
    "Queue jobs are processed and no repeating errors appear in journalctl.",
    "Ports 5432, 6379, and 8080 are not publicly reachable.",
):
    add_check(doc, "☐ " + item)

doc.add_heading("15. Deployment updates", level=1)
start_step_sequence(doc)
add_step(doc, "Enable maintenance mode")
add_code(doc, "cd /var/www/up-cebu-rfid-admin\nphp artisan down --retry=60")
add_step(doc, "Back up PostgreSQL")
add_code(doc, "sudo -u postgres pg_dump -Fc library_rfid > \"$HOME/library_rfid-$(date +%F-%H%M).dump\"")
add_step(doc, "Update source and dependencies")
add_code(doc,
    "git pull --ff-only\n"
    "composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader\n"
    "npm ci\nnpm run build"
)
add_step(doc, "Apply migrations and rebuild caches")
add_code(doc,
    "php artisan migrate --force\nphp artisan optimize:clear\n"
    "php artisan config:cache\nphp artisan event:cache\nphp artisan route:cache\nphp artisan view:cache"
)
add_step(doc, "Restart long-running services")
add_code(doc,
    "php artisan queue:restart\nphp artisan reverb:restart\n"
    "sudo systemctl restart up-cebu-queue up-cebu-reverb up-cebu-rfid-sync php8.3-fpm\n"
    "sudo systemctl reload nginx"
)
add_step(doc, "Return the application to service and verify")
add_code(doc, "php artisan up\ncurl -I https://rfid.example.edu")
add_note(doc, "Rollback", "Application rollback and database rollback are separate decisions. Restore the previous code release first; restore a database backup only when the migration/data impact has been assessed.", warning=True)

doc.add_heading("16. Troubleshooting commands", level=1)
add_table(doc, ["Problem area", "Command"], [
    ("Laravel/PHP error", "tail -n 100 storage/logs/laravel.log; systemctl status php8.3-fpm"),
    ("Nginx error", "nginx -t; journalctl -u nginx -n 100"),
    ("Queue failure", "journalctl -u up-cebu-queue -n 100"),
    ("Reverb/WebSocket failure", "journalctl -u up-cebu-reverb -n 100; ss -lntp | grep 8080"),
    ("RFID sync failure", "journalctl -u up-cebu-rfid-sync -n 100"),
    ("Database failure", "systemctl status postgresql; pg_isready"),
    ("Redis failure", "systemctl status redis-server; redis-cli ping"),
    ("Permissions failure", "namei -l /var/www/up-cebu-rfid-admin/storage/logs"),
], [2600, 6760])

doc.add_heading("17. Source references", level=1)
for ref in (
    "Laravel 12 deployment: https://laravel.com/docs/12.x/deployment",
    "Laravel 12 Reverb production guidance: https://laravel.com/docs/12.x/reverb",
    "Laravel 12 queues: https://laravel.com/docs/12.x/queues",
    "Ubuntu Server PostgreSQL: https://ubuntu.com/server/docs/install-and-configure-postgresql/",
    "Ubuntu Server firewall: https://documentation.ubuntu.com/server/how-to/security/firewalls/",
    "Vite Node.js requirements: https://vite.dev/guide/",
    "Certbot for Nginx: https://certbot.eff.org/instructions?ws=nginx&os=snap",
):
    add_bullet(doc, ref)

doc.core_properties.title = "UP Cebu RFID Admin Ubuntu Server Deployment Guide"
doc.core_properties.subject = "Step-by-step Ubuntu 24.04 production deployment"
doc.core_properties.author = "UP Cebu RFID Admin Project"
OUTPUT.parent.mkdir(parents=True, exist_ok=True)
doc.save(OUTPUT)
print(OUTPUT)
