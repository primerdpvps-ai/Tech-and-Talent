# TTS PMS - Project Management System

A comprehensive TypeScript monorepo for TTS (Time Tracking System) Project Management built with Next.js 14, Prisma, and modern web technologies.

## 🏗️ Architecture

This is a monorepo containing three main packages:

- **`packages/web`** - Next.js 14 application with App Router and React Server Components
- **`packages/db`** - Prisma schema and database client
- **`packages/infra`** - Shared types, utilities, and validation schemas

## 🚀 Tech Stack

### Frontend
- **Next.js 14** with App Router and Server Components
- **TypeScript** for type safety
- **Tailwind CSS** + **Headless UI** for styling
- **React Hook Form** + **Zod** for form validation
- **Chart.js** for data visualization

### Backend & Database
- **Prisma ORM** with MySQL
- **NextAuth.js** for authentication (Credentials provider)
- **Server Actions** for server-side logic

### Development & Testing

### Database
- **Primary**: MySQL 8.x with complete TTS schema
- **Backup**: Automated daily backups
- **Performance**: Optimized indexes and query caching
- **Security**: Encrypted connections and data protection

## 📦 Project Structure

```
tts_pms/
├── index.php                    # Main landing page (Divi-compatible)
├── admin/                       # Complete admin panel
│   ├── index.php               # Dashboard with statistics
│   ├── login.php               # Secure authentication
│   ├── payroll-automation.php  # Automated payroll system
│   └── payslip-generator.php   # PDF payslip generation
├── api/                        # RESTful API endpoints
│   ├── submit-contact.php      # Contact form handler
│   └── submit-proposal.php     # Proposal request handler
├── config/                     # System configuration
│   ├── database.php            # Database connection
│   └── init.php               # Application initialization
├── database/                   # Database schemas
│   ├── schema-cpanel.sql       # cPanel-ready schema
│   └── tts_mysql_schema.sql    # Complete TTS schema
├── packages/web/               # Next.js frontend (optional)
│   └── src/                    # React components and pages
└── deployment/                 # Deployment guides and checklists
```

## 🚀 cPanel Deployment Ready

### Pre-Upload Checklist ✅
- [x] Complete PHP application with all features
- [x] MySQL database schema (cPanel compatible)
- [x] Admin panel with full functionality
- [x] API endpoints for all operations
- [x] Security implementation (OTP, RBAC, validation)
- [x] Payroll automation system
- [x] PDF payslip generation
- [x] File upload handling
- [x] Error logging and monitoring
- [x] Documentation and setup guides

### Deployment Package Contents
- `npm run prisma:generate` - Generate Prisma client
- `npm run prisma:studio` - Open Prisma Studio
- `npm run prisma:seed` - Seed database with initial data

### Testing
- `npm run test` - Run unit tests with Vitest
- `npm run test:e2e` - Run end-to-end tests with Playwright

## 🔧 Configuration

### Environment Variables

Key environment variables (see `.env.example` for full list):

```bash
# Database
DATABASE_URL="mysql://username:password@localhost:3306/tts_pms"

# Authentication
NEXTAUTH_SECRET="your-secret-key"
NEXTAUTH_URL="http://localhost:3000"

# Application Settings
APP_PUBLIC_OPERATIONAL_WINDOW="11:00-02:00"
APP_PUBLIC_SPECIAL_WINDOW="02:00-06:00"
APP_HOURLY_RATE=125
APP_STREAK_BONUS=500
APP_SECURITY_FUND=1000
```

### Role-Based Access Control (RBAC)

The system includes four user roles:

- **ADMIN** - Full system access
- **MANAGER** - Project and team management
- **AGENT** - Task execution and time tracking
- **CLIENT** - Project visibility and reporting

## 🏢 Features

### Core Functionality
- ✅ User authentication and authorization
- ✅ Project and task management
- ✅ Time tracking and timesheets
- ✅ Role-based access control
- ✅ Dashboard with analytics

### Planned Features
- 📊 Advanced reporting and analytics
- 📧 Email notifications
- 📱 SMS notifications
- 📁 File upload management
- 💰 Payroll integration
- 🔌 Agent API with JWT/HMAC security

## 🧪 Testing

### Unit Tests
```bash
npm run test
```

### E2E Tests
```bash
npm run test:e2e
```

### Test Coverage
Tests cover:
- Authentication flows
- Component rendering
- Form validation
- API endpoints
- Database operations

## 🚀 Deployment

### Production Build
```bash
npm run build
npm run start
```

### Environment Setup
1. Set up production database
2. Configure environment variables
3. Run database migrations
4. Build and deploy the application

## 📚 Development Guidelines

### Code Style
- Use TypeScript for all new code
- Follow ESLint and Prettier configurations
- Write tests for new features
- Use conventional commit messages

### Database Changes
1. Create Prisma migration: `npm run prisma:migrate`
2. Update seed file if needed
3. Test migrations in development

### Adding New Features
1. Define types in `packages/infra`
2. Update database schema in `packages/db`
3. Implement UI in `packages/web`
4. Add tests for new functionality

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## 📄 License

This project is private and proprietary.

---

**TTS PMS** - Built with ❤️ using modern web technologies
