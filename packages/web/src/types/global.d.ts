// Temporary type declarations for missing modules
// Remove this file after running npm install

/// <reference types="node" />

declare module 'react' {
  export type ReactNode = any;
  export type FC<P = {}> = (props: P) => ReactNode;
  
  export interface ChangeEvent<T = Element> extends SyntheticEvent<T> {
    target: EventTarget & T;
  }
  
  export interface FormEvent<T = Element> extends SyntheticEvent<T> {
  }
  
  export interface SyntheticEvent<T = Element, E = Event> {
    currentTarget: EventTarget & T;
    target: EventTarget & (T | null);
    preventDefault(): void;
    stopPropagation(): void;
  }
  
  export interface HTMLInputElement {
    value: string;
    checked: boolean;
  }
  
  export interface HTMLSelectElement {
    value: string;
  }
  
  export interface HTMLTextAreaElement {
    value: string;
  }
  
  export function useState<T>(initialState: T): [T, (value: T | ((prev: T) => T)) => void];
  export function useEffect(effect: () => void | (() => void), deps?: any[]): void;
  export function Fragment(props: { children: ReactNode }): ReactNode;
  
  const React: {
    useState: typeof useState;
    useEffect: typeof useEffect;
    Fragment: typeof Fragment;
    ChangeEvent: typeof ChangeEvent;
    FormEvent: typeof FormEvent;
  };
  
  export default React;
}

declare module 'next/link' {
  import React from 'react';
  interface LinkProps {
    href: string;
    children: React.ReactNode;
    className?: string;
  }
  declare const Link: React.FC<LinkProps>;
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
    // Add other models as needed
  }
}

declare module '@playwright/test' {
  export interface Page {
    goto(url: string): Promise<void>;
    click(selector: string): Promise<void>;
    fill(selector: string, value: string): Promise<void>;
    waitForURL(url: string | RegExp): Promise<void>;
    waitForSelector(selector: string, options?: any): Promise<void>;
    locator(selector: string): any;
  }
  
  export const test: {
    (name: string, fn: (args: { page: Page }) => Promise<void>): void;
  };
  
  export const expect: any;
}

declare module 'crypto' {
  export function createHmac(algorithm: string, key: string): {
    update(data: string): {
      digest(encoding: string): string;
    };
  };
  export function timingSafeEqual(a: Buffer, b: Buffer): boolean;
}

// Global Node.js types
declare global {
  namespace NodeJS {
    interface ProcessEnv {
      JWT_SECRET?: string;
      AGENT_DEVICE_SECRET?: string;
      DATABASE_URL?: string;
      NODE_ENV?: string;
    }
  }

  var process: {
    env: NodeJS.ProcessEnv;
  };

  var Buffer: {
    from(data: string): Buffer;
  };

  interface Buffer {
    // Buffer interface
  }
}

// JSX types
declare global {
  namespace JSX {
    interface IntrinsicElements {
      [elemName: string]: any;
    }
  }
}

export {};
