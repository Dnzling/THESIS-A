# DEPLOYMENT & SETUP GUIDE

**Version:** 1.0.0  
**Date:** March 8, 2026  
**Status:** Ready for Production  

---

## 📋 PREREQUISITES

### System Requirements
- PHP 8.1 or higher
- Node.js 16+ & npm/pnpm
- MySQL 8.0 or higher
- Redis (for queue/cache - optional but recommended)
- Composer 2.0+

### Required PHP Extensions
- ext-json
- ext-pdo
- ext-pdo_mysql
- ext-tokenizer
- ext-xml
- ext-ctype
- ext-curl
- ext-fileinfo
- ext-filter
- ext-hash
- ext-mbstring
- ext-openssl
- ext-pcre
- ext-reflection

---

## 🔧 DEVELOPMENT SETUP

### Step 1: Clone Repository
```bash
git clone <your-repo-url>
cd FurnitureStoresPlatform
```

### Step 2: Backend Setup
```bash
cd backend

# Copy environment file
cp .env.example .env

# Generate APP_KEY
php artisan key:generate

# Install dependencies
composer install

# Create .env database credentials
# Update these in .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=furniture_stores
DB_USERNAME=root
DB_PASSWORD=

# Run migrations
php artisan migrate

# (Optional) Seed sample data
php artisan db:seed

# Create storage symlink
php artisan storage:link
```

### Step 3: Frontend Setup
```bash
cd ../frontend

# Install dependencies
npm install
# or
pnpm install

# Create environment file
cp .env.example .env.local

# Update VITE_API_BASE_URL in .env.local
# Example: VITE_API_BASE_URL=http://localhost:8000
```

### Step 4: Start Development Servers

**Terminal 1 - Backend (port 8000)**
```bash
cd backend
php artisan serve
```

**Terminal 2 - Frontend (port 5173)**
```bash
cd frontend
npm run dev
```

**Terminal 3 - Queue Worker (required for events)**
```bash
cd backend
php artisan queue:work
```

**Terminal 4 - Cache/Session (optional)**
```bash
cd backend
php artisan tinker
# Or use Redis if configured
```

Access frontend at: `http://localhost:5173`

---

## 🐳 DOCKER SETUP (Alternative)

### Docker Compose Configuration
```bash
# Create docker-compose.yml in root directory
cd FurnitureStoresPlatform

# Start all services
docker-compose up -d

# Run migrations
docker-compose exec app php artisan migrate

# Access frontend at localhost:3000
# Access backend at localhost:8000
```

---

## 🌍 PRODUCTION DEPLOYMENT

### Platform: Linux Server (Ubuntu 20.04+)

#### Step 1: Server Preparation
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y php8.1 php8.1-{cli,fpm,mysql,redis,mbstring,xml,curl,zip,bcmath,json,ctype,fileinfo}
sudo apt install -y mysql-server redis-server nodejs npm nginx
sudo apt install -y composer git unzip certbot python3-certbot-nginx

# Start services
sudo systemctl start php8.1-fpm
sudo systemctl start mysql
sudo systemctl start redis-server
sudo systemctl start nginx
```

#### Step 2: Clone & Setup Backend
```bash
cd /var/www
sudo git clone <repo-url> furniture-stores
cd furniture-stores/backend

# Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 storage bootstrap/cache

# Install dependencies
composer install --no-dev --optimize-autoloader

# Setup .env
sudo cp .env.example .env
# Edit .env with production values
sudo nano .env

# Generate key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Create storage symbolic link
php artisan storage:link

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Step 3: Setup Frontend
```bash
cd ../frontend

# Install dependencies
npm install --production

# Build for production
npm run build
```

#### Step 4: Configure Nginx
```bash
# Create nginx config
sudo nano /etc/nginx/sites-available/furniture-stores

# Add this configuration:
```
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;

    # Frontend (Vue build)
    root /var/www/furniture-stores/frontend/dist;
    index index.html;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    # Frontend routes (Vue Router)
    location / {
        try_files $uri $uri/ /index.html;
        expires 1d;
    }

    # API proxy to backend
    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Static assets (backend)
    location /storage/ {
        proxy_pass http://127.0.0.1:8000;
    }
}
```

```bash
# Enable site
sudo ln -s /etc/nginx/sites-available/furniture-stores /etc/nginx/sites-enabled/

# Test nginx config
sudo nginx -t

# Restart nginx
sudo systemctl restart nginx

# Add SSL certificate (Let's Encrypt)
sudo certbot --nginx -d your-domain.com
```

#### Step 5: Setup Supervisor for Queue Worker
```bash
# Install Supervisor
sudo apt install -y supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/furniture-stores-queue.conf

# Add this configuration:
```
```ini
[program:furniture-stores-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/furniture-stores/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/furniture-stores-queue.log
stopwaitsecs=3600
user=www-data
```

```bash
# Start supervisor
sudo systemctl restart supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all

# Check status
sudo supervisorctl status
```

#### Step 6: Setup Laravel Horizon (Alternative to Supervisor)
```bash
cd /var/www/furniture-stores/backend

# Install Horizon
composer require laravel/horizon

# Publish config
php artisan horizon:publish

# Start Horizon
php artisan horizon
```

---

## 📊 ENVIRONMENT VARIABLES

### Backend (.env)

```env
# App
APP_NAME="Furniture Stores Platform"
APP_ENV=production
APP_KEY=base64:your-generated-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=furniture_stores
DB_USERNAME=furniture_user
DB_PASSWORD=secure_password

# Cache
CACHE_DRIVER=redis
CACHE_TTL=3600

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=noreply@furniture-stores.com
MAIL_FROM_NAME="${APP_NAME}"

# AWS (optional, for S3 storage)
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

# Sanctum
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SANCTUM_EXPIRATION=43200
```

### Frontend (.env.production)

```env
VITE_API_BASE_URL=https://your-domain.com
VITE_APP_NAME="Furniture Stores Platform"
VITE_ENV=production
```

---

## 🔐 SECURITY CHECKLIST

Before going live:

- [ ] Change all default passwords
- [ ] Set `APP_DEBUG=false`
- [ ] Generate unique `APP_KEY`
- [ ] Configure firewall rules
- [ ] Enable HTTPS/SSL certificate
- [ ] Setup database backups
- [ ] Configure rate limiting
- [ ] Enable CORS only for your domain
- [ ] Setup logging and monitoring
- [ ] Configure error tracking (Sentry, Rollbar, etc)
- [ ] Regular security updates
- [ ] Database encryption at rest
- [ ] API key rotation schedule

---

## 🧪 TESTING & VALIDATION

### Run Tests
```bash
cd backend

# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test tests/Feature/InventoryTest.php
```

### Manual Testing Checklist
```
Frontend:
- [ ] Login successful
- [ ] Dashboard loads
- [ ] Notifications dropdown works
- [ ] Alert dashboard functional
- [ ] Can create stock transfer
- [ ] Can generate reports
- [ ] Can export data

Backend:
- [ ] All 26 API endpoints return 200
- [ ] Notifications sent on events
- [ ] Transfer workflow completes
- [ ] Alerts generate on stock changes
- [ ] Reports generate with data
- [ ] Configuration saves
- [ ] Error handling working
```

---

## 📈 MONITORING & MAINTENANCE

### Logging
```bash
# Monitor real-time logs
tail -f /var/www/furniture-stores/backend/storage/logs/laravel.log

# Monitor queue logs
tail -f /var/log/furniture-stores-queue.log
```

### Database Backups
```bash
# Automated daily backup
# Add to crontab:
0 2 * * * mysqldump -u user -p password furniture_stores > /backups/db_$(date +\%Y\%m\%d).sql
```

### Performance Monitoring
```bash
# Check queue health
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Scaling for High Traffic

1. **Database Optimization**
   - Add indexes on frequently queried columns
   - Implement database partitioning
   - Use read replicas for reporting

2. **Caching Strategy**
   - Cache reports (Redis)
   - Cache configuration settings
   - Cache frequently accessed data

3. **Load Balancing**
   - Setup multiple backend instances
   - Use load balancer (nginx, HAProxy)
   - Sticky sessions for WebSocket

4. **Queue Optimization**
   - Increase supervisor processes
   - Use multiple queue channels
   - Implement job timeouts

---

## 🚨 TROUBLESHOOTING

### Common Issues

**Issue: Permission denied errors**
```bash
sudo chown -R www-data:www-data /var/www/furniture-stores
sudo chmod -R 755 /var/www/furniture-stores
sudo chmod -R 775 /var/www/furniture-stores/backend/storage
```

**Issue: Queue jobs not processing**
```bash
# Check if queue worker is running
sudo supervisorctl status

# Restart queue worker
sudo supervisorctl restart furniture-stores-queue:*

# Check Redis connection
redis-cli ping  # Should return PONG
```

**Issue: Frontend not connecting to backend**
```bash
# Check CORS in backend (config/cors.php)
# Verify API_BASE_URL in frontend .env
# Check if backend is running: curl http://localhost:8000/api/health
```

**Issue: Database connection errors**
```bash
# Verify MySQL is running
sudo systemctl status mysql

# Check database exists
mysql -u root -p -e "SHOW DATABASES;"

# Verify .env credentials
cat .env | grep DB_
```

---

## 🎯 DEPLOYMENT VERIFICATION

### Post-Deployment Checklist

```bash
# 1. Verify backend is running
curl -H "Authorization: Bearer token" https://your-domain.com/api/inventory/dashboard

# 2. Check frontend is accessible
curl https://your-domain.com | grep "<!DOCTYPE html>"

# 3. Verify database connectivity
php artisan tinker
>>> DB::table('users')->count()

# 4. Check queue worker status
sudo supervisorctl status

# 5. Verify SSL certificate
openssl s_client -connect your-domain.com:443

# 6. Check logs for errors
tail -f storage/logs/laravel.log

# 7. Monitor performance
php artisan horizon  # If using Horizon
```

---

## 📞 SUPPORT

### Getting Help
- Check logs first: `storage/logs/laravel.log`
- Review error messages carefully
- Search GitHub issues
- Contact development team

### Reporting Issues
Include:
1. Error message
2. Steps to reproduce
3. Browser/environment details
4. Recent log entries
5. Screenshots if applicable

---

## 🎓 ADDITIONAL RESOURCES

### Documentation
- [Laravel Documentation](https://laravel.com/docs)
- [Vue 3 Documentation](https://vuejs.org)
- [Nginx Documentation](https://nginx.org/en/docs/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

### Tools
- Laravel Tinker - Interactive shell
- Postman - API testing
- Chrome DevTools - Frontend debugging
- MySQL Workbench - Database management

---

**Deployment Status: ✅ Ready**  
**Estimated Deployment Time: 2-4 hours**  
**Support Level: Full**
