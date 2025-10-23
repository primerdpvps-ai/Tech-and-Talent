declare module 'react' {
  export type ReactNode = any;
  export type FC<P = {}> = (props: P) => ReactNode;
  
  export interface ChangeEvent<T = Element> extends SyntheticEvent<T> {
    target: EventTarget & T;
  }
  
  export interface FormEvent<T = Element> extends SyntheticEvent<T> {
    preventDefault(): void;
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
  
  namespace React {
    export type ReactNode = any;
    export type FC<P = {}> = (props: P) => ReactNode;
    export interface ChangeEvent<T = Element> extends SyntheticEvent<T> {
      target: EventTarget & T;
    }
    export interface FormEvent<T = Element> extends SyntheticEvent<T> {
      preventDefault(): void;
    }
    export interface SyntheticEvent<T = Element, E = Event> {
      currentTarget: EventTarget & T;
      target: EventTarget & (T | null);
      preventDefault(): void;
      stopPropagation(): void;
    }
  }
  
  export = React;
  export as namespace React;
}
