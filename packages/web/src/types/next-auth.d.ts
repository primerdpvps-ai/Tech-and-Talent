import { DefaultSession, DefaultUser } from 'next-auth';
import { JWT, DefaultJWT } from 'next-auth/jwt';
import { UserRole } from '@tts-pms/infra';

declare module 'next-auth' {
  interface Session {
    user: {
      id: string;
      role: UserRole;
      fullName: string;
      coreLocked: boolean;
    } & DefaultSession['user'];
  }

  interface User extends DefaultUser {
    role: UserRole;
    fullName: string;
    coreLocked: boolean;
  }
}

declare module 'next-auth/jwt' {
  interface JWT extends DefaultJWT {
    role: UserRole;
    fullName: string;
    coreLocked: boolean;
  }
}
