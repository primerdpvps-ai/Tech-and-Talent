import { NextRequest, NextResponse } from 'next/server';
import { getToken } from 'next-auth/jwt';
import { UserRole } from '@tts-pms/infra';
import { getRoleDashboard, hasRole } from '@/lib/auth';
import { canAccessRoute } from '@/lib/rbac';

export async function middleware(request: NextRequest) {
  const token = await getToken({ 
    req: request, 
    secret: process.env.NEXTAUTH_SECRET 
  });

  const { pathname } = request.nextUrl;

  // Public routes that don't require authentication
  const publicRoutes = [
    '/auth/signin',
    '/auth/signup',
    '/api/auth',
    '/api/otp',
    '/_next',
    '/favicon.ico',
  ];

  const isPublicRoute = publicRoutes.some(route => pathname.startsWith(route));

  // Allow public routes
  if (isPublicRoute) {
    return NextResponse.next();
  }

  // Redirect to signin if not authenticated
  if (!token) {
    const signInUrl = new URL('/auth/signin', request.url);
    signInUrl.searchParams.set('callbackUrl', pathname);
    return NextResponse.redirect(signInUrl);
  }

  const userRole = token.role as UserRole;

  // Root path - redirect to appropriate dashboard
  if (pathname === '/') {
    const dashboardUrl = getRoleDashboard(userRole);
    return NextResponse.redirect(new URL(dashboardUrl, request.url));
  }

  // API routes - basic auth check (specific role checks in individual routes)
  if (pathname.startsWith('/api/')) {
    return NextResponse.next();
  }

  // Check route access permissions
  if (!canAccessRoute(userRole, pathname)) {
    // Redirect to appropriate dashboard if user can't access the route
    const dashboardUrl = getRoleDashboard(userRole);
    return NextResponse.redirect(new URL(dashboardUrl, request.url));
  }

  // Admin routes - require MANAGER or higher
  if (pathname.startsWith('/admin') && !hasRole(userRole, UserRole.MANAGER)) {
    const dashboardUrl = getRoleDashboard(userRole);
    return NextResponse.redirect(new URL(dashboardUrl, request.url));
  }

  // CEO-only routes
  if (pathname.startsWith('/system') && userRole !== UserRole.CEO) {
    const dashboardUrl = getRoleDashboard(userRole);
    return NextResponse.redirect(new URL(dashboardUrl, request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    /*
     * Match all request paths except for the ones starting with:
     * - _next/static (static files)
     * - _next/image (image optimization files)
     * - favicon.ico (favicon file)
     */
    '/((?!_next/static|_next/image|favicon.ico).*)',
  ],
};
