import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError, ClientRequestStatus } from '@tts-pms/infra';

const CreateClientRequestSchema = z.object({
  businessName: z.string().min(1, 'Business name is required'),
  contactEmail: z.string().email('Valid email is required'),
  contactPhone: z.string().optional(),
  brief: z.string().min(10, 'Brief must be at least 10 characters'),
  attachments: z.record(z.string()).optional(),
});

// POST /api/client-requests - Submit client request (public)
export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const validation = CreateClientRequestSchema.safeParse(body);
    if (!validation.success) {
      return NextResponse.json(
        createApiError('Invalid client request data', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const { businessName, contactEmail, contactPhone, brief, attachments } = validation.data;

    // Check for duplicate requests from same email within 24 hours
    const recentRequest = await prisma.clientRequest.findFirst({
      where: {
        contactEmail,
        createdAt: {
          gte: new Date(Date.now() - 24 * 60 * 60 * 1000), // 24 hours ago
        }
      }
    });

    if (recentRequest) {
      return NextResponse.json(
        createApiError('You have already submitted a request within the last 24 hours', 'DUPLICATE_REQUEST'),
        { status: 429 }
      );
    }

    // Create client request
    const clientRequest = await prisma.clientRequest.create({
      data: {
        businessName,
        contactEmail,
        contactPhone,
        brief,
        attachments,
        status: ClientRequestStatus.NEW,
      }
    });

    return NextResponse.json(
      createApiResponse({
        id: clientRequest.id,
        businessName: clientRequest.businessName,
        contactEmail: clientRequest.contactEmail,
        status: clientRequest.status,
        createdAt: clientRequest.createdAt,
        referenceNumber: `CR-${clientRequest.id.slice(-8).toUpperCase()}`,
        nextSteps: [
          'Your request has been received and assigned a reference number',
          'Our team will review your requirements within 24-48 hours',
          'You will receive an email with next steps and potential solutions',
          'For urgent matters, please call our business line'
        ]
      }, 'Client request submitted successfully'),
      { status: 201 }
    );

  } catch (error) {
    console.error('Client request submission error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

// GET /api/client-requests - Get client requests (admin only)
export async function GET(request: NextRequest) {
  try {
    // This endpoint would typically require admin authentication
    // For now, we'll implement basic functionality
    
    const { searchParams } = new URL(request.url);
    const status = searchParams.get('status') as ClientRequestStatus | null;
    const search = searchParams.get('search');
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '10');
    const offset = (page - 1) * limit;

    const where: any = {};
    
    if (status) {
      where.status = status;
    }

    if (search) {
      where.OR = [
        { businessName: { contains: search, mode: 'insensitive' } },
        { contactEmail: { contains: search, mode: 'insensitive' } },
        { brief: { contains: search, mode: 'insensitive' } },
      ];
    }

    const [requests, total] = await Promise.all([
      prisma.clientRequest.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        skip: offset,
        take: limit,
      }),
      prisma.clientRequest.count({ where })
    ]);

    // Add reference numbers to requests
    const requestsWithRefs = requests.map(req => ({
      ...req,
      referenceNumber: `CR-${req.id.slice(-8).toUpperCase()}`,
    }));

    // Calculate status summary
    const statusSummary = await prisma.clientRequest.groupBy({
      by: ['status'],
      _count: { status: true },
    });

    const statusCounts = statusSummary.reduce((acc, item) => {
      acc[item.status] = item._count.status;
      return acc;
    }, {} as Record<string, number>);

    return NextResponse.json(
      createApiResponse({
        requests: requestsWithRefs,
        pagination: {
          page,
          limit,
          total,
          totalPages: Math.ceil(total / limit),
          hasNext: page * limit < total,
          hasPrev: page > 1,
        },
        summary: {
          total,
          statusCounts,
          recentRequests: requests.filter(r => 
            new Date(r.createdAt).getTime() > Date.now() - 7 * 24 * 60 * 60 * 1000
          ).length, // Last 7 days
        }
      })
    );

  } catch (error) {
    console.error('Get client requests error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
