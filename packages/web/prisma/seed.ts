import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcryptjs';

const prisma = new PrismaClient();

async function main() {
  console.log('🌱 Starting database seed...');

  // Clear existing data (in development only)
  if (process.env.NODE_ENV === 'development') {
    console.log('🧹 Clearing existing data...');
    await prisma.weeklyUpload.deleteMany();
    await prisma.penalty.deleteMany();
    await prisma.bonus.deleteMany();
    await prisma.dailySummary.deleteMany();
    await prisma.timerSession.deleteMany();
    await prisma.application.deleteMany();
    await prisma.employment.deleteMany();
    await prisma.gig.deleteMany();
    await prisma.user.deleteMany();
    await prisma.systemSettings.deleteMany();
  }

  // Create system settings
  console.log('⚙️ Creating system settings...');
  const systemSettings = [
    { key: 'base_hourly_rate', value: '15', description: 'Base hourly rate for employees' },
    { key: 'manager_hourly_rate', value: '25', description: 'Hourly rate for managers' },
    { key: 'weekend_multiplier', value: '1.5', description: 'Weekend work multiplier' },
    { key: 'overtime_multiplier', value: '1.5', description: 'Overtime work multiplier' },
    { key: 'performance_bonus_90', value: '100', description: 'Performance bonus for 90%+ performance' },
    { key: 'performance_bonus_95', value: '150', description: 'Performance bonus for 95%+ performance' },
    { key: 'perfect_attendance_weekly', value: '50', description: 'Weekly perfect attendance bonus' },
    { key: 'referral_bonus', value: '500', description: 'Employee referral bonus' },
    { key: 'penalty_late_15min', value: '5', description: 'Penalty for 15 minute late arrival' },
    { key: 'penalty_late_30min', value: '10', description: 'Penalty for 30 minute late arrival' },
    { key: 'penalty_unexcused_absence', value: '50', description: 'Penalty for unexcused absence' },
    { key: 'penalty_missing_weekly_upload', value: '30', description: 'Penalty for missing weekly upload' },
    { key: 'working_hours_start', value: '09:00', description: 'Standard working hours start time' },
    { key: 'working_hours_end', value: '17:00', description: 'Standard working hours end time' },
    { key: 'working_days', value: 'Monday,Tuesday,Wednesday,Thursday,Friday', description: 'Standard working days' },
    { key: 'minimum_daily_hours', value: '6', description: 'Minimum daily working hours' },
  ];

  for (const setting of systemSettings) {
    await prisma.systemSettings.upsert({
      where: { key: setting.key },
      update: { value: setting.value, description: setting.description },
      create: setting,
    });
  }

  // Create CEO/Admin user
  console.log('👑 Creating CEO/Admin user...');
  const ceoUser = await prisma.user.create({
    data: {
      email: 'ceo@tts-pms.com',
      passwordHash: await bcrypt.hash('admin123', 12),
      fullName: 'John CEO',
      role: 'CEO',
      isEmailVerified: true,
      createdAt: new Date(),
    },
  });

  // Create Manager user
  console.log('👨‍💼 Creating Manager user...');
  const managerUser = await prisma.user.create({
    data: {
      email: 'manager@tts-pms.com',
      passwordHash: await bcrypt.hash('manager123', 12),
      fullName: 'Sarah Manager',
      role: 'MANAGER',
      isEmailVerified: true,
      createdAt: new Date(),
    },
  });

  // Create Manager employment record
  await prisma.employment.create({
    data: {
      userId: managerUser.id,
      startDate: new Date('2024-01-01'),
      rdpHost: 'rdp-server-01.tts-pms.com',
      rdpUsername: 'sarah.manager',
      rdpPassword: 'encrypted_rdp_password',
      isActive: true,
    },
  });

  // Create sample employees
  console.log('👥 Creating sample employees...');
  const employees = [
    {
      email: 'candidate@tts-pms.com',
      fullName: 'Mike Candidate',
      role: 'VISITOR',
      password: 'candidate123',
    },
    {
      email: 'newemployee@tts-pms.com',
      fullName: 'Lisa NewEmployee',
      role: 'NEW_EMPLOYEE',
      password: 'newemployee123',
    },
    {
      email: 'employee1@tts-pms.com',
      fullName: 'David Employee',
      role: 'EMPLOYEE',
      password: 'employee123',
    },
    {
      email: 'employee2@tts-pms.com',
      fullName: 'Emma Worker',
      role: 'EMPLOYEE',
      password: 'employee123',
    },
    {
      email: 'employee3@tts-pms.com',
      fullName: 'James Staff',
      role: 'EMPLOYEE',
      password: 'employee123',
    },
  ];

  const createdEmployees = [];
  for (const emp of employees) {
    const user = await prisma.user.create({
      data: {
        email: emp.email,
        passwordHash: await bcrypt.hash(emp.password, 12),
        fullName: emp.fullName,
        role: emp.role as any,
        isEmailVerified: true,
        createdAt: new Date(),
      },
    });
    createdEmployees.push(user);

    // Create employment records for employees (not visitors)
    if (emp.role !== 'VISITOR') {
      await prisma.employment.create({
        data: {
          userId: user.id,
          managerId: managerUser.id,
          startDate: new Date('2024-02-01'),
          rdpHost: 'rdp-server-02.tts-pms.com',
          rdpUsername: user.email.split('@')[0],
          rdpPassword: 'encrypted_rdp_password',
          isActive: true,
        },
      });
    }
  }

  // Create sample gigs
  console.log('💼 Creating sample gigs...');
  const gigs = [
    {
      title: 'Data Entry Specialist',
      description: 'Accurate data entry and database management for various client projects.',
      longDescription: `We are looking for a detail-oriented Data Entry Specialist to join our team. You will be responsible for:

• Entering data from various sources into our database systems
• Maintaining data accuracy and integrity
• Performing quality checks on completed work
• Meeting daily productivity targets
• Following established procedures and guidelines

This is a remote position with flexible hours. Perfect for someone who enjoys working with data and has strong attention to detail.`,
      requirements: [
        'High school diploma or equivalent',
        'Typing speed of at least 40 WPM',
        'Strong attention to detail',
        'Basic computer skills',
        'Reliable internet connection',
      ],
      benefits: [
        'Flexible working hours',
        'Work from home',
        'Performance bonuses',
        'Weekly payments',
        'Career advancement opportunities',
      ],
      hourlyRate: 15.00,
      category: 'DATA_ENTRY',
      isActive: true,
      featured: true,
    },
    {
      title: 'Virtual Assistant',
      description: 'Provide administrative support and customer service for multiple clients.',
      longDescription: `Join our team as a Virtual Assistant and help businesses streamline their operations. Your responsibilities will include:

• Managing email correspondence and scheduling
• Customer service via chat and phone
• Basic bookkeeping and invoice management
• Social media management
• Research and data compilation
• General administrative tasks

We offer comprehensive training and ongoing support to help you succeed in this role.`,
      requirements: [
        'Excellent English communication skills',
        'Previous customer service experience',
        'Proficiency in Microsoft Office',
        'Strong organizational skills',
        'Ability to multitask effectively',
      ],
      benefits: [
        'Competitive hourly rate',
        'Flexible schedule',
        'Training provided',
        'Growth opportunities',
        'Supportive team environment',
      ],
      hourlyRate: 18.00,
      category: 'VIRTUAL_ASSISTANT',
      isActive: true,
      featured: true,
    },
    {
      title: 'Content Writer',
      description: 'Create engaging content for websites, blogs, and marketing materials.',
      longDescription: `We are seeking a talented Content Writer to create compelling content across various platforms. You will:

• Write blog posts, articles, and web content
• Create marketing copy and product descriptions
• Develop social media content
• Conduct research on industry topics
• Optimize content for SEO
• Collaborate with the marketing team

This role is perfect for someone with a passion for writing and digital marketing.`,
      requirements: [
        'Bachelor\'s degree in English, Marketing, or related field',
        'Excellent writing and editing skills',
        'SEO knowledge preferred',
        'Portfolio of published work',
        'Ability to meet deadlines',
      ],
      benefits: [
        'Creative freedom',
        'Byline opportunities',
        'Professional development',
        'Flexible deadlines',
        'Remote work environment',
      ],
      hourlyRate: 22.00,
      category: 'CONTENT_WRITING',
      isActive: true,
      featured: false,
    },
    {
      title: 'Customer Support Representative',
      description: 'Provide excellent customer service via chat, email, and phone support.',
      longDescription: `Join our customer support team and help clients resolve their issues efficiently. Your role includes:

• Responding to customer inquiries via multiple channels
• Troubleshooting technical issues
• Processing orders and returns
• Maintaining customer satisfaction metrics
• Documenting common issues and solutions
• Escalating complex cases to supervisors

We provide extensive training and support tools to ensure your success.`,
      requirements: [
        'High school diploma required',
        'Previous customer service experience',
        'Strong problem-solving skills',
        'Patience and empathy',
        'Reliable internet and quiet workspace',
      ],
      benefits: [
        'Comprehensive training',
        'Performance incentives',
        'Career advancement',
        'Flexible shifts',
        'Team support',
      ],
      hourlyRate: 16.00,
      category: 'CUSTOMER_SUPPORT',
      isActive: true,
      featured: false,
    },
    {
      title: 'Social Media Manager',
      description: 'Manage social media accounts and create engaging content for various brands.',
      longDescription: `We are looking for a creative Social Media Manager to enhance our clients' online presence. You will:

• Develop and implement social media strategies
• Create and schedule engaging content
• Monitor social media metrics and analytics
• Respond to comments and messages
• Collaborate with design and marketing teams
• Stay updated on social media trends

This role offers great creative freedom and the opportunity to work with diverse brands.`,
      requirements: [
        'Experience with major social media platforms',
        'Content creation skills',
        'Knowledge of social media analytics',
        'Creative thinking and writing skills',
        'Understanding of brand voice and tone',
      ],
      benefits: [
        'Creative environment',
        'Diverse client portfolio',
        'Professional growth',
        'Flexible schedule',
        'Latest tools and software',
      ],
      hourlyRate: 20.00,
      category: 'SOCIAL_MEDIA',
      isActive: true,
      featured: true,
    },
    {
      title: 'Graphic Designer',
      description: 'Create visual designs for digital and print materials across various projects.',
      longDescription: `Join our creative team as a Graphic Designer and bring ideas to life through visual design. Your responsibilities include:

• Creating logos, brochures, and marketing materials
• Designing web graphics and social media content
• Developing brand identity elements
• Collaborating with clients on design requirements
• Preparing files for print and digital distribution
• Maintaining brand consistency across projects

We work with clients from various industries, offering diverse and exciting design challenges.`,
      requirements: [
        'Proficiency in Adobe Creative Suite',
        'Strong portfolio of design work',
        'Understanding of design principles',
        'Attention to detail',
        'Ability to work with client feedback',
      ],
      benefits: [
        'Creative projects',
        'Latest design software',
        'Portfolio building opportunities',
        'Flexible deadlines',
        'Collaborative team environment',
      ],
      hourlyRate: 25.00,
      category: 'DESIGN',
      isActive: true,
      featured: false,
    },
  ];

  for (const gigData of gigs) {
    await prisma.gig.create({
      data: {
        ...gigData,
        requirements: JSON.stringify(gigData.requirements),
        benefits: JSON.stringify(gigData.benefits),
        createdAt: new Date(),
        updatedAt: new Date(),
      },
    });
  }

  // Create sample applications
  console.log('📝 Creating sample applications...');
  const gig1 = await prisma.gig.findFirst({ where: { title: 'Data Entry Specialist' } });
  const gig2 = await prisma.gig.findFirst({ where: { title: 'Virtual Assistant' } });

  if (gig1) {
    // Application from candidate
    await prisma.application.create({
      data: {
        userId: createdEmployees[0].id, // Mike Candidate
        gigId: gig1.id,
        status: 'PENDING',
        personalInfo: {
          address: '123 Main St, Anytown, USA',
          phone: '+1-555-0123',
          emergencyContact: 'Jane Candidate - +1-555-0124',
          experience: '2 years of data entry experience at previous company',
        },
        documents: {
          resume: '/uploads/mike-candidate-resume.pdf',
          cnic: '/uploads/mike-candidate-cnic.jpg',
          utility: '/uploads/mike-candidate-utility.pdf',
        },
        submittedAt: new Date(),
      },
    });
  }

  if (gig2) {
    // Application from another user
    await prisma.application.create({
      data: {
        userId: createdEmployees[1].id, // Lisa NewEmployee
        gigId: gig2.id,
        status: 'APPROVED',
        personalInfo: {
          address: '456 Oak Ave, Somewhere, USA',
          phone: '+1-555-0125',
          emergencyContact: 'Bob NewEmployee - +1-555-0126',
          experience: '3 years of virtual assistant work, proficient in various software tools',
        },
        documents: {
          resume: '/uploads/lisa-newemployee-resume.pdf',
          cnic: '/uploads/lisa-newemployee-cnic.jpg',
          utility: '/uploads/lisa-newemployee-utility.pdf',
        },
        submittedAt: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000), // 7 days ago
        reviewedAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000), // 5 days ago
        reviewedBy: managerUser.id,
        reviewNotes: 'Excellent qualifications and experience. Approved for immediate start.',
      },
    });
  }

  // Create sample daily summaries for employees
  console.log('📊 Creating sample daily summaries...');
  const employees_with_employment = createdEmployees.slice(1); // Skip the candidate

  for (const employee of employees_with_employment) {
    // Create daily summaries for the last 7 days
    for (let i = 0; i < 7; i++) {
      const date = new Date();
      date.setDate(date.getDate() - i);
      date.setHours(0, 0, 0, 0);

      // Skip weekends
      if (date.getDay() === 0 || date.getDay() === 6) continue;

      const billableSeconds = Math.floor(Math.random() * 3600) + 21600; // 6-7 hours
      const meetsDailyMinimum = billableSeconds >= 21600;

      await prisma.dailySummary.create({
        data: {
          userId: employee.id,
          date,
          billableSeconds,
          meetsDailyMinimum,
          uploadsDone: Math.random() > 0.3, // 70% chance of uploads done
          lastUpdatedAt: new Date(),
        },
      });
    }
  }

  // Create sample timer sessions
  console.log('⏱️ Creating sample timer sessions...');
  for (const employee of employees_with_employment.slice(0, 2)) {
    // Create a completed session from yesterday
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    yesterday.setHours(9, 0, 0, 0);

    const endTime = new Date(yesterday);
    endTime.setHours(17, 0, 0, 0);

    await prisma.timerSession.create({
      data: {
        userId: employee.id,
        deviceId: `DEVICE-${employee.id}`,
        startedAt: yesterday,
        endedAt: endTime,
        activeSeconds: 28800, // 8 hours
        ip: '192.168.1.100',
        inactivityPauses: {
          breaks: [
            { start: '12:00:00', end: '13:00:00', reason: 'lunch' }
          ]
        },
        aggregatedAt: new Date(),
      },
    });
  }

  // Create sample bonuses and penalties
  console.log('💰 Creating sample bonuses and penalties...');
  const activeEmployee = employees_with_employment[2]; // David Employee

  // Perfect attendance bonus
  await prisma.bonus.create({
    data: {
      userId: activeEmployee.id,
      type: 'PERFECT_ATTENDANCE',
      amount: 50.00,
      description: 'Perfect attendance for the week',
      awardedAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
      awardedBy: managerUser.id,
    },
  });

  // Late arrival penalty
  await prisma.penalty.create({
    data: {
      userId: activeEmployee.id,
      type: 'LATE_ARRIVAL',
      amount: 10.00,
      description: 'Late arrival - 30 minutes',
      appliedAt: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000),
      appliedBy: managerUser.id,
    },
  });

  // Create sample notifications
  console.log('🔔 Creating sample notifications...');
  await prisma.notification.create({
    data: {
      userId: ceoUser.id,
      type: 'SYSTEM_ALERT',
      title: 'Welcome to TTS PMS',
      message: 'Your TTS PMS system has been successfully set up with sample data.',
      priority: 'MEDIUM',
      createdAt: new Date(),
    },
  });

  await prisma.notification.create({
    data: {
      userId: managerUser.id,
      type: 'APPLICATION_PENDING',
      title: 'New Application Pending Review',
      message: 'Mike Candidate has applied for the Data Entry Specialist position.',
      priority: 'HIGH',
      createdAt: new Date(),
    },
  });

  // Create initial job log entry
  console.log('📋 Creating initial job log...');
  await prisma.jobLog.create({
    data: {
      runId: `seed_${Date.now()}`,
      jobName: 'database-seed',
      description: 'Initial database seeding',
      status: 'COMPLETED',
      startedAt: new Date(),
      completedAt: new Date(),
      duration: 5000,
      result: {
        success: true,
        message: 'Database seeded successfully',
        recordsCreated: {
          users: employees.length + 2, // employees + CEO + manager
          gigs: gigs.length,
          applications: 2,
          dailySummaries: employees_with_employment.length * 5, // ~5 days per employee
          systemSettings: systemSettings.length,
        }
      },
      metadata: {
        environment: process.env.NODE_ENV || 'development',
        seedVersion: '1.0.0',
      },
    },
  });

  console.log('✅ Database seed completed successfully!');
  console.log('\n📋 Created accounts:');
  console.log('👑 CEO: ceo@tts-pms.com / admin123');
  console.log('👨‍💼 Manager: manager@tts-pms.com / manager123');
  console.log('🧑‍💼 Candidate: candidate@tts-pms.com / candidate123');
  console.log('👨‍💻 New Employee: newemployee@tts-pms.com / newemployee123');
  console.log('👩‍💻 Employee 1: employee1@tts-pms.com / employee123');
  console.log('👨‍💻 Employee 2: employee2@tts-pms.com / employee123');
  console.log('👩‍💻 Employee 3: employee3@tts-pms.com / employee123');
  console.log(`\n💼 Created ${gigs.length} sample gigs`);
  console.log('📝 Created 2 sample applications');
  console.log('📊 Created sample daily summaries and timer sessions');
  console.log('⚙️ Configured system settings');
}

main()
  .catch((e) => {
    console.error('❌ Seed failed:', e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
