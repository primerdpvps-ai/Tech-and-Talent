import { NextRequest, NextResponse } from 'next/server';
import { prisma } from '@tts-pms/db';
import { createApiResponse, createApiError } from '@tts-pms/infra';

// GET /api/gigs - Get available gigs (public)
export async function GET(request: NextRequest) {
  try {
    const { searchParams } = new URL(request.url);
    const active = searchParams.get('active');
    const search = searchParams.get('search');
    const minPrice = searchParams.get('minPrice');
    const maxPrice = searchParams.get('maxPrice');
    const badges = searchParams.get('badges')?.split(',');
    const page = parseInt(searchParams.get('page') || '1');
    const limit = parseInt(searchParams.get('limit') || '10');
    const offset = (page - 1) * limit;

    const where: any = {};
    
    // Filter by active status (default to active only)
    if (active !== 'false') {
      where.active = true;
    } else if (active === 'false') {
      where.active = false;
    }

    // Search in title and description
    if (search) {
      where.OR = [
        { title: { contains: search, mode: 'insensitive' } },
        { description: { contains: search, mode: 'insensitive' } },
      ];
    }

    // Price range filter
    if (minPrice || maxPrice) {
      where.price = {};
      if (minPrice) where.price.gte = parseFloat(minPrice);
      if (maxPrice) where.price.lte = parseFloat(maxPrice);
    }

    // Badge filter (if badges contain any of the specified badges)
    if (badges && badges.length > 0) {
      where.badges = {
        path: '$',
        array_contains: badges,
      };
    }

    const [gigs, total] = await Promise.all([
      prisma.gig.findMany({
        where,
        orderBy: [
          { active: 'desc' }, // Active gigs first
          { createdAt: 'desc' }
        ],
        skip: offset,
        take: limit,
      }),
      prisma.gig.count({ where })
    ]);

    // Get available badges for filtering
    const allGigs = await prisma.gig.findMany({
      where: { active: true },
      select: { badges: true }
    });
    
    const availableBadges = Array.from(
      new Set(
        allGigs
          .flatMap(gig => gig.badges as string[] || [])
          .filter(Boolean)
      )
    ).sort();

    // Calculate price range
    const priceStats = await prisma.gig.aggregate({
      where: { active: true },
      _min: { price: true },
      _max: { price: true },
      _avg: { price: true },
    });

    return NextResponse.json(
      createApiResponse({
        gigs,
        pagination: {
          page,
          limit,
          total,
          totalPages: Math.ceil(total / limit),
          hasNext: page * limit < total,
          hasPrev: page > 1,
        },
        filters: {
          availableBadges,
          priceRange: {
            min: priceStats._min.price || 0,
            max: priceStats._max.price || 0,
            average: priceStats._avg.price || 0,
          }
        }
      })
    );

  } catch (error) {
    console.error('Get gigs error:', error);
    return NextResponse.json(
      createApiError('Internal server error', 'INTERNAL_ERROR'),
      { status: 500 }
    );
  }
}
