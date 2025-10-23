@echo off
setlocal enabledelayedexpansion

REM TTS PMS Development Setup Script for Windows
REM This script sets up the complete development environment using Docker

echo 🚀 TTS PMS Development Setup
echo ================================

REM Check if Docker is running
docker info >nul 2>&1
if errorlevel 1 (
    echo ❌ Docker is not running. Please start Docker Desktop and try again.
    pause
    exit /b 1
)

REM Check if docker-compose is available
docker-compose --version >nul 2>&1
if errorlevel 1 (
    echo ❌ docker-compose is not installed. Please install Docker Desktop.
    pause
    exit /b 1
)

echo ✅ Docker is running

REM Create .env file if it doesn't exist
if not exist .env (
    echo 📝 Creating .env file from .env.example...
    copy .env.example .env >nul
    
    echo 🔐 Generating secure secrets...
    echo   Note: Using default secrets for Windows setup
    echo   For production, generate secure secrets manually
    
    echo ✅ Created .env file with default configuration
) else (
    echo ✅ .env file already exists
)

REM Stop any existing containers
echo 🛑 Stopping any existing containers...
docker-compose down >nul 2>&1

REM Pull latest images
echo 📥 Pulling latest Docker images...
docker-compose pull

REM Build the web application
echo 🔨 Building web application...
docker-compose build web

REM Start all services
echo 🚀 Starting all services...
docker-compose up -d

REM Wait for services to be healthy
echo ⏳ Waiting for services to be ready...

REM Wait for MySQL
echo   - Waiting for MySQL...
set timeout=60
:wait_mysql
docker-compose exec -T mysql mysqladmin ping -h localhost -u root -prootpassword --silent >nul 2>&1
if errorlevel 1 (
    set /a timeout-=1
    if !timeout! equ 0 (
        echo ❌ MySQL failed to start within 60 seconds
        docker-compose logs mysql
        pause
        exit /b 1
    )
    timeout /t 1 /nobreak >nul
    goto wait_mysql
)
echo   ✅ MySQL is ready

REM Wait for MinIO
echo   - Waiting for MinIO...
set timeout=30
:wait_minio
curl -f http://localhost:9000/minio/health/live >nul 2>&1
if errorlevel 1 (
    set /a timeout-=1
    if !timeout! equ 0 (
        echo ❌ MinIO failed to start within 30 seconds
        docker-compose logs minio
        pause
        exit /b 1
    )
    timeout /t 1 /nobreak >nul
    goto wait_minio
)
echo   ✅ MinIO is ready

REM Wait for Redis
echo   - Waiting for Redis...
set timeout=30
:wait_redis
docker-compose exec -T redis redis-cli ping >nul 2>&1
if errorlevel 1 (
    set /a timeout-=1
    if !timeout! equ 0 (
        echo ❌ Redis failed to start within 30 seconds
        docker-compose logs redis
        pause
        exit /b 1
    )
    timeout /t 1 /nobreak >nul
    goto wait_redis
)
echo   ✅ Redis is ready

REM Run database migrations and seed
echo 🗄️ Setting up database...
docker-compose run --rm db-migrate

REM Wait for web application
echo   - Waiting for web application...
set timeout=60
:wait_web
curl -f http://localhost:3000/api/health >nul 2>&1
if errorlevel 1 (
    set /a timeout-=1
    if !timeout! equ 0 (
        echo ❌ Web application failed to start within 60 seconds
        docker-compose logs web
        pause
        exit /b 1
    )
    timeout /t 2 /nobreak >nul
    goto wait_web
)
echo   ✅ Web application is ready

REM Initialize job scheduler
echo 🔧 Initializing job scheduler...
curl -X POST http://localhost:3000/api/jobs/init -H "Authorization: Bearer admin-secret-token-123" -H "Content-Type: application/json" >nul 2>&1
if errorlevel 1 (
    echo   ⚠️ Job scheduler initialization failed (this is normal on first run)
)

echo.
echo 🎉 Setup complete! Your TTS PMS development environment is ready.
echo.
echo 📍 Access your services:
echo   🌐 Web Application:    http://localhost:3000
echo   🗄️ Prisma Studio:     http://localhost:5555
echo   📦 MinIO Console:      http://localhost:9001
echo   📧 Mailhog:           http://localhost:8025
echo.
echo 🔐 Default login credentials:
echo   👑 CEO:        ceo@tts-pms.com / admin123
echo   👨‍💼 Manager:    manager@tts-pms.com / manager123
echo   👨‍💻 Employee:   employee1@tts-pms.com / employee123
echo.
echo 📚 Useful commands:
echo   docker-compose logs -f web     # View web application logs
echo   docker-compose restart web     # Restart web application
echo   docker-compose down            # Stop all services
echo   docker-compose down -v         # Stop and remove all data
echo.
echo 🆘 If you encounter issues, check the troubleshooting section in DOCKER_SETUP.md
echo.
echo Press any key to open the web application...
pause >nul
start http://localhost:3000
