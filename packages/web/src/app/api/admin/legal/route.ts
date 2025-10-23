import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma, LegalDocumentType } from '@tts-pms/db';
import { createApiError, createApiResponse, UserRole } from '@tts-pms/infra';
import { requireRole } from '@/lib/rbac';

const LegalDocumentSchema = z.object({
  type: z.nativeEnum(LegalDocumentType),
  title: z.string().min(1),
  content: z.string().min(1),
  effectiveDate: z.string().min(1),
  version: z.string().min(1),
  published: z.boolean().optional().default(true),
});

export async function GET(request: NextRequest) {
  const guard = await requireRole(request, UserRole.CEO);
  if (!guard.success) {
    return guard.response;
  }

  try {
    const { searchParams } = new URL(request.url);
    const typeParam = searchParams.get('type') as LegalDocumentType | null;

    const documents = await prisma.legalDocument.findMany({
      where: typeParam ? { type: typeParam } : undefined,
      orderBy: { updatedAt: 'desc' },
    });

    return NextResponse.json(createApiResponse(documents));
  } catch (error) {
    console.error('Failed to load legal documents:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

export async function POST(request: NextRequest) {
  const guard = await requireRole(request, UserRole.CEO);
  if (!guard.success) {
    return guard.response;
  }

  try {
    const body = await request.json();
    const parseResult = LegalDocumentSchema.safeParse(body);

    if (!parseResult.success) {
      return NextResponse.json(
        createApiError('Invalid legal document payload', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const payload = parseResult.data;

    const document = await prisma.legalDocument.create({
      data: {
        type: payload.type,
        title: payload.title,
        content: payload.content,
        effectiveDate: new Date(payload.effectiveDate),
        version: payload.version,
        published: payload.published,
      },
    });

    return NextResponse.json(
      createApiResponse(document, 'Legal document created'),
      { status: 201 }
    );
  } catch (error) {
    console.error('Failed to create legal document:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
