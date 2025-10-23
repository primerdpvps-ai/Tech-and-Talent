import { PrismaClient, UserRole, EvaluationResult, JobType, ApplicationStatus } from '@prisma/client';
import bcrypt from 'bcryptjs';

const prisma = new PrismaClient();

async function main() {
  console.log('🌱 Starting database seed...');

  // Create CEO user
  const ceoPassword = await bcrypt.hash('ceo123', 12);
  const ceo = await prisma.user.upsert({
    where: { email: 'ceo@tts-pms.com' },
    update: {},
    create: {
      email: 'ceo@tts-pms.com',
      passwordHash: ceoPassword,
      fullName: 'Chief Executive Officer',
      role: UserRole.CEO,
      city: 'New York',
      province: 'NY',
      country: 'USA',
      coreLocked: false,
    },
  });

  // Create manager user
  const managerPassword = await bcrypt.hash('manager123', 12);
  const manager = await prisma.user.upsert({
    where: { email: 'manager@tts-pms.com' },
    update: {},
    create: {
      email: 'manager@tts-pms.com',
      passwordHash: managerPassword,
      fullName: 'John Manager',
      role: UserRole.MANAGER,
      city: 'Los Angeles',
      province: 'CA',
      country: 'USA',
      coreLocked: false,
    },
  });

  // Create employee user
  const employeePassword = await bcrypt.hash('employee123', 12);
  const employee = await prisma.user.upsert({
    where: { email: 'employee@tts-pms.com' },
    update: {},
    create: {
      email: 'employee@tts-pms.com',
      passwordHash: employeePassword,
      fullName: 'Jane Employee',
      role: UserRole.EMPLOYEE,
      city: 'Chicago',
      province: 'IL',
      country: 'USA',
      coreLocked: false,
    },
  });

  // Create new employee user
  const newEmployeePassword = await bcrypt.hash('newemployee123', 12);
  const newEmployee = await prisma.user.upsert({
    where: { email: 'newemployee@tts-pms.com' },
    update: {},
    create: {
      email: 'newemployee@tts-pms.com',
      passwordHash: newEmployeePassword,
      fullName: 'Alex NewEmployee',
      role: UserRole.NEW_EMPLOYEE,
      city: 'Miami',
      province: 'FL',
      country: 'USA',
      coreLocked: false,
    },
  });

  // Create candidate user
  const candidatePassword = await bcrypt.hash('candidate123', 12);
  const candidate = await prisma.user.upsert({
    where: { email: 'candidate@tts-pms.com' },
    update: {},
    create: {
      email: 'candidate@tts-pms.com',
      passwordHash: candidatePassword,
      fullName: 'Sam Candidate',
      role: UserRole.CANDIDATE,
      city: 'Seattle',
      province: 'WA',
      country: 'USA',
      coreLocked: false,
    },
  });

  // Create employment records for employees
  await prisma.employment.upsert({
    where: { userId: employee.id },
    update: {},
    create: {
      userId: employee.id,
      rdpHost: 'rdp.company.com',
      rdpUsername: 'jane.employee',
      startDate: new Date('2023-01-15'),
      firstPayrollEligibleFrom: new Date('2023-01-22'),
      securityFundDeducted: true,
    },
  });

  await prisma.employment.upsert({
    where: { userId: newEmployee.id },
    update: {},
    create: {
      userId: newEmployee.id,
      rdpHost: 'rdp.company.com',
      rdpUsername: 'alex.newemployee',
      startDate: new Date('2024-01-01'),
      firstPayrollEligibleFrom: new Date('2024-01-08'),
      securityFundDeducted: false,
    },
  });

  // Create evaluation for candidate
  await prisma.evaluation.create({
    data: {
      userId: candidate.id,
      age: 28,
      deviceType: 'Desktop',
      ramText: '16GB DDR4',
      processorText: 'Intel Core i7-10700K',
      stableInternet: true,
      provider: 'Comcast',
      linkSpeed: '100 Mbps',
      numUsers: 2,
      speedtestUrl: 'https://speedtest.net/result/12345',
      profession: 'Software Developer',
      dailyTimeOk: true,
      timeWindows: ['09:00-17:00', '19:00-23:00'],
      qualification: 'Bachelor in Computer Science',
      confidentialityOk: true,
      typingOk: true,
      result: EvaluationResult.ELIGIBLE,
      attempts: 1,
    },
  });

  // Create application for candidate
  await prisma.application.create({
    data: {
      userId: candidate.id,
      jobType: JobType.FULL_TIME,
      status: ApplicationStatus.APPROVED,
      decidedByUserId: manager.id,
      decidedAt: new Date(),
      files: {
        resume: 'resume_sam_candidate.pdf',
        coverLetter: 'cover_letter.pdf'
      },
    },
  });

  // Create sample gigs
  await prisma.gig.createMany({
    data: [
      {
        slug: 'data-entry-basic',
        title: 'Basic Data Entry',
        description: 'Simple data entry tasks for beginners',
        price: 15.00,
        badges: ['Entry Level', 'Remote'],
        active: true,
      },
      {
        slug: 'content-moderation',
        title: 'Content Moderation',
        description: 'Review and moderate user-generated content',
        price: 20.00,
        badges: ['Intermediate', 'Flexible Hours'],
        active: true,
      },
      {
        slug: 'customer-support',
        title: 'Customer Support Specialist',
        description: 'Handle customer inquiries and support tickets',
        price: 25.00,
        badges: ['Advanced', 'Communication Skills'],
        active: true,
      },
    ],
    skipDuplicates: true,
  });

  // Create sample client requests
  await prisma.clientRequest.createMany({
    data: [
      {
        businessName: 'Tech Startup Inc.',
        contactEmail: 'contact@techstartup.com',
        contactPhone: '+1-555-0123',
        brief: 'We need help with data processing and customer support for our growing platform.',
        attachments: {
          requirements: 'project_requirements.pdf',
          budget: 'budget_outline.xlsx'
        },
        status: 'NEW',
      },
      {
        businessName: 'E-commerce Solutions',
        contactEmail: 'hello@ecommerce.com',
        contactPhone: '+1-555-0456',
        brief: 'Looking for content moderation services for our marketplace.',
        status: 'IN_REVIEW',
      },
    ],
    skipDuplicates: true,
  });

  console.log('✅ Database seeded successfully!');
  console.log('👤 Users created:');
  console.log(`   CEO: ceo@tts-pms.com / ceo123`);
  console.log(`   Manager: manager@tts-pms.com / manager123`);
  console.log(`   Employee: employee@tts-pms.com / employee123`);
  console.log(`   New Employee: newemployee@tts-pms.com / newemployee123`);
  console.log(`   Candidate: candidate@tts-pms.com / candidate123`);
  console.log('📊 Sample data created for evaluations, applications, gigs, and client requests');
}

main()
  .catch((e) => {
    console.error('❌ Seed failed:', e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
