# TTS PMS Docker Development Setup

This guide will help you set up the complete TTS PMS development environment using Docker.

## 🚀 Quick Start

### Prerequisites
- Docker Desktop installed and running
- Git (for cloning the repository)
- At least 4GB RAM available for Docker

### 1. Clone and Setup
```bash
# Clone the repository
git clone <repository-url>
cd tts_pms

# Copy environment configuration
cp .env.example .env

# Generate required secrets (optional - defaults will work for development)
# NEXTAUTH_SECRET
openssl rand -base64 32

# JWT_SECRET  
openssl rand -base64 32

# AGENT_DEVICE_SECRET
openssl rand -hex 32
```

### 2. Start All Services
```bash
# Start all services in detached mode
docker-compose up -d

# View logs (optional)
docker-compose logs -f web
```

### 3. Initialize Database
```bash
# Run database migrations and seed data
docker-compose run --rm db-migrate

# Or manually:
docker-compose exec web npx prisma migrate deploy
docker-compose exec web npx prisma db seed
```

### 4. Access the Application
The application will be available at the following URLs:

## 📍 Local URLs

| Service | URL | Description |
|---------|-----|-------------|
| **Web Application** | http://localhost:3000 | Main TTS PMS application |
| **Prisma Studio** | http://localhost:5555 | Database management interface |
| **MinIO Console** | http://localhost:9001 | S3-compatible storage management |
| **Mailhog Web UI** | http://localhost:8025 | Email testing interface |
| **MySQL Database** | localhost:3306 | Direct database access |
| **Redis** | localhost:6379 | Cache and session storage |

## 🔐 Default Login Credentials

After running the seed script, you can log in with these accounts:

| Role | Email | Password | Description |
|------|-------|----------|-------------|
| **CEO** | ceo@tts-pms.com | admin123 | Full system access |
| **Manager** | manager@tts-pms.com | manager123 | Team management access |
| **Employee** | employee1@tts-pms.com | employee123 | Standard employee access |
| **New Employee** | newemployee@tts-pms.com | newemployee123 | Training-locked employee |
| **Candidate** | candidate@tts-pms.com | candidate123 | Job application access |

## 🛠️ Development Workflow

### Starting Development
```bash
# Start all services
docker-compose up -d

# View application logs
docker-compose logs -f web

# Access the application
open http://localhost:3000
```

### Making Code Changes
The web application uses volume mounts, so changes to your code will be reflected immediately:

```bash
# The following directories are mounted:
# ./packages/web -> /app (in container)

# Hot reload is enabled for Next.js
# Changes to React components, pages, and API routes will auto-reload
```

### Database Operations
```bash
# View database with Prisma Studio
open http://localhost:5555

# Run migrations
docker-compose exec web npx prisma migrate dev

# Reset database and reseed
docker-compose exec web npx prisma migrate reset

# Generate Prisma client (after schema changes)
docker-compose exec web npx prisma generate
```

### Email Testing
```bash
# View sent emails
open http://localhost:8025

# All emails sent by the application will appear in Mailhog
# No actual emails are sent in development
```

### File Storage Testing
```bash
# Access MinIO console
open http://localhost:9001

# Login with: minioadmin / minioadmin123
# Buckets are automatically created:
# - tts-pms-uploads (for weekly uploads)
# - tts-pms-screenshots (for agent screenshots)  
# - tts-pms-documents (for application documents)
```

## 🔧 Service Configuration

### MySQL Database
- **Host**: mysql (internal) / localhost (external)
- **Port**: 3306
- **Database**: tts_pms
- **Username**: tts_user
- **Password**: tts_password
- **Root Password**: rootpassword

### MinIO (S3 Storage)
- **API Endpoint**: http://localhost:9000
- **Console**: http://localhost:9001
- **Access Key**: minioadmin
- **Secret Key**: minioadmin123
- **Buckets**: Auto-created with public read access

### Redis Cache
- **Host**: redis (internal) / localhost (external)
- **Port**: 6379
- **Password**: redispassword

### Mailhog (Email Testing)
- **SMTP Host**: mailhog (internal) / localhost (external)
- **SMTP Port**: 1025
- **Web UI**: http://localhost:8025
- **No authentication required**

## 🐛 Troubleshooting

### Common Issues

#### Services Won't Start
```bash
# Check if ports are already in use
netstat -tulpn | grep :3000
netstat -tulpn | grep :3306

# Stop conflicting services
sudo service mysql stop
sudo service redis-server stop

# Restart Docker services
docker-compose down
docker-compose up -d
```

#### Database Connection Issues
```bash
# Check MySQL health
docker-compose exec mysql mysqladmin ping -h localhost -u root -prootpassword

# View MySQL logs
docker-compose logs mysql

# Reset database
docker-compose down -v
docker-compose up -d mysql
docker-compose run --rm db-migrate
```

#### Web Application Issues
```bash
# View application logs
docker-compose logs web

# Restart web service
docker-compose restart web

# Rebuild web container
docker-compose build web
docker-compose up -d web
```

#### File Upload Issues
```bash
# Check MinIO health
curl http://localhost:9000/minio/health/live

# View MinIO logs
docker-compose logs minio

# Recreate buckets
docker-compose restart minio-client
```

### Health Checks
```bash
# Check all service health
docker-compose ps

# Test application health
curl http://localhost:3000/api/health

# Test database connection
docker-compose exec web npx prisma db pull
```

### Logs and Debugging
```bash
# View all logs
docker-compose logs

# View specific service logs
docker-compose logs web
docker-compose logs mysql
docker-compose logs minio

# Follow logs in real-time
docker-compose logs -f web

# View last 100 lines
docker-compose logs --tail=100 web
```

## 🧪 Testing Features

### Job System Testing
```bash
# Initialize job scheduler
curl -X POST http://localhost:3000/api/jobs/init \
  -H "Authorization: Bearer admin-secret-token-123"

# Run a job manually
curl -X POST http://localhost:3000/api/jobs/run \
  -H "Authorization: Bearer admin-secret-token-123" \
  -H "Content-Type: application/json" \
  -d '{"jobName": "hourly-aggregation", "force": true}'

# Check job status
curl http://localhost:3000/api/jobs/init
```

### Agent API Testing
```bash
# Login as agent
curl -X POST http://localhost:3000/api/agent/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "employee1@tts-pms.com",
    "password": "employee123",
    "deviceId": "TEST-DEVICE-001"
  }'

# Use the returned token and deviceSecret for subsequent requests
```

### Email Testing
1. Go to http://localhost:8025
2. Trigger an email action in the app (registration, password reset, etc.)
3. View the email in Mailhog interface

### File Upload Testing
1. Go to http://localhost:9001 (MinIO Console)
2. Login with minioadmin/minioadmin123
3. Upload files through the application
4. Verify files appear in the appropriate buckets

## 🚀 Production Deployment

### Environment Variables
Update `.env` for production:

```bash
# Security
NEXTAUTH_SECRET="production-secret-here"
JWT_SECRET="production-jwt-secret-here"
AGENT_DEVICE_SECRET="production-agent-secret-here"

# Database (use managed service)
DATABASE_URL="mysql://user:pass@prod-db:3306/tts_pms"

# Email (use real SMTP)
SMTP_HOST="smtp.sendgrid.net"
SMTP_PORT=587
SMTP_USER="apikey"
SMTP_PASSWORD="your-sendgrid-api-key"

# Storage (use AWS S3)
S3_ENDPOINT="" # Leave empty for AWS
S3_ACCESS_KEY_ID="your-aws-key"
S3_SECRET_ACCESS_KEY="your-aws-secret"
S3_BUCKET_NAME="your-production-bucket"
```

### Docker Production Build
```bash
# Build production image
docker build -t tts-pms-web ./packages/web

# Run with production environment
docker run -d \
  --name tts-pms-production \
  -p 3000:3000 \
  --env-file .env.production \
  tts-pms-web
```

## 📚 Additional Resources

- [Next.js Documentation](https://nextjs.org/docs)
- [Prisma Documentation](https://www.prisma.io/docs)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [MinIO Documentation](https://docs.min.io/)

## 🆘 Getting Help

If you encounter issues:

1. Check the troubleshooting section above
2. View service logs: `docker-compose logs [service-name]`
3. Check service health: `docker-compose ps`
4. Restart services: `docker-compose restart [service-name]`
5. Reset everything: `docker-compose down -v && docker-compose up -d`

## 🧹 Cleanup

### Stop Services
```bash
# Stop all services
docker-compose down

# Stop and remove volumes (WARNING: This deletes all data)
docker-compose down -v

# Remove all containers and images
docker-compose down --rmi all
```

### Reset Development Environment
```bash
# Complete reset
docker-compose down -v
docker system prune -f
docker-compose up -d
docker-compose run --rm db-migrate
```
