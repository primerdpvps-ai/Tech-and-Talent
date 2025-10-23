import { NextAuthOptions } from 'next-auth';
import CredentialsProvider from 'next-auth/providers/credentials';
import bcrypt from 'bcryptjs';
import { prisma } from '@tts-pms/db';
import { LoginSchema, UserRole } from '@tts-pms/infra';

export const authOptions: NextAuthOptions = {
  providers: [
    CredentialsProvider({
      name: 'credentials',
      credentials: {
        email: { label: 'Email', type: 'email' },
        password: { label: 'Password', type: 'password' }
      },
      async authorize(credentials) {
        if (!credentials?.email || !credentials?.password) {
          return null;
        }

        const validation = LoginSchema.safeParse(credentials);
        if (!validation.success) {
          return null;
        }

        const user = await prisma.user.findUnique({
          where: { email: credentials.email }
        });

        if (!user) {
          return null;
        }

        const isPasswordValid = await bcrypt.compare(
          credentials.password,
          user.passwordHash
        );

        if (!isPasswordValid) {
          return null;
        }

        return {
          id: user.id,
          email: user.email,
          fullName: user.fullName,
          role: user.role,
          coreLocked: user.coreLocked,
        };
      }
    })
  ],
  session: {
    strategy: 'jwt',
    maxAge: 24 * 60 * 60, // 24 hours
  },
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.role = user.role;
        token.fullName = user.fullName;
        token.coreLocked = user.coreLocked;
      }
      return token;
    },
    async session({ session, token }) {
      if (token) {
        session.user.id = token.sub!;
        session.user.role = token.role as UserRole;
        session.user.fullName = token.fullName as string;
        session.user.coreLocked = token.coreLocked as boolean;
      }
      return session;
    },
  },
  pages: {
    signIn: '/auth/signin',
    signOut: '/auth/signin',
  },
  secret: process.env.NEXTAUTH_SECRET,
};

// RBAC Helper Functions
export function hasRole(userRole: UserRole, requiredRole: UserRole): boolean {
  const roleHierarchy = {
    [UserRole.VISITOR]: 0,
    [UserRole.CANDIDATE]: 1,
    [UserRole.NEW_EMPLOYEE]: 2,
    [UserRole.EMPLOYEE]: 3,
    [UserRole.MANAGER]: 4,
    [UserRole.CEO]: 5,
  };

  return roleHierarchy[userRole] >= roleHierarchy[requiredRole];
}

export function requireRole(userRole: UserRole, requiredRole: UserRole): boolean {
  if (!hasRole(userRole, requiredRole)) {
    throw new Error(`Access denied. Required role: ${requiredRole}, user role: ${userRole}`);
  }
  return true;
}

export function getRoleDashboard(role: UserRole): string {
  switch (role) {
    case UserRole.CEO:
      return '/dashboard/ceo';
    case UserRole.MANAGER:
      return '/dashboard/manager';
    case UserRole.EMPLOYEE:
    case UserRole.NEW_EMPLOYEE:
      return '/dashboard/employee';
    case UserRole.CANDIDATE:
      return '/dashboard/candidate';
    case UserRole.VISITOR:
    default:
      return '/dashboard/visitor';
  }
}
