-- CreateTable
CREATE TABLE `users` (
    `id` VARCHAR(191) NOT NULL,
    `email` VARCHAR(191) NOT NULL,
    `emailVerified` DATETIME(3) NULL,
    `phone` VARCHAR(191) NULL,
    `phoneVerified` DATETIME(3) NULL,
    `passwordHash` VARCHAR(191) NOT NULL,
    `fullName` VARCHAR(191) NOT NULL,
    `dob` DATE NULL,
    `city` VARCHAR(191) NULL,
    `province` VARCHAR(191) NULL,
    `country` VARCHAR(191) NULL,
    `address` TEXT NULL,
    `coreLocked` BOOLEAN NOT NULL DEFAULT false,
    `role` ENUM('VISITOR', 'CANDIDATE', 'NEW_EMPLOYEE', 'EMPLOYEE', 'MANAGER', 'CEO') NOT NULL DEFAULT 'VISITOR',
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `updatedAt` DATETIME(3) NOT NULL,

    UNIQUE INDEX `users_email_key`(`email`),
    INDEX `users_email_idx`(`email`),
    INDEX `users_role_idx`(`role`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `evaluations` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(191) NOT NULL,
    `age` INTEGER NULL,
    `deviceType` VARCHAR(191) NULL,
    `ramText` VARCHAR(191) NULL,
    `processorText` VARCHAR(191) NULL,
    `stableInternet` BOOLEAN NULL,
    `provider` VARCHAR(191) NULL,
    `linkSpeed` VARCHAR(191) NULL,
    `numUsers` INTEGER NULL,
    `speedtestUrl` VARCHAR(191) NULL,
    `profession` VARCHAR(191) NULL,
    `dailyTimeOk` BOOLEAN NULL,
    `timeWindows` JSON NULL,
    `qualification` VARCHAR(191) NULL,
    `confidentialityOk` BOOLEAN NULL,
    `typingOk` BOOLEAN NULL,
    `result` ENUM('ELIGIBLE', 'PENDING', 'REJECTED') NULL,
    `reasons` JSON NULL,
    `attempts` INTEGER NOT NULL DEFAULT 1,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `evaluations_userId_idx`(`userId`),
    INDEX `evaluations_result_idx`(`result`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `applications` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(191) NOT NULL,
    `jobType` ENUM('FULL_TIME', 'PART_TIME') NOT NULL,
    `status` ENUM('UNDER_REVIEW', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'UNDER_REVIEW',
    `reasons` JSON NULL,
    `submittedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `decidedAt` DATETIME(3) NULL,
    `decidedByUserId` VARCHAR(191) NULL,
    `files` JSON NULL,

    INDEX `applications_userId_idx`(`userId`),
    INDEX `applications_status_idx`(`status`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `employment` (
    `userId` VARCHAR(191) NOT NULL,
    `rdpHost` VARCHAR(191) NULL,
    `rdpUsername` VARCHAR(191) NULL,
    `startDate` DATE NOT NULL,
    `firstPayrollEligibleFrom` DATE NOT NULL,
    `securityFundDeducted` BOOLEAN NOT NULL DEFAULT false,

    PRIMARY KEY (`userId`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `timer_sessions` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(191) NOT NULL,
    `startedAt` DATETIME(3) NOT NULL,
    `endedAt` DATETIME(3) NULL,
    `activeSeconds` INTEGER NOT NULL DEFAULT 0,
    `deviceId` VARCHAR(191) NULL,
    `ip` VARCHAR(191) NULL,
    `inactivityPauses` JSON NULL,

    INDEX `timer_sessions_userId_idx`(`userId`),
    INDEX `timer_sessions_startedAt_idx`(`startedAt`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `daily_summaries` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(191) NOT NULL,
    `date` DATE NOT NULL,
    `billableSeconds` INTEGER NOT NULL DEFAULT 0,
    `uploadsDone` BOOLEAN NOT NULL DEFAULT false,
    `meetsDailyMinimum` BOOLEAN NOT NULL DEFAULT false,

    UNIQUE INDEX `daily_summaries_userId_date_key`(`userId`, `date`),
    INDEX `daily_summaries_userId_idx`(`userId`),
    INDEX `daily_summaries_date_idx`(`date`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `payroll_weeks` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(191) NOT NULL,
    `weekStart` DATE NOT NULL,
    `weekEnd` DATE NOT NULL,
    `hoursDecimal` DECIMAL(5, 2) NOT NULL,
    `baseAmount` DECIMAL(10, 2) NOT NULL,
    `streakBonus` DECIMAL(10, 2) NOT NULL DEFAULT 0,
    `deductions` JSON NULL,
    `finalAmount` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('PENDING', 'PROCESSING', 'PAID', 'DELAYED') NOT NULL DEFAULT 'PENDING',
    `paidAt` DATETIME(3) NULL,
    `reference` VARCHAR(191) NULL,

    UNIQUE INDEX `payroll_weeks_userId_weekStart_key`(`userId`, `weekStart`),
    INDEX `payroll_weeks_userId_idx`(`userId`),
    INDEX `payroll_weeks_status_idx`(`status`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `leaves` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(191) NOT NULL,
    `type` ENUM('SHORT', 'ONE_DAY', 'LONG') NOT NULL,
    `dateFrom` DATE NOT NULL,
    `dateTo` DATE NOT NULL,
    `noticeHours` INTEGER NOT NULL,
    `status` ENUM('PENDING', 'APPROVED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    `penalties` JSON NULL,
    `requestedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `decidedAt` DATETIME(3) NULL,
    `decidedByUserId` VARCHAR(191) NULL,

    INDEX `leaves_userId_idx`(`userId`),
    INDEX `leaves_status_idx`(`status`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `penalties` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(191) NOT NULL,
    `policyArea` VARCHAR(191) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `reason` TEXT NOT NULL,
    `payrollWeekId` VARCHAR(191) NOT NULL,

    INDEX `penalties_userId_idx`(`userId`),
    INDEX `penalties_payrollWeekId_idx`(`payrollWeekId`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `recordings` (
    `id` VARCHAR(191) NOT NULL,
    `userId` VARCHAR(191) NOT NULL,
    `weekStart` DATE NOT NULL,
    `fileKey` VARCHAR(191) NOT NULL,
    `uploadedAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
    `validated` BOOLEAN NOT NULL DEFAULT false,

    INDEX `recordings_userId_idx`(`userId`),
    INDEX `recordings_weekStart_idx`(`weekStart`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `gigs` (
    `id` VARCHAR(191) NOT NULL,
    `slug` VARCHAR(191) NOT NULL,
    `title` VARCHAR(191) NOT NULL,
    `description` TEXT NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `badges` JSON NULL,
    `active` BOOLEAN NOT NULL DEFAULT true,
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    UNIQUE INDEX `gigs_slug_key`(`slug`),
    INDEX `gigs_active_idx`(`active`),
    INDEX `gigs_slug_idx`(`slug`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- CreateTable
CREATE TABLE `client_requests` (
    `id` VARCHAR(191) NOT NULL,
    `businessName` VARCHAR(191) NOT NULL,
    `contactEmail` VARCHAR(191) NOT NULL,
    `contactPhone` VARCHAR(191) NULL,
    `brief` TEXT NOT NULL,
    `attachments` JSON NULL,
    `status` ENUM('NEW', 'IN_REVIEW', 'CLOSED') NOT NULL DEFAULT 'NEW',
    `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),

    INDEX `client_requests_status_idx`(`status`),
    INDEX `client_requests_contactEmail_idx`(`contactEmail`),
    PRIMARY KEY (`id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- AddForeignKey
ALTER TABLE `evaluations` ADD CONSTRAINT `evaluations_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `applications` ADD CONSTRAINT `applications_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `applications` ADD CONSTRAINT `applications_decidedByUserId_fkey` FOREIGN KEY (`decidedByUserId`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `employment` ADD CONSTRAINT `employment_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `timer_sessions` ADD CONSTRAINT `timer_sessions_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `daily_summaries` ADD CONSTRAINT `daily_summaries_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `payroll_weeks` ADD CONSTRAINT `payroll_weeks_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `leaves` ADD CONSTRAINT `leaves_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `leaves` ADD CONSTRAINT `leaves_decidedByUserId_fkey` FOREIGN KEY (`decidedByUserId`) REFERENCES `users`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `penalties` ADD CONSTRAINT `penalties_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `penalties` ADD CONSTRAINT `penalties_payrollWeekId_fkey` FOREIGN KEY (`payrollWeekId`) REFERENCES `payroll_weeks`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE `recordings` ADD CONSTRAINT `recordings_userId_fkey` FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
