import { NextRequest, NextResponse } from 'next/server';
import { z } from 'zod';
import { prisma, LegalDocumentType } from '@tts-pms/db';
import { createApiError, createApiResponse, UserRole } from '@tts-pms/infra';
import { requireRole } from '@/lib/rbac';

const UpdateLegalDocumentSchema = z.object({
  title: z.string().min(1).optional(),
  content: z.string().min(1).optional(),
  effectiveDate: z.string().min(1).optional(),
  version: z.string().min(1).optional(),
  published: z.boolean().optional(),
});

export async function PUT(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  const guard = await requireRole(request, UserRole.CEO);
  if (!guard.success) {
    return guard.response;
  }

  try {
    const documentId = params.id;
    const body = await request.json();
    const parseResult = UpdateLegalDocumentSchema.safeParse(body);

    if (!parseResult.success) {
      return NextResponse.json(
        createApiError('Invalid legal document payload', 'VALIDATION_ERROR'),
        { status: 400 }
      );
    }

    const existing = await prisma.legalDocument.findUnique({
      where: { id: documentId },
    });

    if (!existing) {
      return NextResponse.json(
        createApiError('Legal document not found', 'NOT_FOUND'),
        { status: 404 }
      );
    }

    const payload = parseResult.data;
    const updated = await prisma.legalDocument.update({
      where: { id: documentId },
      data: {
        title: payload.title ?? existing.title,
        content: payload.content ?? existing.content,
        effectiveDate: payload.effectiveDate
          ? new Date(payload.effectiveDate)
          : existing.effectiveDate,
        version: payload.version ?? existing.version,
        published: payload.published ?? existing.published,
      },
    });

    return NextResponse.json(
      createApiResponse(updated, 'Legal document updated'),
    );
  } catch (error) {
    console.error('Failed to update legal document:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}

export async function DELETE(
  request: NextRequest,
  { params }: { params: { id: string } }
) {
  const guard = await requireRole(request, UserRole.CEO);
  if (!guard.success) {
    return guard.response;
  }

  try {
    const documentId = params.id;
    await prisma.legalDocument.delete({ where: { id: documentId } });

    return NextResponse.json(
      createApiResponse({ id: documentId }, 'Legal document deleted'),
    );
  } catch (error) {
    console.error('Failed to delete legal document:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
