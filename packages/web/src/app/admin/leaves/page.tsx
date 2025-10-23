'use client';

import { useState, useEffect } from 'react';
import { Modal } from '@/components/ui/modal';

interface LeaveRequest {
  id: string;
  employeeId: string;
  employeeName: string;
  type: 'SHORT' | 'ONE_DAY' | 'LONG';
  dateFrom: string;
  dateTo: string;
  reason: string;
  isEmergency: boolean;
  status: 'PENDING' | 'APPROVED' | 'REJECTED';
  submittedAt: string;
  reviewedAt?: string;
  reviewedBy?: string;
  reviewNotes?: string;
  suggestedPenalty?: {
    type: string;
    amount: number;
    reason: string;
  };
}

export default function AdminLeavesPage() {
  const [leaveRequests, setLeaveRequests] = useState<LeaveRequest[]>([]);
  const [filteredRequests, setFilteredRequests] = useState<LeaveRequest[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedRequest, setSelectedRequest] = useState<LeaveRequest | null>(null);
  const [actionModal, setActionModal] = useState<{ type: 'approve' | 'reject'; request: LeaveRequest } | null>(null);
  const [reviewNotes, setReviewNotes] = useState('');
  const [applyPenalty, setApplyPenalty] = useState(false);
  const [penaltyAmount, setPenaltyAmount] = useState(0);
  const [isProcessing, setIsProcessing] = useState(false);

  // Filters
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [typeFilter, setTypeFilter] = useState<string>('all');
  const [emergencyFilter, setEmergencyFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    fetchLeaveRequests();
  }, []);

  useEffect(() => {
    filterRequests();
  }, [leaveRequests, statusFilter, typeFilter, emergencyFilter, searchQuery]);

  const fetchLeaveRequests = async () => {
    try {
      setIsLoading(true);
      // Mock data
      const mockRequests: LeaveRequest[] = [
        {
          id: '1',
          employeeId: 'emp1',
          employeeName: 'John Smith',
          type: 'ONE_DAY',
          dateFrom: '2024-03-20',
          dateTo: '2024-03-20',
          reason: 'Medical appointment that could not be scheduled outside work hours',
          isEmergency: false,
          status: 'PENDING',
          submittedAt: '2024-03-15T10:00:00Z',
          suggestedPenalty: {
            type: 'Insufficient Notice',
            amount: 25,
            reason: 'Less than 24 hours notice for one-day leave'
          }
        },
        {
          id: '2',
          employeeId: 'emp2',
          employeeName: 'Sarah Johnson',
          type: 'SHORT',
          dateFrom: '2024-03-18',
          dateTo: '2024-03-18',
          reason: 'Family emergency - need to pick up sick child from school',
          isEmergency: true,
          status: 'PENDING',
          submittedAt: '2024-03-18T08:30:00Z'
        },
        {
          id: '3',
          employeeId: 'emp3',
          employeeName: 'Mike Wilson',
          type: 'LONG',
          dateFrom: '2024-03-25',
          dateTo: '2024-03-29',
          reason: 'Pre-planned vacation with family. All work will be completed before departure.',
          isEmergency: false,
          status: 'APPROVED',
          submittedAt: '2024-03-01T14:00:00Z',
          reviewedAt: '2024-03-02T09:00:00Z',
          reviewedBy: 'Admin',
          reviewNotes: 'Approved - sufficient notice provided and coverage arranged'
        }
      ];
      
      setLeaveRequests(mockRequests);
    } catch (error) {
      console.error('Failed to fetch leave requests:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const filterRequests = () => {
    let filtered = leaveRequests;

    if (statusFilter !== 'all') {
      filtered = filtered.filter(req => req.status === statusFilter);
    }

    if (typeFilter !== 'all') {
      filtered = filtered.filter(req => req.type === typeFilter);
    }

    if (emergencyFilter !== 'all') {
      const isEmergency = emergencyFilter === 'emergency';
      filtered = filtered.filter(req => req.isEmergency === isEmergency);
    }

    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(req =>
        req.employeeName.toLowerCase().includes(query) ||
        req.reason.toLowerCase().includes(query)
      );
    }

    setFilteredRequests(filtered);
  };

  const handleLeaveAction = async (requestId: string, action: 'approve' | 'reject', notes: string, penalty?: { amount: number }) => {
    try {
      setIsProcessing(true);
      const response = await fetch(`/api/admin/leaves/${requestId}/decision`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, notes, penalty })
      });

      if (response.ok) {
        await fetchLeaveRequests();
        setActionModal(null);
        setReviewNotes('');
        setApplyPenalty(false);
        setPenaltyAmount(0);
      }
    } catch (error) {
      console.error('Failed to process leave request:', error);
    } finally {
      setIsProcessing(false);
    }
  };

  const calculatePenaltySuggestion = (request: LeaveRequest) => {
    const submittedDate = new Date(request.submittedAt);
    const leaveDate = new Date(request.dateFrom);
    const noticeHours = (leaveDate.getTime() - submittedDate.getTime()) / (1000 * 60 * 60);
    
    let suggestions = [];

    // Check notice period violations
    if (request.type === 'SHORT' && noticeHours < 2 && !request.isEmergency) {
      suggestions.push({ type: 'Insufficient Notice', amount: 10, reason: 'Less than 2 hours notice for short leave' });
    } else if (request.type === 'ONE_DAY' && noticeHours < 24 && !request.isEmergency) {
      suggestions.push({ type: 'Insufficient Notice', amount: 25, reason: 'Less than 24 hours notice for one-day leave' });
    } else if (request.type === 'LONG' && noticeHours < 168 && !request.isEmergency) {
      suggestions.push({ type: 'Insufficient Notice', amount: 50, reason: 'Less than 7 days notice for long leave' });
    }

    // Check weekend violations
    const isWeekend = (date: Date) => date.getDay() === 0 || date.getDay() === 6;
    if (isWeekend(leaveDate)) {
      suggestions.push({ type: 'Weekend Leave', amount: 15, reason: 'Weekend leave penalty' });
    }

    return suggestions;
  };

  const getStatusBadge = (status: string) => {
    const colors = {
      PENDING: 'bg-yellow-100 text-yellow-800',
      APPROVED: 'bg-green-100 text-green-800',
      REJECTED: 'bg-red-100 text-red-800'
    };
    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[status as keyof typeof colors]}`}>
        {status}
      </span>
    );
  };

  const getTypeBadge = (type: string) => {
    const colors = {
      SHORT: 'bg-blue-100 text-blue-800',
      ONE_DAY: 'bg-purple-100 text-purple-800',
      LONG: 'bg-orange-100 text-orange-800'
    };
    const labels = {
      SHORT: 'Short',
      ONE_DAY: 'One Day',
      LONG: 'Long'
    };
    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[type as keyof typeof colors]}`}>
        {labels[type as keyof typeof labels]}
      </span>
    );
  };

  const formatDateRange = (dateFrom: string, dateTo: string) => {
    const from = new Date(dateFrom);
    const to = new Date(dateTo);
    
    if (dateFrom === dateTo) {
      return from.toLocaleDateString();
    }
    
    return `${from.toLocaleDateString()} - ${to.toLocaleDateString()}`;
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Leave Requests</h1>
          <p className="text-gray-600">Review and manage employee leave requests</p>
        </div>
        <button
          onClick={fetchLeaveRequests}
          className="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Refresh
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
          <input
            type="text"
            placeholder="Search employee or reason..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="all">All Statuses</option>
            <option value="PENDING">Pending</option>
            <option value="APPROVED">Approved</option>
            <option value="REJECTED">Rejected</option>
          </select>
          <select
            value={typeFilter}
            onChange={(e) => setTypeFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="all">All Types</option>
            <option value="SHORT">Short Leave</option>
            <option value="ONE_DAY">One Day</option>
            <option value="LONG">Long Leave</option>
          </select>
          <select
            value={emergencyFilter}
            onChange={(e) => setEmergencyFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          >
            <option value="all">All Requests</option>
            <option value="emergency">Emergency Only</option>
            <option value="regular">Regular Only</option>
          </select>
          <div className="text-sm text-gray-600 flex items-center">
            {filteredRequests.length} of {leaveRequests.length} requests
          </div>
        </div>
      </div>

      {/* Leave Requests Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p className="text-gray-500">Loading leave requests...</p>
          </div>
        ) : filteredRequests.length === 0 ? (
          <div className="p-8 text-center">
            <svg className="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p className="text-gray-500">No leave requests found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dates</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Submitted</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penalties</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredRequests.map((request) => {
                  const penalties = calculatePenaltySuggestion(request);
                  return (
                    <tr key={request.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div>
                          <div className="text-sm font-medium text-gray-900">{request.employeeName}</div>
                          {request.isEmergency && (
                            <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 mt-1">
                              Emergency
                            </span>
                          )}
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">{getTypeBadge(request.type)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {formatDateRange(request.dateFrom, request.dateTo)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">{getStatusBadge(request.status)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {new Date(request.submittedAt).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {penalties.length > 0 ? (
                          <div className="text-xs">
                            {penalties.map((penalty, index) => (
                              <div key={index} className="text-red-600">
                                ${penalty.amount} - {penalty.type}
                              </div>
                            ))}
                          </div>
                        ) : (
                          <span className="text-xs text-gray-500">None</span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div className="flex space-x-2">
                          <button
                            onClick={() => setSelectedRequest(request)}
                            className="text-blue-600 hover:text-blue-700"
                          >
                            View
                          </button>
                          {request.status === 'PENDING' && (
                            <>
                              <button
                                onClick={() => setActionModal({ type: 'approve', request })}
                                className="text-green-600 hover:text-green-700"
                              >
                                Approve
                              </button>
                              <button
                                onClick={() => setActionModal({ type: 'reject', request })}
                                className="text-red-600 hover:text-red-700"
                              >
                                Reject
                              </button>
                            </>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Request Detail Modal */}
      {selectedRequest && (
        <Modal
          isOpen={true}
          onClose={() => setSelectedRequest(null)}
          title={`Leave Request - ${selectedRequest.employeeName}`}
          size="lg"
        >
          <div className="space-y-6">
            <div className="grid grid-cols-2 gap-6">
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-3">Request Details</h4>
                <div className="space-y-2 text-sm">
                  <div><span className="font-medium">Employee:</span> {selectedRequest.employeeName}</div>
                  <div><span className="font-medium">Type:</span> {getTypeBadge(selectedRequest.type)}</div>
                  <div><span className="font-medium">Dates:</span> {formatDateRange(selectedRequest.dateFrom, selectedRequest.dateTo)}</div>
                  <div><span className="font-medium">Emergency:</span> {selectedRequest.isEmergency ? 'Yes' : 'No'}</div>
                  <div><span className="font-medium">Status:</span> {getStatusBadge(selectedRequest.status)}</div>
                  <div><span className="font-medium">Submitted:</span> {new Date(selectedRequest.submittedAt).toLocaleString()}</div>
                </div>
              </div>
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-3">Penalty Analysis</h4>
                <div className="space-y-2">
                  {calculatePenaltySuggestion(selectedRequest).map((penalty, index) => (
                    <div key={index} className="bg-red-50 p-3 rounded-lg text-sm">
                      <div className="font-medium text-red-800">${penalty.amount} - {penalty.type}</div>
                      <div className="text-red-700">{penalty.reason}</div>
                    </div>
                  ))}
                  {calculatePenaltySuggestion(selectedRequest).length === 0 && (
                    <div className="bg-green-50 p-3 rounded-lg text-sm text-green-700">
                      No penalties suggested
                    </div>
                  )}
                </div>
              </div>
            </div>
            
            <div>
              <h4 className="text-sm font-medium text-gray-900 mb-2">Reason</h4>
              <p className="text-sm text-gray-700 bg-gray-50 p-3 rounded-lg">{selectedRequest.reason}</p>
            </div>

            {selectedRequest.reviewNotes && (
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-2">Review Notes</h4>
                <p className="text-sm text-gray-700 bg-blue-50 p-3 rounded-lg">{selectedRequest.reviewNotes}</p>
                <p className="text-xs text-gray-500 mt-1">
                  Reviewed by {selectedRequest.reviewedBy} on {selectedRequest.reviewedAt && new Date(selectedRequest.reviewedAt).toLocaleString()}
                </p>
              </div>
            )}

            <div className="flex justify-end">
              <button
                onClick={() => setSelectedRequest(null)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Close
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* Action Modal */}
      {actionModal && (
        <Modal
          isOpen={true}
          onClose={() => setActionModal(null)}
          title={`${actionModal.type === 'approve' ? 'Approve' : 'Reject'} Leave Request`}
        >
          <div className="space-y-4">
            <p className="text-sm text-gray-700">
              {actionModal.type === 'approve' ? 'Approve' : 'Reject'} leave request from{' '}
              <span className="font-medium">{actionModal.request.employeeName}</span> for{' '}
              {formatDateRange(actionModal.request.dateFrom, actionModal.request.dateTo)}?
            </p>

            {/* Penalty suggestions for approvals */}
            {actionModal.type === 'approve' && calculatePenaltySuggestion(actionModal.request).length > 0 && (
              <div className="bg-yellow-50 p-4 rounded-lg">
                <h4 className="text-sm font-medium text-yellow-800 mb-2">Penalty Suggestions</h4>
                <div className="space-y-2">
                  {calculatePenaltySuggestion(actionModal.request).map((penalty, index) => (
                    <label key={index} className="flex items-start">
                      <input
                        type="checkbox"
                        checked={applyPenalty}
                        onChange={(e) => {
                          setApplyPenalty(e.target.checked);
                          if (e.target.checked) setPenaltyAmount(penalty.amount);
                        }}
                        className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-0.5 mr-3"
                      />
                      <div className="text-sm">
                        <div className="font-medium text-yellow-800">${penalty.amount} - {penalty.type}</div>
                        <div className="text-yellow-700">{penalty.reason}</div>
                      </div>
                    </label>
                  ))}
                </div>
              </div>
            )}

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                {actionModal.type === 'approve' ? 'Approval Notes (Optional)' : 'Rejection Reason (Required)'}
              </label>
              <textarea
                value={reviewNotes}
                onChange={(e) => setReviewNotes(e.target.value)}
                rows={3}
                placeholder={actionModal.type === 'approve' 
                  ? 'Add any notes about the approval...' 
                  : 'Please provide a reason for rejection...'
                }
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                required={actionModal.type === 'reject'}
              />
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setActionModal(null)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                disabled={isProcessing}
              >
                Cancel
              </button>
              <button
                onClick={() => handleLeaveAction(
                  actionModal.request.id, 
                  actionModal.type, 
                  reviewNotes,
                  applyPenalty ? { amount: penaltyAmount } : undefined
                )}
                disabled={isProcessing || (actionModal.type === 'reject' && !reviewNotes.trim())}
                className={`px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed ${
                  actionModal.type === 'approve'
                    ? 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
                    : 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
                }`}
              >
                {isProcessing ? (
                  <div className="flex items-center">
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                    Processing...
                  </div>
                ) : (
                  actionModal.type === 'approve' ? 'Approve' : 'Reject'
                )}
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
}
