#!/usr/bin/env node

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('🚀 Setting up TTS PMS...\n');

// Check if .env exists
const envPath = path.join(__dirname, '..', '.env');
if (!fs.existsSync(envPath)) {
  console.log('📝 Creating .env file from .env.example...');
  const exampleEnv = fs.readFileSync(path.join(__dirname, '..', '.env.example'), 'utf8');
  fs.writeFileSync(envPath, exampleEnv);
  console.log('✅ .env file created. Please update it with your actual values.\n');
} else {
  console.log('✅ .env file already exists.\n');
}

try {
  console.log('📦 Installing dependencies...');
  execSync('npm install', { stdio: 'inherit', cwd: path.join(__dirname, '..') });
  console.log('✅ Dependencies installed.\n');

  console.log('🔧 Building packages...');
  execSync('npm run build --workspace=@tts-pms/infra', { stdio: 'inherit', cwd: path.join(__dirname, '..') });
  execSync('npm run build --workspace=@tts-pms/db', { stdio: 'inherit', cwd: path.join(__dirname, '..') });
  console.log('✅ Packages built.\n');

  console.log('🗄️  Generating Prisma client...');
  execSync('npm run prisma:generate', { stdio: 'inherit', cwd: path.join(__dirname, '..') });
  console.log('✅ Prisma client generated.\n');

  console.log('🎯 Setup complete!\n');
  console.log('Next steps:');
  console.log('1. Update your .env file with actual database credentials');
  console.log('2. Run: npm run prisma:migrate');
  console.log('3. Run: npm run prisma:seed');
  console.log('4. Run: npm run dev');
  console.log('\n🎉 Happy coding!');

} catch (error) {
  console.error('❌ Setup failed:', error.message);
  process.exit(1);
}
