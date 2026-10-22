# ChartSense AI — Institutional AI Chart Scanner & Technical Analysis Platform

> **"Understand the Chart. Analyze the Market. Trade with a Plan."**

ChartSense AI is a full-stack, enterprise-grade AI-powered financial market scanner built with **Laravel 13**, **Tailwind CSS v4**, **Alpine.js**, and **PostgreSQL 15** (fully manageable through **pgAdmin 4**).

---

## 💎 Core Business Rule: Exness Referral-Gated Access

**ChartSense AI contains ZERO in-app purchases, credit systems, paywalls, or billing gateways.**

Scanner access is unlocked exclusively by registering through our official Exness referral partner link:
- **Official Exness Registration Link:** [https://one.exnessonelink.com/a/l4l2nsdkfb?source=app](https://one.exnessonelink.com/a/l4l2nsdkfb?source=app)
- **Partner ID:** `l4l2nsdkfb`

The platform enforces this rule server-side via custom middleware (`referral.verified`). Unverified accounts are restricted to the landing page, dashboard overview, and the referral verification portal.

---

## 🚀 Key Modules & Capabilities

### 1. Dual-Input Chart Scanner
- **Chart Screenshots:** Upload charts from TradingView, MetaTrader 4/5, or mobile brokers (PNG, JPG, WebP up to 15MB).
- **Real OHLCV Datasets:** Upload historical CSV files or paste raw price data to calculate mathematical and algorithmic indicators.

### 2. Algorithmic Technical Analysis Engine
- **Moving Averages:** SMA (20, 50, 200) and EMA (9, 21, 50).
- **Oscillators & Volatility:** RSI (14-period Wilder smoothing), MACD (12, 26, 9 with signal and histogram), ATR (14-period volatility).
- **Smart Money Concepts (SMC):** Break of Structure (BOS), Change of Character (CHOCH), Fair Value Gaps (FVG), Order Blocks, and Swing Pivots.
- **Fibonacci Retracements:** Automatic golden ratio calculations (0.0%, 23.6%, 38.2%, 50.0%, 61.8%, 78.6%, 100.0%).

### 3. Price Action Geometry & Trading Plan Validation
- Enforces strict institutional risk management rules:
  - **BUY Setup:** `Stop Loss < Entry Target < TP1 < TP2 < TP3`
  - **SELL Setup:** `Stop Loss > Entry Target > TP1 > TP2 > TP3`
  - Automatically calculates risk-to-reward (R:R) ratios.
  - Automatically invalidates contradictory setups and downgrades to `WAIT` or `NO VALID SETUP`.

### 4. Multi-Provider AI Architecture
- Native support for **Google Gemini** (multimodal vision), **Anthropic Claude 3.5 Sonnet**, and **OpenAI GPT-4o**.
- Intelligent deterministic fallback: If AI API keys are not supplied or rate-limited, ChartSense AI seamlessly runs its algorithmic rule engine.

### 5. Administration & Governance
- **Referral Verification Queue:** Review trader account IDs and evidence files; approve, reject, or suspend access with one click.
- **User Management:** Monitor traders, toggle roles (`user` ↔ `admin`), and suspend/restore accounts.
- **Scan Monitoring:** Inspect scan records, inputs, and results across all users.
- **AI & System Settings:** Dynamically change active AI providers, models, and public announcements.
- **System Audit Trail:** Immutable ledger logging authentication, referral decisions, settings changes, and scan completions.

---

## 🛠️ System Requirements

- **Operating System:** Windows 10/11, macOS, or Linux (Ubuntu 22.04+ recommended for production)
- **PHP:** 8.2 or 8.5+ with extensions: `pdo_pgsql`, `pgsql`, `mbstring`, `fileinfo`, `curl`, `openssl`, `gd`
- **Database:** PostgreSQL 15+
- **Database GUI:** pgAdmin 4
- **Node.js:** 18+ & npm
- **Composer:** 2.x+

---

## 💻 Local Development Setup (Windows / PowerShell)

### Step 1: Clone and Install Dependencies

```powershell
# Navigate to projects directory
cd d:\laravel_projects\chartsense-ai

# Install PHP dependencies
composer install

# Install Node.js frontend dependencies
npm install
```

### Step 2: Environment Configuration

Copy the example environment file and generate the application encryption key:

```powershell
cp .env.example .env
php artisan key:generate
```

Verify your `.env` contains the PostgreSQL credentials and Exness partner configuration:

```ini
APP_NAME="ChartSense AI"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=chartsense_db
DB_USERNAME=postgres
DB_PASSWORD=your_postgres_password

# Official Exness Partner Gateway
EXNESS_REFERRAL_URL=https://one.exnessonelink.com/a/l4l2nsdkfb?source=app
EXNESS_PARTNER_CODE=l4l2nsdkfb

# AI Provider Keys (Optional - internal fallback is active)
AI_DEFAULT_PROVIDER=gemini
GEMINI_API_KEY=
ANTHROPIC_API_KEY=
OPENAI_API_KEY=
```

### Step 3: PostgreSQL Database Setup in pgAdmin 4

1. Open **pgAdmin 4**.
2. Connect to your local PostgreSQL server (`127.0.0.1:5432`).
3. Right-click **Databases** &rarr; **Create** &rarr; **Database...**
4. Name the database `chartsense_db` and click **Save**.

### Step 4: Run Migrations and Seeders

Run the database migrations and seed default administrative and test accounts:

```powershell
php artisan migrate --seed
```

### Step 5: Build Frontend Assets & Start Server

```powershell
# Build Tailwind CSS v4 assets
npm.cmd run build

# Start the Laravel development server
php artisan serve
```

Visit **http://localhost:8000** in your web browser.

---

## 👥 Pre-Seeded Demonstration Accounts

| Role | Email | Password | Access Status |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@chartsense.ai` | `Password@123456` | Full Admin Portal (`/admin`) |
| **Verified Trader** | `trader@chartsense.ai` | `Password@123456` | Scanner **UNLOCKED** (`/scanner`) |
| **Unverified Trader** | `newtrader@chartsense.ai` | `Password@123456` | Scanner **LOCKED** (Redirects to `/referral-access`) |

---

## 🧪 Automated Testing Suite

ChartSense AI includes a 100% passing automated feature test suite covering all critical authentication, referral gating, scanner workflows, and admin controls:

```powershell
# Run the complete test suite
php artisan test
```

### Test Coverage Highlights:
- `LandingPageTest`: Public landing, legal policies, and database seeding validation.
- `AuthWorkflowTest`: Registration, login, rate limiting, remember me, profile editing, and suspension lockout.
- `ReferralVerificationGateTest`: Server-side scanner gating, screenshot/ID submission, admin approval/rejection/suspension, and audit logging.
- `ChartScannerWorkflowTest`: CSV parsing, RSI/MACD/ATR/SMC calculations, trading plan price geometry validation, and data privacy isolation.
- `AdminDashboardTest`: Authorization boundaries, user role toggling, setting updates, and immutable audit logs.

---

## 🌐 Production Deployment Guide (Ubuntu 22.04 LTS / Nginx)

### 1. Server Prerequisites

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-curl php8.3-xml php8.3-zip php8.3-gd nginx postgresql postgresql-contrib supervisor certbot python3-certbot-nginx
```

### 2. Nginx Virtual Host Configuration

Create `/etc/nginx/sites-available/chartsense`:

```nginx
server {
    listen 80;
    server_name chartsense.yourdomain.com;
    root /var/www/chartsense-ai/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;
    charset utf-8;

    client_max_body_size 25M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site and obtain SSL via Let's Encrypt:

```bash
sudo ln -s /etc/nginx/sites-available/chartsense /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d chartsense.yourdomain.com
```

### 3. File Permissions & Optimization

```bash
cd /var/www/chartsense-ai
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Production cache commands
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Background Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/chartsense-worker.conf`:

```ini
[program:chartsense-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/chartsense-ai/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/chartsense-ai/storage/logs/worker.log
stopwaitsecs=3600
```

Start Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start chartsense-worker:*
```

---

## 🔒 Institutional Cybersecurity Hardening

ChartSense AI adheres to OWASP Top 10 institutional security standards:
1. **HTTP Security Headers (`SecurityHeadersMiddleware`):**
   - `Content-Security-Policy`: Restricts scripts/styles/frames strictly to self, Google Identity, Firebase, and trusted CDNs.
   - `Strict-Transport-Security` (HSTS): Enforces 1-year preload TLS encryption.
   - `X-Frame-Options: SAMEORIGIN`: Prevents UI redressing and clickjacking.
   - `X-Content-Type-Options: nosniff`: Defeats MIME sniffing attacks.
   - `X-XSS-Protection: 1; mode=block`: Active browser-level XSS filter.
   - `Permissions-Policy`: Hard disables unauthorized hardware sensors (camera, microphone, geolocation, payment).
2. **Reverse Proxy & TLS Termination:** Fully trusts reverse proxy headers (`trustProxies('*')`) for cloud load balancers (AWS ALB, Cloudflare, Render, Railway).
3. **Storage & Path Traversal Isolation:** Chart screenshots and referral proofs reside exclusively in private storage (`storage/app/`), guarded by `realpath()` containment checks and strict IDOR authorization logic.
4. **Rate Limiting & DDoS Mitigation:** Sensitive routes are rate-limited per IP (Login: 15/min, Register: 10/min, Firebase session: 20/min, Scanner: 15/min).
5. **Session & Cookie Hardening:** `SameSite=Lax`, `HttpOnly=true`, `secure=true` on HTTPS, with JSON session serialization preventing PHP object injection.
6. **No Billing Risk:** By architectural mandate, no credit cards or billing credentials exist within the application.

---

## 🐳 Production Cloud Deployment (Docker & PostgreSQL)

ChartSense AI comes pre-packaged with production Docker and 1-click cloud manifests:

### 1. Render Deployment (`render.yaml`)
1. Fork or push this repository to GitHub.
2. In [Render Dashboard](https://dashboard.render.com), click **New +** &rarr; **Blueprint**.
3. Select your repository. Render will automatically provision:
   - Managed **PostgreSQL 16** database (`chartsense-db`).
   - Containerized Web Service running PHP 8.3 FPM + Nginx.

### 2. Railway Deployment (`railway.json`)
1. In [Railway](https://railway.app), click **New Project** &rarr; **Deploy from GitHub repo**.
2. Add a **PostgreSQL** database service to the project.
3. Railway automatically builds the multi-stage `Dockerfile` and runs automatic database migrations.

---

## 📜 Legal & Compliance

- **Trading Risk Disclosure:** Financial trading involves substantial risk of loss. ChartSense AI is an educational analysis and structure-mapping tool, not a registered financial advisory service.
- **Affiliation:** ChartSense AI operates under the official Exness Partner Program (ID: `l4l2nsdkfb`).

---

**Developed with precision for disciplined traders.** &copy; 2026 ChartSense AI.
