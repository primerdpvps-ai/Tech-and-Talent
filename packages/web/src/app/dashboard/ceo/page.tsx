'use client';

import { useSession } from 'next-auth/react';
import { redirect } from 'next/navigation';
import { useState } from 'react';

// Mock data for CEO dashboard
const mockKPIs = {
  totalEmployees: 47,
  activeToday: 32,
  totalRevenue: 125000,
  monthlyGrowth: 12.5,
  avgProductivity: 89,
  clientSatisfaction: 96,
  newApplications: 8,
  pendingApprovals: 5,
};

const mockApprovals = [
  {
    id: '1',
    type: 'CORE_FIELD_CHANGE',
    employeeName: 'John Doe',
    field: 'Date of Birth',
    oldValue: '1990-05-15',
    newValue: '1990-05-16',
    reason: 'Correction in official documents',
    submittedAt: '2024-01-17T10:30:00Z',
    status: 'PENDING',
  },
  {
    id: '2',
    type: 'POLICY_EXCEPTION',
    employeeName: 'Jane Smith',
    description: 'Extended leave request beyond policy limits',
    details: 'Family emergency requiring 15 days leave',
    submittedAt: '2024-01-16T14:20:00Z',
    status: 'PENDING',
  },
];

const mockPayrollData = {
  currentWeek: {
    weekOf: '2024-01-15',
    totalHours: 1247.5,
    totalAmount: 155937.50,
    employeeCount: 32,
    processed: false,
  },
  lastWeek: {
    weekOf: '2024-01-08',
    totalHours: 1189.2,
    totalAmount: 148650.00,
    employeeCount: 30,
    processed: true,
  },
};

export default function CEODashboard() {
  const { data: session, status } = useSession();
  const [selectedTab, setSelectedTab] = useState('overview');
  const [isProcessingPayroll, setIsProcessingPayroll] = useState(false);

  if (status === 'loading') {
    return <div className="p-6">Loading...</div>;
  }

  if (!session || session.user.role !== 'CEO') {
    redirect('/');
  }

  const handleApprovalDecision = async (approvalId: string, decision: 'approve' | 'reject') => {
    try {
      const response = await fetch(`/api/admin/approvals/${approvalId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ decision }),
      });

      if (response.ok) {
        alert(`Request ${decision}d successfully`);
        // Refresh data
      } else {
        alert('Failed to process request');
      }
    } catch (error) {
      alert('An error occurred');
    }
  };

  const handlePayrollRun = async () => {
    if (!confirm('Are you sure you want to run payroll for this week? This action cannot be undone.')) {
      return;
    }

    setIsProcessingPayroll(true);
    try {
      const response = await fetch('/api/payroll/run', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          weekOf: mockPayrollData.currentWeek.weekOf,
        }),
      });

      if (response.ok) {
        alert('Payroll processed successfully!');
        // Refresh data
      } else {
        alert('Failed to process payroll');
      }
    } catch (error) {
      alert('An error occurred during payroll processing');
    } finally {
      setIsProcessingPayroll(false);
    }
  };

  return (
    <div className="p-6">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">
          CEO Dashboard
        </h1>
        <p className="mt-2 text-gray-600">
          Global overview, approvals, and strategic decisions.
        </p>
      </div>

      {/* Global KPIs */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-blue-900">{mockKPIs.totalEmployees}</p>
              <p className="text-sm text-blue-700">Total Employees</p>
            </div>
            <div className="w-12 h-12 bg-blue-200 rounded-lg flex items-center justify-center">
              <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
          </div>
          <div className="mt-4 text-sm text-blue-600">
            {mockKPIs.activeToday} active today
          </div>
        </div>

        <div className="bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-green-900">${(mockKPIs.totalRevenue / 1000).toFixed(0)}K</p>
              <p className="text-sm text-green-700">Monthly Revenue</p>
            </div>
            <div className="w-12 h-12 bg-green-200 rounded-lg flex items-center justify-center">
              <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
          </div>
          <div className="mt-4 text-sm text-green-600">
            +{mockKPIs.monthlyGrowth}% from last month
          </div>
        </div>

        <div className="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl shadow-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-purple-900">{mockKPIs.avgProductivity}%</p>
              <p className="text-sm text-purple-700">Avg Productivity</p>
            </div>
            <div className="w-12 h-12 bg-purple-200 rounded-lg flex items-center justify-center">
              <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
          </div>
          <div className="mt-4 text-sm text-purple-600">
            {mockKPIs.clientSatisfaction}% client satisfaction
          </div>
        </div>

        <div className="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl shadow-lg p-6">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-2xl font-bold text-yellow-900">{mockKPIs.pendingApprovals}</p>
              <p className="text-sm text-yellow-700">Pending Approvals</p>
            </div>
            <div className="w-12 h-12 bg-yellow-200 rounded-lg flex items-center justify-center">
              <svg className="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div className="mt-4 text-sm text-yellow-600">
            {mockKPIs.newApplications} new applications
          </div>
        </div>
      </div>

      {/* Tab Navigation */}
      <div className="mb-6">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8">
            {[
              { id: 'overview', name: 'Overview', icon: '📊' },
              { id: 'approvals', name: 'Approvals', icon: '✅' },
              { id: 'payroll', name: 'Payroll', icon: '💰' },
              { id: 'settings', name: 'Settings', icon: '⚙️' },
            ].map((tab) => (
              <button
                key={tab.id}
                onClick={() => setSelectedTab(tab.id)}
                className={`flex items-center py-2 px-1 border-b-2 font-medium text-sm ${
                  selectedTab === tab.id
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <span className="mr-2">{tab.icon}</span>
                {tab.name}
              </button>
            ))}
          </nav>
        </div>
      </div>

      {/* Tab Content */}
      {selectedTab === 'overview' && (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* Revenue Chart */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-6">Monthly Revenue Trend</h3>
            <div className="h-64 flex items-end justify-between space-x-2">
              {['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'].map((month, index) => {
                const height = Math.random() * 80 + 20; // Mock data
                const amount = (height * 1000).toFixed(0);
                return (
                  <div key={month} className="flex-1 flex flex-col items-center">
                    <div className="text-xs text-gray-600 mb-2">${amount}K</div>
                    <div 
                      className="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t-sm transition-all hover:from-blue-600 hover:to-blue-500"
                      style={{ height: `${height}%` }}
                    ></div>
                    <div className="text-xs text-gray-500 mt-2">{month}</div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Team Performance */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-6">Team Performance</h3>
            <div className="space-y-4">
              {[
                { name: 'Data Entry Team', performance: 95, members: 12 },
                { name: 'Analysis Team', performance: 87, members: 8 },
                { name: 'Support Team', performance: 92, members: 6 },
                { name: 'Quality Assurance', performance: 89, members: 4 },
              ].map((team) => (
                <div key={team.name} className="flex items-center justify-between">
                  <div>
                    <div className="font-medium text-gray-900">{team.name}</div>
                    <div className="text-sm text-gray-500">{team.members} members</div>
                  </div>
                  <div className="flex items-center">
                    <div className="w-24 bg-gray-200 rounded-full h-2 mr-3">
                      <div 
                        className="bg-blue-600 h-2 rounded-full" 
                        style={{ width: `${team.performance}%` }}
                      ></div>
                    </div>
                    <span className="text-sm font-medium text-gray-900">{team.performance}%</span>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {selectedTab === 'approvals' && (
        <div className="bg-white rounded-xl shadow-lg p-6">
          <h3 className="text-lg font-bold text-gray-900 mb-6">Pending Approvals</h3>
          <div className="space-y-6">
            {mockApprovals.map((approval) => (
              <div key={approval.id} className="border border-gray-200 rounded-lg p-6">
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <div className="flex items-center mb-2">
                      <span className={`px-3 py-1 rounded-full text-xs font-medium mr-3 ${
                        approval.type === 'CORE_FIELD_CHANGE' 
                          ? 'bg-blue-100 text-blue-800' 
                          : 'bg-purple-100 text-purple-800'
                      }`}>
                        {approval.type.replace('_', ' ')}
                      </span>
                      <h4 className="text-lg font-semibold text-gray-900">{approval.employeeName}</h4>
                    </div>
                    
                    {approval.type === 'CORE_FIELD_CHANGE' ? (
                      <div className="text-gray-600">
                        <p><strong>Field:</strong> {approval.field}</p>
                        <p><strong>From:</strong> {approval.oldValue}</p>
                        <p><strong>To:</strong> {approval.newValue}</p>
                        <p><strong>Reason:</strong> {approval.reason}</p>
                      </div>
                    ) : (
                      <div className="text-gray-600">
                        <p><strong>Request:</strong> {approval.description}</p>
                        <p><strong>Details:</strong> {approval.details}</p>
                      </div>
                    )}
                    
                    <p className="text-sm text-gray-500 mt-2">
                      Submitted: {new Date(approval.submittedAt).toLocaleDateString()}
                    </p>
                  </div>
                </div>
                
                <div className="flex space-x-3">
                  <button
                    onClick={() => handleApprovalDecision(approval.id, 'approve')}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium"
                  >
                    ✓ Approve
                  </button>
                  <button
                    onClick={() => handleApprovalDecision(approval.id, 'reject')}
                    className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium"
                  >
                    ✗ Reject
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {selectedTab === 'payroll' && (
        <div className="space-y-8">
          {/* Current Week Payroll */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <div className="flex items-center justify-between mb-6">
              <h3 className="text-lg font-bold text-gray-900">Current Week Payroll</h3>
              <span className="text-sm text-gray-500">Week of {mockPayrollData.currentWeek.weekOf}</span>
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
              <div className="text-center">
                <div className="text-2xl font-bold text-gray-900">{mockPayrollData.currentWeek.totalHours}h</div>
                <div className="text-sm text-gray-600">Total Hours</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-green-600">${mockPayrollData.currentWeek.totalAmount.toLocaleString()}</div>
                <div className="text-sm text-gray-600">Total Amount</div>
              </div>
              <div className="text-center">
                <div className="text-2xl font-bold text-blue-600">{mockPayrollData.currentWeek.employeeCount}</div>
                <div className="text-sm text-gray-600">Employees</div>
              </div>
            </div>
            
            {!mockPayrollData.currentWeek.processed ? (
              <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div className="flex">
                  <svg className="w-5 h-5 text-yellow-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                  <div>
                    <h4 className="text-sm font-medium text-yellow-800">Payroll Ready for Processing</h4>
                    <p className="text-sm text-yellow-700 mt-1">
                      Review the payroll details above and click the button below to process payments for this week.
                    </p>
                  </div>
                </div>
              </div>
            ) : (
              <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div className="flex">
                  <svg className="w-5 h-5 text-green-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <h4 className="text-sm font-medium text-green-800">Payroll Processed</h4>
                    <p className="text-sm text-green-700 mt-1">
                      This week's payroll has been successfully processed and payments have been initiated.
                    </p>
                  </div>
                </div>
              </div>
            )}
            
            <div className="flex justify-center">
              <button
                onClick={handlePayrollRun}
                disabled={mockPayrollData.currentWeek.processed || isProcessingPayroll}
                className="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition-colors font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {isProcessingPayroll ? 'Processing...' : 'Run Payroll'}
              </button>
            </div>
          </div>

          {/* Payroll History */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-6">Recent Payroll History</h3>
            <div className="overflow-x-auto">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Week Of
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Total Hours
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Total Amount
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Employees
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  <tr>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {mockPayrollData.lastWeek.weekOf}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {mockPayrollData.lastWeek.totalHours}h
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      ${mockPayrollData.lastWeek.totalAmount.toLocaleString()}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {mockPayrollData.lastWeek.employeeCount}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                        Processed
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {selectedTab === 'settings' && (
        <div className="bg-white rounded-xl shadow-lg p-6">
          <h3 className="text-lg font-bold text-gray-900 mb-6">Policy Settings</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
              <div className="text-center">
                <div className="text-2xl mb-2">👥</div>
                <div className="text-sm font-medium text-gray-900">User Management</div>
                <div className="text-xs text-gray-500">Manage roles and permissions</div>
              </div>
            </div>
            
            <div className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
              <div className="text-center">
                <div className="text-2xl mb-2">💰</div>
                <div className="text-sm font-medium text-gray-900">Payroll Settings</div>
                <div className="text-xs text-gray-500">Configure rates and policies</div>
              </div>
            </div>
            
            <div className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
              <div className="text-center">
                <div className="text-2xl mb-2">📅</div>
                <div className="text-sm font-medium text-gray-900">Leave Policies</div>
                <div className="text-xs text-gray-500">Manage leave types and rules</div>
              </div>
            </div>
            
            <div className="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
              <div className="text-center">
                <div className="text-2xl mb-2">🔒</div>
                <div className="text-sm font-medium text-gray-900">Security Settings</div>
                <div className="text-xs text-gray-500">Configure security policies</div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
