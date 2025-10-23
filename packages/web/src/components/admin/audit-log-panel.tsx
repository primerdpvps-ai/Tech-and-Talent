'use client';

import { useState, useEffect } from 'react';

interface AuditLogEntry {
  id: string;
  timestamp: string;
  adminId: string;
  adminName: string;
  action: string;
  resource: string;
  resourceId?: string;
  details: Record<string, any>;
  ipAddress: string;
}

export function AuditLogPanel() {
  const [logs, setLogs] = useState<AuditLogEntry[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [filter, setFilter] = useState<'all' | 'applications' | 'employees' | 'payroll' | 'leaves' | 'settings'>('all');

  useEffect(() => {
    fetchAuditLogs();
  }, [filter]);

  const fetchAuditLogs = async () => {
    try {
      setIsLoading(true);
      const response = await fetch(`/api/admin/audit-logs?filter=${filter}&limit=50`);
      if (response.ok) {
        const data = await response.json();
        setLogs(data.logs || []);
      }
    } catch (error) {
      console.error('Failed to fetch audit logs:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const formatTimestamp = (timestamp: string) => {
    const date = new Date(timestamp);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getActionIcon = (action: string) => {
    switch (action.toLowerCase()) {
      case 'approve':
      case 'approved':
        return (
          <div className="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
            <svg className="w-3 h-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
        );
      case 'reject':
      case 'rejected':
        return (
          <div className="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
            <svg className="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
        );
      case 'create':
      case 'created':
        return (
          <div className="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
            <svg className="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
          </div>
        );
      case 'update':
      case 'updated':
        return (
          <div className="w-6 h-6 bg-yellow-100 rounded-full flex items-center justify-center">
            <svg className="w-3 h-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
          </div>
        );
      case 'delete':
      case 'deleted':
        return (
          <div className="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
            <svg className="w-3 h-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </div>
        );
      default:
        return (
          <div className="w-6 h-6 bg-gray-100 rounded-full flex items-center justify-center">
            <svg className="w-3 h-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        );
    }
  };

  const getActionDescription = (log: AuditLogEntry) => {
    const { action, resource, details } = log;
    
    switch (resource.toLowerCase()) {
      case 'application':
        if (action === 'approved') return `Approved application for ${details.candidateName}`;
        if (action === 'rejected') return `Rejected application for ${details.candidateName}`;
        break;
      case 'employee':
        if (action === 'role_changed') return `Changed ${details.employeeName}'s role to ${details.newRole}`;
        if (action === 'penalty_applied') return `Applied penalty to ${details.employeeName}`;
        break;
      case 'payroll':
        if (action === 'batch_created') return `Created payroll batch for week ${details.weekOf}`;
        if (action === 'batch_finalized') return `Finalized payroll batch ${details.batchId}`;
        break;
      case 'leave':
        if (action === 'approved') return `Approved leave request from ${details.employeeName}`;
        if (action === 'rejected') return `Rejected leave request from ${details.employeeName}`;
        break;
      case 'settings':
        if (action === 'updated') return `Updated ${details.setting} setting`;
        break;
    }
    
    return `${action} ${resource}${details.name ? ` (${details.name})` : ''}`;
  };

  // Mock data for demonstration
  useEffect(() => {
    const mockLogs: AuditLogEntry[] = [
      {
        id: '1',
        timestamp: new Date(Date.now() - 5 * 60000).toISOString(),
        adminId: 'admin1',
        adminName: 'John Admin',
        action: 'approved',
        resource: 'application',
        resourceId: 'app123',
        details: { candidateName: 'Sarah Johnson', position: 'Data Entry' },
        ipAddress: '192.168.1.100'
      },
      {
        id: '2',
        timestamp: new Date(Date.now() - 15 * 60000).toISOString(),
        adminId: 'admin1',
        adminName: 'John Admin',
        action: 'role_changed',
        resource: 'employee',
        resourceId: 'emp456',
        details: { employeeName: 'Mike Wilson', oldRole: 'EMPLOYEE', newRole: 'MANAGER' },
        ipAddress: '192.168.1.100'
      },
      {
        id: '3',
        timestamp: new Date(Date.now() - 30 * 60000).toISOString(),
        adminId: 'admin1',
        adminName: 'John Admin',
        action: 'batch_created',
        resource: 'payroll',
        resourceId: 'batch789',
        details: { weekOf: '2024-03-11', employeeCount: 45 },
        ipAddress: '192.168.1.100'
      },
      {
        id: '4',
        timestamp: new Date(Date.now() - 45 * 60000).toISOString(),
        adminId: 'admin1',
        adminName: 'John Admin',
        action: 'rejected',
        resource: 'leave',
        resourceId: 'leave101',
        details: { employeeName: 'Alice Brown', reason: 'Insufficient notice period' },
        ipAddress: '192.168.1.100'
      },
      {
        id: '5',
        timestamp: new Date(Date.now() - 60 * 60000).toISOString(),
        adminId: 'admin1',
        adminName: 'John Admin',
        action: 'updated',
        resource: 'settings',
        resourceId: 'setting1',
        details: { setting: 'Base Hourly Rate', oldValue: '$15', newValue: '$16' },
        ipAddress: '192.168.1.100'
      }
    ];
    
    setLogs(mockLogs);
    setIsLoading(false);
  }, []);

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 h-fit">
      {/* Header */}
      <div className="p-4 border-b border-gray-200">
        <div className="flex items-center justify-between mb-3">
          <h3 className="text-lg font-semibold text-gray-900">Audit Log</h3>
          <button
            onClick={fetchAuditLogs}
            className="p-1 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded"
            aria-label="Refresh audit log"
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
          </button>
        </div>
        
        {/* Filter */}
        <select
          value={filter}
          onChange={(e) => setFilter(e.target.value as any)}
          className="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        >
          <option value="all">All Actions</option>
          <option value="applications">Applications</option>
          <option value="employees">Employees</option>
          <option value="payroll">Payroll</option>
          <option value="leaves">Leaves</option>
          <option value="settings">Settings</option>
        </select>
      </div>

      {/* Log Entries */}
      <div className="max-h-96 overflow-y-auto">
        {isLoading ? (
          <div className="p-4 text-center">
            <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
            <p className="text-sm text-gray-500 mt-2">Loading audit logs...</p>
          </div>
        ) : logs.length === 0 ? (
          <div className="p-4 text-center">
            <svg className="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p className="text-sm text-gray-500">No audit logs found</p>
          </div>
        ) : (
          <div className="divide-y divide-gray-100">
            {logs.map((log) => (
              <div key={log.id} className="p-4 hover:bg-gray-50 transition-colors">
                <div className="flex items-start space-x-3">
                  {getActionIcon(log.action)}
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 mb-1">
                      {getActionDescription(log)}
                    </p>
                    <div className="flex items-center text-xs text-gray-500 space-x-2">
                      <span>{log.adminName}</span>
                      <span>•</span>
                      <span>{formatTimestamp(log.timestamp)}</span>
                    </div>
                    {log.details && Object.keys(log.details).length > 0 && (
                      <div className="mt-2 text-xs text-gray-600">
                        {Object.entries(log.details).map(([key, value]) => (
                          <div key={key} className="flex">
                            <span className="font-medium capitalize mr-1">{key.replace(/([A-Z])/g, ' $1')}:</span>
                            <span>{String(value)}</span>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Footer */}
      <div className="p-3 border-t border-gray-200 bg-gray-50 rounded-b-lg">
        <p className="text-xs text-gray-500 text-center">
          Showing last 50 actions • Auto-refreshes every 30s
        </p>
      </div>
    </div>
  );
}
