'use client';

import { useSession } from 'next-auth/react';
import { redirect } from 'next/navigation';
import Link from 'next/link';
import { useState } from 'react';

// Mock data for team performance
const mockTeamData = [
  {
    id: '1',
    name: 'John Doe',
    role: 'EMPLOYEE',
    status: 'online',
    todayHours: 6.5,
    weeklyHours: 32.5,
    productivity: 95,
    lastActive: '2 minutes ago',
  },
  {
    id: '2',
    name: 'Jane Smith',
    role: 'EMPLOYEE',
    status: 'online',
    todayHours: 7.2,
    weeklyHours: 35.8,
    productivity: 92,
    lastActive: '5 minutes ago',
  },
  {
    id: '3',
    name: 'Mike Johnson',
    role: 'NEW_EMPLOYEE',
    status: 'offline',
    todayHours: 0,
    weeklyHours: 18.3,
    productivity: 88,
    lastActive: '2 hours ago',
  },
];

const mockApplications = [
  {
    id: '1',
    name: 'Sarah Wilson',
    position: 'Data Entry Specialist',
    submittedAt: '2024-01-17T10:30:00Z',
    status: 'UNDER_REVIEW',
    score: 85,
  },
  {
    id: '2',
    name: 'David Brown',
    position: 'Virtual Assistant',
    submittedAt: '2024-01-16T14:20:00Z',
    status: 'UNDER_REVIEW',
    score: 78,
  },
];

const mockLeaveRequests = [
  {
    id: '1',
    employeeName: 'John Doe',
    type: 'ONE_DAY',
    dateFrom: '2024-01-20',
    dateTo: '2024-01-20',
    reason: 'Medical appointment',
    status: 'PENDING',
    submittedAt: '2024-01-17T09:15:00Z',
  },
  {
    id: '2',
    employeeName: 'Jane Smith',
    type: 'LONG',
    dateFrom: '2024-01-25',
    dateTo: '2024-01-27',
    reason: 'Family vacation',
    status: 'PENDING',
    submittedAt: '2024-01-16T16:45:00Z',
  },
];

export default function ManagerDashboard() {
  const { data: session, status } = useSession();
  const [selectedTab, setSelectedTab] = useState('overview');

  if (status === 'loading') {
    return <div className="p-6">Loading...</div>;
  }

  if (!session || session.user.role !== 'MANAGER') {
    redirect('/');
  }

  const handleApplicationDecision = async (applicationId: string, decision: 'approve' | 'reject') => {
    try {
      const response = await fetch(`/api/applications/${applicationId}/decide`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ decision }),
      });

      if (response.ok) {
        alert(`Application ${decision}d successfully`);
        // Refresh data
      } else {
        alert('Failed to process application');
      }
    } catch (error) {
      alert('An error occurred');
    }
  };

  const handleLeaveDecision = async (leaveId: string, decision: 'approve' | 'reject') => {
    try {
      const response = await fetch(`/api/leaves/${leaveId}`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ decision }),
      });

      if (response.ok) {
        alert(`Leave request ${decision}d successfully`);
        // Refresh data
      } else {
        alert('Failed to process leave request');
      }
    } catch (error) {
      alert('An error occurred');
    }
  };

  return (
    <div className="p-6">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">
          Manager Dashboard
        </h1>
        <p className="mt-2 text-gray-600">
          Manage your team, review applications, and monitor performance.
        </p>
      </div>

      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-xl shadow-lg p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
              <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a4 4 0 11-8 0 4 4 0 018 0z" />
              </svg>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">{mockTeamData.length}</p>
              <p className="text-sm text-gray-600">Team Members</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-lg p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
              <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">
                {mockTeamData.filter(member => member.status === 'online').length}
              </p>
              <p className="text-sm text-gray-600">Active Now</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-lg p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
              <svg className="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
              </svg>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">{mockApplications.length}</p>
              <p className="text-sm text-gray-600">Pending Applications</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-lg p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
              <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
              </svg>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">
                {Math.round(mockTeamData.reduce((sum, member) => sum + member.productivity, 0) / mockTeamData.length)}%
              </p>
              <p className="text-sm text-gray-600">Avg Performance</p>
            </div>
          </div>
        </div>
      </div>

      {/* Tab Navigation */}
      <div className="mb-6">
        <div className="border-b border-gray-200">
          <nav className="-mb-px flex space-x-8">
            {[
              { id: 'overview', name: 'Team Overview', icon: '👥' },
              { id: 'applications', name: 'Applications', icon: '📋' },
              { id: 'leaves', name: 'Leave Requests', icon: '📅' },
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
        <div className="bg-white rounded-xl shadow-lg p-6">
          <h3 className="text-lg font-bold text-gray-900 mb-6">Team Performance</h3>
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Employee
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Today
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    This Week
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Productivity
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Last Active
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {mockTeamData.map((member) => (
                  <tr key={member.id}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <div className="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                          <span className="text-sm font-medium text-gray-600">
                            {member.name.split(' ').map(n => n[0]).join('')}
                          </span>
                        </div>
                        <div>
                          <div className="text-sm font-medium text-gray-900">{member.name}</div>
                          <div className="text-sm text-gray-500">{member.role.replace('_', ' ')}</div>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        member.status === 'online' 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-gray-100 text-gray-800'
                      }`}>
                        {member.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {member.todayHours}h
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {member.weeklyHours}h
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex items-center">
                        <div className="flex-1 bg-gray-200 rounded-full h-2 mr-2">
                          <div 
                            className="bg-blue-600 h-2 rounded-full" 
                            style={{ width: `${member.productivity}%` }}
                          ></div>
                        </div>
                        <span className="text-sm text-gray-900">{member.productivity}%</span>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {member.lastActive}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {selectedTab === 'applications' && (
        <div className="bg-white rounded-xl shadow-lg p-6">
          <h3 className="text-lg font-bold text-gray-900 mb-6">Pending Applications</h3>
          <div className="space-y-4">
            {mockApplications.map((application) => (
              <div key={application.id} className="border border-gray-200 rounded-lg p-6">
                <div className="flex items-center justify-between mb-4">
                  <div>
                    <h4 className="text-lg font-semibold text-gray-900">{application.name}</h4>
                    <p className="text-gray-600">{application.position}</p>
                    <p className="text-sm text-gray-500">
                      Submitted: {new Date(application.submittedAt).toLocaleDateString()}
                    </p>
                  </div>
                  <div className="text-right">
                    <div className="text-2xl font-bold text-blue-600 mb-2">{application.score}</div>
                    <div className="text-sm text-gray-500">Evaluation Score</div>
                  </div>
                </div>
                
                <div className="flex space-x-3">
                  <button
                    onClick={() => handleApplicationDecision(application.id, 'approve')}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium"
                  >
                    ✓ Approve
                  </button>
                  <button
                    onClick={() => handleApplicationDecision(application.id, 'reject')}
                    className="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors font-medium"
                  >
                    ✗ Reject
                  </button>
                  <Link
                    href={`/dashboard/manager/applications/${application.id}`}
                    className="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors font-medium"
                  >
                    👁️ Review Details
                  </Link>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {selectedTab === 'leaves' && (
        <div className="bg-white rounded-xl shadow-lg p-6">
          <h3 className="text-lg font-bold text-gray-900 mb-6">Leave Requests</h3>
          <div className="space-y-4">
            {mockLeaveRequests.map((leave) => (
              <div key={leave.id} className="border border-gray-200 rounded-lg p-6">
                <div className="flex items-start justify-between mb-4">
                  <div>
                    <h4 className="text-lg font-semibold text-gray-900">{leave.employeeName}</h4>
                    <div className="flex items-center space-x-4 text-sm text-gray-600 mt-2">
                      <span className={`px-2 py-1 rounded-full text-xs font-medium ${
                        leave.type === 'SHORT' ? 'bg-blue-100 text-blue-800' :
                        leave.type === 'ONE_DAY' ? 'bg-green-100 text-green-800' :
                        'bg-purple-100 text-purple-800'
                      }`}>
                        {leave.type.replace('_', ' ')}
                      </span>
                      <span>📅 {leave.dateFrom} to {leave.dateTo}</span>
                    </div>
                    <p className="text-gray-600 mt-2">
                      <strong>Reason:</strong> {leave.reason}
                    </p>
                    <p className="text-sm text-gray-500 mt-1">
                      Submitted: {new Date(leave.submittedAt).toLocaleDateString()}
                    </p>
                  </div>
                </div>
                
                <div className="flex space-x-3">
                  <button
                    onClick={() => handleLeaveDecision(leave.id, 'approve')}
                    className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors font-medium"
                  >
                    ✓ Approve
                  </button>
                  <button
                    onClick={() => handleLeaveDecision(leave.id, 'reject')}
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
    </div>
  );
}
