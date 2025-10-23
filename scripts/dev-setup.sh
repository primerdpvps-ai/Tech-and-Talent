#!/bin/bash

# TTS PMS Development Setup Script
# This script sets up the complete development environment using Docker

set -e

echo "🚀 TTS PMS Development Setup"
echo "================================"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker Desktop and try again."
    exit 1
fi

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    echo "❌ docker-compose is not installed. Please install Docker Desktop."
    exit 1
fi

echo "✅ Docker is running"

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
    
    # Generate secrets
    echo "🔐 Generating secure secrets..."
    
    # Generate NEXTAUTH_SECRET
    NEXTAUTH_SECRET=$(openssl rand -base64 32)
    sed -i.bak "s/your-nextauth-secret-key-here-generate-with-openssl-rand-base64-32/$NEXTAUTH_SECRET/g" .env
    
    # Generate JWT_SECRET
    JWT_SECRET=$(openssl rand -base64 32)
    sed -i.bak "s/your-jwt-secret-key-here-generate-with-openssl-rand-base64-32/$JWT_SECRET/g" .env
    
    # Generate AGENT_DEVICE_SECRET
    AGENT_DEVICE_SECRET=$(openssl rand -hex 32)
    sed -i.bak "s/your-agent-device-secret-key-generate-with-openssl-rand-hex-32/$AGENT_DEVICE_SECRET/g" .env
    
    # Clean up backup files
    rm -f .env.bak
    
    echo "✅ Generated secure secrets in .env file"
else
    echo "✅ .env file already exists"
fi

# Stop any existing containers
echo "🛑 Stopping any existing containers..."
docker-compose down > /dev/null 2>&1 || true

# Pull latest images
echo "📥 Pulling latest Docker images..."
docker-compose pull

# Build the web application
echo "🔨 Building web application..."
docker-compose build web

# Start all services
echo "🚀 Starting all services..."
docker-compose up -d

# Wait for services to be healthy
echo "⏳ Waiting for services to be ready..."

# Wait for MySQL
echo "  - Waiting for MySQL..."
timeout=60
while ! docker-compose exec -T mysql mysqladmin ping -h localhost -u root -prootpassword --silent > /dev/null 2>&1; do
    timeout=$((timeout - 1))
    if [ $timeout -eq 0 ]; then
        echo "❌ MySQL failed to start within 60 seconds"
        docker-compose logs mysql
        exit 1
    fi
    sleep 1
done
echo "  ✅ MySQL is ready"

# Wait for MinIO
echo "  - Waiting for MinIO..."
timeout=30
while ! curl -f http://localhost:9000/minio/health/live > /dev/null 2>&1; do
    timeout=$((timeout - 1))
    if [ $timeout -eq 0 ]; then
        echo "❌ MinIO failed to start within 30 seconds"
        docker-compose logs minio
        exit 1
    fi
    sleep 1
done
echo "  ✅ MinIO is ready"

# Wait for Redis
echo "  - Waiting for Redis..."
timeout=30
while ! docker-compose exec -T redis redis-cli ping > /dev/null 2>&1; do
    timeout=$((timeout - 1))
    if [ $timeout -eq 0 ]; then
        echo "❌ Redis failed to start within 30 seconds"
        docker-compose logs redis
        exit 1
    fi
    sleep 1
done
echo "  ✅ Redis is ready"

# Run database migrations and seed
echo "🗄️  Setting up database..."
docker-compose run --rm db-migrate

# Wait for web application
echo "  - Waiting for web application..."
timeout=60
while ! curl -f http://localhost:3000/api/health > /dev/null 2>&1; do
    timeout=$((timeout - 1))
    if [ $timeout -eq 0 ]; then
        echo "❌ Web application failed to start within 60 seconds"
        docker-compose logs web
        exit 1
    fi
    sleep 2
done
echo "  ✅ Web application is ready"

# Initialize job scheduler
echo "🔧 Initializing job scheduler..."
curl -X POST http://localhost:3000/api/jobs/init \
  -H "Authorization: Bearer admin-secret-token-123" \
  -H "Content-Type: application/json" \
  > /dev/null 2>&1 || echo "  ⚠️  Job scheduler initialization failed (this is normal on first run)"

echo ""
echo "🎉 Setup complete! Your TTS PMS development environment is ready."
echo ""
echo "📍 Access your services:"
echo "  🌐 Web Application:    http://localhost:3000"
echo "  🗄️  Prisma Studio:     http://localhost:5555"
echo "  📦 MinIO Console:      http://localhost:9001"
echo "  📧 Mailhog:           http://localhost:8025"
echo ""
echo "🔐 Default login credentials:"
echo "  👑 CEO:        ceo@tts-pms.com / admin123"
echo "  👨‍💼 Manager:    manager@tts-pms.com / manager123"
echo "  👨‍💻 Employee:   employee1@tts-pms.com / employee123"
echo ""
echo "📚 Useful commands:"
echo "  docker-compose logs -f web     # View web application logs"
echo "  docker-compose restart web     # Restart web application"
echo "  docker-compose down            # Stop all services"
echo "  docker-compose down -v         # Stop and remove all data"
echo ""
echo "🆘 If you encounter issues, check the troubleshooting section in DOCKER_SETUP.md"
