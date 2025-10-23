declare module 'jsonwebtoken' {
  export function sign(payload: any, secret: string, options?: any): string;
  export function verify(token: string, secret: string): any;
}

declare module '@prisma/client' {
  export class PrismaClient {
    constructor();
    user: any;
    agentDevice: any;
    timerSession: any;
    dailySummary: any;
    agentRequestLog: any;
    jobLog: any;
    payrollBatch: any;
    notification: any;
    penalty: any;
    bonus: any;
    employment: any;
    application: any;
    gig: any;
    systemSettings: any;
    payrollWeek: any;
    $queryRaw: any;
    $disconnect(): Promise<void>;
  }
}

declare module 'crypto' {
  export function createHmac(algorithm: string, key: string): {
    update(data: string): {
      digest(encoding: string): string;
    };
  };
  export function timingSafeEqual(a: Buffer, b: Buffer): boolean;
}

declare module '@playwright/test' {
  export interface Page {
    goto(url: string): Promise<void>;
    click(selector: string): Promise<void>;
    fill(selector: string, value: string): Promise<void>;
    waitForURL(url: string | RegExp): Promise<void>;
    waitForSelector(selector: string, options?: any): Promise<void>;
    locator(selector: string): any;
    keyboard: {
      press(key: string): Promise<void>;
    };
  }
  
  export const test: {
    (name: string, fn: (args: { page: Page }) => Promise<void>): void;
  };
  
  export const expect: any;
}

declare module 'next/link' {
  import { ReactNode } from 'react';
  interface LinkProps {
    href: string;
    children: ReactNode;
    className?: string;
  }
  declare const Link: (props: LinkProps) => ReactNode;
  export default Link;
}

declare module 'next/navigation' {
  export function useRouter(): {
    push: (url: string) => void;
    replace: (url: string) => void;
    back: () => void;
  };
  export function useSearchParams(): URLSearchParams;
}
