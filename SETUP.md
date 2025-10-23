# TTS PMS Setup Guide

## Quick Start

1. **Run the setup script:**
   ```bash
   npm run setup
   ```

2. **Configure your environment:**
   - Edit `.env` with your database credentials
   - Update other environment variables as needed

3. **Set up the database:**
   ```bash
   # Generate Prisma client
   npm run prisma:generate
   
   # Run the initial migration
   npm run prisma:migrate
   
   # Seed the database
   npm run prisma:seed
   ```

4. **Start development:**
   ```bash
   npm run dev
   ```

## Manual Setup (Alternative)

If you prefer to set up manually:

### 1. Install Dependencies
```bash
npm install
```

### 2. Build Packages
```bash
npm run build --workspace=@tts-pms/infra
npm run build --workspace=@tts-pms/db
```

### 3. Environment Configuration
```bash
cp .env.example .env
# Edit .env with your values
```

### 4. Database Setup
```bash
# Generate Prisma client
npm run prisma:generate

# Run migrations
npm run prisma:migrate

# Seed database
npm run prisma:seed
```

### 5. Start Development Server
```bash
npm run dev
```

## Default Login Credentials

After seeding, use these accounts:

- **CEO:** `ceo@tts-pms.com` / `ceo123`
- **Manager:** `manager@tts-pms.com` / `manager123`
- **Employee:** `employee@tts-pms.com` / `employee123`
- **New Employee:** `newemployee@tts-pms.com` / `newemployee123`
- **Candidate:** `candidate@tts-pms.com` / `candidate123`

## Available Commands

### Development
- `npm run dev` - Start development servers
- `npm run build` - Build for production
- `npm run start` - Start production servers
- `npm run lint` - Run linting
- `npm run format` - Format code

### Database
- `npm run prisma:migrate` - Run database migrations
- `npm run prisma:generate` - Generate Prisma client
- `npm run prisma:studio` - Open Prisma Studio
- `npm run prisma:seed` - Seed database

### Testing
- `npm run test` - Run unit tests
- `npm run test:e2e` - Run e2e tests

## Troubleshooting

### Common Issues

1. **TypeScript errors after setup:**
   - Run `npm run build` to build all packages
   - Restart your IDE/TypeScript server

2. **Database connection issues:**
   - Check your DATABASE_URL in .env
   - Ensure MySQL is running
   - Verify database exists

3. **Module resolution errors:**
   - Run `npm install` in the root directory
   - Check that workspace packages are properly linked

### Getting Help

1. Check the main README.md for detailed documentation
2. Review the .env.example for required environment variables
3. Ensure all dependencies are installed with `npm install`

## Next Steps

After setup:

1. Customize the database schema in `packages/db/prisma/schema.prisma`
2. Add new components in `packages/web/src/components/`
3. Create new API routes in `packages/web/src/app/api/`
4. Add shared types in `packages/infra/src/types/`

Happy coding! 🚀
