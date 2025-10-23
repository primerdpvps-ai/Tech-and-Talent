import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { SignInForm } from '../auth/signin-form';

// Mock next-auth/react
vi.mock('next-auth/react', () => ({
  signIn: vi.fn(),
}));

// Mock next/navigation
vi.mock('next/navigation', () => ({
  useRouter: () => ({
    push: vi.fn(),
    refresh: vi.fn(),
  }),
}));

describe('SignInForm', () => {
  it('renders sign in form', () => {
    render(<SignInForm />);
    
    expect(screen.getByLabelText(/email address/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/password/i)).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument();
  });

  it('shows demo account information', () => {
    render(<SignInForm />);
    
    expect(screen.getByText(/demo accounts/i)).toBeInTheDocument();
    expect(screen.getByText(/admin@tts-pms.com/)).toBeInTheDocument();
  });
});
