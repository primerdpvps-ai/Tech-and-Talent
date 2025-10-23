'use client';

import Link from 'next/link';
import { useSession, signOut } from 'next-auth/react';

export function QuickActions() {
  const { data: session } = useSession();

  if (!session) return null;

  const actions = [
    {
      name: 'New Project',
      href: '/projects/new',
      icon: '➕',
      description: 'Create a new project',
      roles: ['ADMIN', 'MANAGER'],
    },
    {
      name: 'Log Time',
      href: '/timesheet',
      icon: '⏰',
      description: 'Track your work hours',
      roles: ['AGENT', 'MANAGER'],
    },
    {
      name: 'View Projects',
      href: '/projects',
      icon: '📁',
      description: 'Browse all projects',
      roles: ['ADMIN', 'MANAGER', 'AGENT', 'CLIENT'],
    },
    {
      name: 'Reports',
      href: '/reports',
      icon: '📊',
      description: 'View analytics and reports',
      roles: ['ADMIN', 'MANAGER'],
    },
    {
      name: 'Team',
      href: '/team',
      icon: '👥',
      description: 'Manage team members',
      roles: ['ADMIN', 'MANAGER'],
    },
    {
      name: 'Settings',
      href: '/settings',
      icon: '⚙️',
      description: 'Account settings',
      roles: ['ADMIN', 'MANAGER', 'AGENT', 'CLIENT'],
    },
  ];

  const userActions = actions.filter(action => 
    action.roles.includes(session.user.role)
  );

  return (
    <div className="space-y-6">
      <div className="card">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
        <div className="space-y-2">
          {userActions.map((action) => (
            <Link
              key={action.name}
              href={action.href}
              className="flex items-center p-3 text-sm text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <span className="text-lg mr-3">{action.icon}</span>
              <div>
                <div className="font-medium">{action.name}</div>
                <div className="text-gray-500 text-xs">{action.description}</div>
              </div>
            </Link>
          ))}
        </div>
      </div>

      <div className="card">
        <h3 className="text-lg font-medium text-gray-900 mb-4">Account</h3>
        <div className="space-y-3">
          <div className="text-sm">
            <div className="font-medium text-gray-900">
              {session.user.firstName} {session.user.lastName}
            </div>
            <div className="text-gray-500">{session.user.email}</div>
            <div className="text-xs text-gray-400 mt-1">
              Role: {session.user.role}
            </div>
          </div>
          <button
            onClick={() => signOut({ callbackUrl: '/auth/signin' })}
            className="w-full text-left text-sm text-red-600 hover:text-red-800 transition-colors"
          >
            Sign out
          </button>
        </div>
      </div>
    </div>
  );
}
