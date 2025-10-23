import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, EvaluationResult, UserRole } from '@tts-pms/infra';
import { authOptions } from '@/lib/auth';

const EvaluationSubmissionSchema = z.object({
  age: z.number().min(18, 'Must be at least 18 years old').max(100),
  deviceType: z.string().min(1, 'Device type is required'),
  ramText: z.string().min(1, 'RAM information is required'),
  processorText: z.string().min(1, 'Processor information is required'),
  stableInternet: z.boolean(),
  provider: z.string().min(1, 'Internet provider is required'),
  linkSpeed: z.string().min(1, 'Link speed is required'),
  numUsers: z.number().min(1, 'Number of users is required'),
  speedtestUrl: z.string().url('Valid speedtest URL is required'),
  profession: z.string().min(1, 'Profession is required'),
  dailyTimeOk: z.boolean(),
  timeWindows: z.array(z.string()),
  qualification: z.string().min(1, 'Qualification is required'),
  confidentialityOk: z.boolean(),
  typingOk: z.boolean(),
});

interface EvaluationDecision {
  result: EvaluationResult;
  reasons: string[];
  score: number;
}

function evaluateSubmission(data: z.infer<typeof EvaluationSubmissionSchema>): EvaluationDecision {
  const reasons: string[] = [];
  let score = 0;

  // Age evaluation
  if (data.age < 18) {
    reasons.push('Must be at least 18 years old');
    return { result: EvaluationResult.REJECTED, reasons, score: 0 };
  }
  if (data.age >= 18 && data.age <= 65) score += 10;

  // Device and technical requirements
  const ramMatch = data.ramText.toLowerCase();
  if (ramMatch.includes('16gb') || ramMatch.includes('32gb') || ramMatch.includes('64gb')) {
    score += 15;
  } else if (ramMatch.includes('8gb')) {
    score += 10;
  } else if (ramMatch.includes('4gb')) {
    score += 5;
    reasons.push('RAM may be insufficient for optimal performance');
  } else {
    reasons.push('Insufficient RAM specification');
  }

  // Processor evaluation
  const processorMatch = data.processorText.toLowerCase();
  if (processorMatch.includes('i7') || processorMatch.includes('i9') || processorMatch.includes('ryzen 7') || processorMatch.includes('ryzen 9')) {
    score += 15;
  } else if (processorMatch.includes('i5') || processorMatch.includes('ryzen 5')) {
    score += 10;
  } else if (processorMatch.includes('i3') || processorMatch.includes('ryzen 3')) {
    score += 5;
    reasons.push('Processor may not meet performance requirements');
  } else {
    reasons.push('Processor specification unclear or insufficient');
  }

  // Internet stability
  if (!data.stableInternet) {
    reasons.push('Stable internet connection is required');
    score -= 10;
  } else {
    score += 10;
  }

  // Internet speed evaluation
  const speedMatch = data.linkSpeed.toLowerCase();
  if (speedMatch.includes('100') || speedMatch.includes('200') || parseInt(speedMatch) >= 100) {
    score += 15;
  } else if (speedMatch.includes('50') || parseInt(speedMatch) >= 50) {
    score += 10;
  } else if (speedMatch.includes('25') || parseInt(speedMatch) >= 25) {
    score += 5;
    reasons.push('Internet speed may be marginal for some tasks');
  } else {
    reasons.push('Internet speed insufficient');
  }

  // Multiple users on connection
  if (data.numUsers > 5) {
    reasons.push('Too many users sharing internet connection');
    score -= 5;
  } else if (data.numUsers <= 2) {
    score += 5;
  }

  // Professional background
  const techProfessions = ['developer', 'programmer', 'engineer', 'analyst', 'designer', 'it', 'computer', 'software'];
  if (techProfessions.some(prof => data.profession.toLowerCase().includes(prof))) {
    score += 10;
  }

  // Availability
  if (!data.dailyTimeOk) {
    reasons.push('Daily time commitment not confirmed');
    score -= 5;
  } else {
    score += 10;
  }

  // Time windows (should have at least one operational window)
  if (data.timeWindows.length === 0) {
    reasons.push('No available time windows specified');
    score -= 10;
  } else {
    score += 5;
  }

  // Essential requirements
  if (!data.confidentialityOk) {
    reasons.push('Confidentiality agreement not accepted');
    return { result: EvaluationResult.REJECTED, reasons, score: 0 };
  } else {
    score += 10;
  }

  if (!data.typingOk) {
    reasons.push('Typing skills requirement not met');
    score -= 5;
  } else {
    score += 5;
  }

  // Decision logic
  if (score >= 80) {
    return { result: EvaluationResult.ELIGIBLE, reasons: [], score };
  } else if (score >= 60) {
    return { result: EvaluationResult.PENDING, reasons, score };
  } else {
    return { result: EvaluationResult.REJECTED, reasons, score };
  }
}

// POST /api/evaluation - Submit evaluation
export async function POST(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    // Only visitors and candidates can submit evaluations
    if (![UserRole.VISITOR, UserRole.CANDIDATE].includes(session.user.role)) {
      return NextResponse.json(
        createApiError('Evaluation not available for your role', 'INVALID_ROLE'),
        { status: 403 }
      );
    }

    const body = await request.json();
    const validation = EvaluationSubmissionSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid evaluation data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    // Check if user already has a recent evaluation
    const existingEvaluation = await prisma.evaluation.findFirst({
      where: {
        userId: session.user.id,
        createdAt: {
          gte: new Date(Date.now() - 24 * 60 * 60 * 1000), // 24 hours ago
        }
      }
    });

    if (existingEvaluation) {
      return NextResponse.json(
        createApiError('You can only submit one evaluation per 24 hours', 'RATE_LIMITED'),
        { status: 429 }
      );
    }

    // Run evaluation decision logic
    const decision = evaluateSubmission(validation.data);

    // Create evaluation record
    const evaluation = await prisma.evaluation.create({
      data: {
        userId: session.user.id,
        ...validation.data,
        result: decision.result,
        reasons: decision.reasons.length > 0 ? decision.reasons : null,
        attempts: 1,
      }
    });

    // Update user role if eligible
    if (decision.result === EvaluationResult.ELIGIBLE && session.user.role === UserRole.VISITOR) {
      await prisma.user.update({
        where: { id: session.user.id },
        data: { role: UserRole.CANDIDATE }
      });
    }

    return NextResponse.json(
      createApiResponse({
        id: evaluation.id,
        result: decision.result,
        reasons: decision.reasons,
        score: decision.score,
        canReapply: decision.result === EvaluationResult.REJECTED,
        nextSteps: decision.result === EvaluationResult.ELIGIBLE 
          ? ['You can now submit job applications']
          : decision.result === EvaluationResult.PENDING
          ? ['Your evaluation is under review', 'You will be contacted within 48 hours']
          : ['Please review the feedback and improve your setup', 'You can reapply after 7 days']
      }, 'Evaluation submitted successfully'),
      { status: 201 }
    );

  } catch (error) {
    console.error('Evaluation submission error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

// GET /api/evaluation - Get user's evaluations
export async function GET(request: NextRequest) {
  try {
    const session = await getServerSession(authOptions);
    if (!session) {
      return NextResponse.json(
        createApiError('Unauthorized', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    const evaluations = await prisma.evaluation.findMany({
      where: { userId: session.user.id },
      orderBy: { createdAt: 'desc' },
      select: {
        id: true,
        result: true,
        reasons: true,
        attempts: true,
        createdAt: true,
      }
    });

    return NextResponse.json(createApiResponse(evaluations));

  } catch (error) {
    console.error('Get evaluations error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
