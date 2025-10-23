import { NextRequest, NextResponse } from 'next/server';
import { getServerSession } from 'next-auth';
import { authOptions, hasRole, getRoleDashboard } from '@/lib/auth';
import { UserRole, createApiError } from '@tts-pms/infra';

export interface RoleGuardOptions {
  requiredRole: UserRole;
  redirectOnFail?: boolean;
  allowSameUser?: boolean; // Allow if the user is accessing their own resource
}

/**
 * Server-side role guard for API routes
 */
export async function requireRole(
  request: NextRequest,
  requiredRole: UserRole,
  options: { allowSameUser?: boolean; userIdParam?: string } = {}
): Promise<{ success: true; session: any } | { success: false; response: NextResponse }> {
  const session = await getServerSession(authOptions);
  
  if (!session) {
    return {
      success: false,
      response: NextResponse.json(
        createApiError('Authentication required', 'UNAUTHORIZED'),
        { status: 401 }
      )
    };
  }

  // Check role hierarchy
  if (!hasRole(session.user.role, requiredRole)) {
    // If allowSameUser is true, check if user is accessing their own resource
    if (options.allowSameUser && options.userIdParam) {
      const { searchParams } = new URL(request.url);
      const userId = searchParams.get(options.userIdParam) || 
                   request.nextUrl.pathname.split('/').pop();
      
      if (userId === session.user.id) {
        return { success: true, session };
      }
    }

    return {
      success: false,
      response: NextResponse.json(
        createApiError(
          `Access denied. Required role: ${requiredRole}, your role: ${session.user.role}`,
          'INSUFFICIENT_PERMISSIONS'
        ),
        { status: 403 }
      )
    };
  }

  return { success: true, session };
}

/**
 * Middleware function for role-based route protection
 */
export function createRoleMiddleware(options: RoleGuardOptions) {
  return async function roleMiddleware(request: NextRequest) {
    const session = await getServerSession(authOptions);
    
    if (!session) {
      if (options.redirectOnFail) {
        return NextResponse.redirect(new URL('/auth/signin', request.url));
      }
      return NextResponse.json(
        createApiError('Authentication required', 'UNAUTHORIZED'),
        { status: 401 }
      );
    }

    if (!hasRole(session.user.role, options.requiredRole)) {
      if (options.redirectOnFail) {
        // Redirect to appropriate dashboard based on user's role
        const dashboardUrl = getRoleDashboard(session.user.role);
        return NextResponse.redirect(new URL(dashboardUrl, request.url));
      }
      
      return NextResponse.json(
        createApiError(
          `Access denied. Required role: ${options.requiredRole}`,
          'INSUFFICIENT_PERMISSIONS'
        ),
        { status: 403 }
      );
    }

    return NextResponse.next();
  };
}

/**
 * Client-side role check hook (for components)
 */
export function useRoleGuard(requiredRole: UserRole, userRole?: UserRole): boolean {
  if (!userRole) return false;
  return hasRole(userRole, requiredRole);
}

/**
 * Higher-order component for role-based component protection
 */
export function withRoleGuard<P extends object>(
  Component: React.ComponentType<P>,
  requiredRole: UserRole,
  fallback?: React.ComponentType
) {
  return function RoleGuardedComponent(props: P) {
    // This would typically use a session hook
    // For now, we'll assume the role is passed as a prop
    const userRole = (props as any).userRole as UserRole;
    
    if (!hasRole(userRole, requiredRole)) {
      if (fallback) {
        const FallbackComponent = fallback;
        return <FallbackComponent />;
      }
      return (
        <div className="p-4 text-center">
          <h2 className="text-xl font-semibold text-red-600">Access Denied</h2>
          <p className="text-gray-600">
            You don't have permission to view this content.
          </p>
        </div>
      );
    }

    return <Component {...props} />;
  };
}

/**
 * Route protection configuration for different user roles
 */
export const ROLE_ROUTES = {
  [UserRole.VISITOR]: [
    '/dashboard/visitor',
    '/profile',
    '/evaluation',
  ],
  [UserRole.CANDIDATE]: [
    '/dashboard/candidate',
    '/profile',
    '/evaluation',
    '/application',
  ],
  [UserRole.NEW_EMPLOYEE]: [
    '/dashboard/employee',
    '/profile',
    '/timesheet',
    '/training',
  ],
  [UserRole.EMPLOYEE]: [
    '/dashboard/employee',
    '/profile',
    '/timesheet',
    '/payroll',
    '/leave',
  ],
  [UserRole.MANAGER]: [
    '/dashboard/manager',
    '/profile',
    '/team',
    '/applications',
    '/approvals',
    '/reports',
  ],
  [UserRole.CEO]: [
    '/dashboard/ceo',
    '/profile',
    '/admin',
    '/analytics',
    '/system',
  ],
};

/**
 * Check if a user can access a specific route
 */
export function canAccessRoute(userRole: UserRole, route: string): boolean {
  // CEO can access everything
  if (userRole === UserRole.CEO) return true;
  
  // Check role-specific routes
  const allowedRoutes = ROLE_ROUTES[userRole] || [];
  return allowedRoutes.some(allowedRoute => 
    route.startsWith(allowedRoute) || allowedRoute.startsWith(route)
  );
}
