'use client';

import { useState, useEffect } from 'react';
import { Modal } from '@/components/ui/modal';

interface Application {
  id: string;
  candidateName: string;
  email: string;
  phone: string;
  position: string;
  appliedAt: string;
  status: 'PENDING' | 'APPROVED' | 'REJECTED';
  documents: {
    cnic: string;
    utility: string;
    selfie: string;
    contract: string;
  };
  personalInfo: {
    address: string;
    emergencyContact: string;
    experience: string;
  };
  rejectionReason?: string;
}

export default function AdminApplicationsPage() {
  const [applications, setApplications] = useState<Application[]>([]);
  const [filteredApplications, setFilteredApplications] = useState<Application[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedApplication, setSelectedApplication] = useState<Application | null>(null);
  const [previewDocument, setPreviewDocument] = useState<{ type: string; url: string } | null>(null);
  const [actionModal, setActionModal] = useState<{ type: 'approve' | 'reject'; application: Application } | null>(null);
  const [actionReason, setActionReason] = useState('');
  const [isProcessing, setIsProcessing] = useState(false);

  // Filters
  const [statusFilter, setStatusFilter] = useState<'all' | 'PENDING' | 'APPROVED' | 'REJECTED'>('all');
  const [positionFilter, setPositionFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    fetchApplications();
  }, []);

  useEffect(() => {
    filterApplications();
  }, [applications, statusFilter, positionFilter, searchQuery]);

  const fetchApplications = async () => {
    try {
      setIsLoading(true);
      const response = await fetch('/api/admin/applications');
      if (response.ok) {
        const data = await response.json();
        setApplications(data.applications || []);
      }
    } catch (error) {
      console.error('Failed to fetch applications:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const filterApplications = () => {
    let filtered = applications;

    if (statusFilter !== 'all') {
      filtered = filtered.filter(app => app.status === statusFilter);
    }

    if (positionFilter !== 'all') {
      filtered = filtered.filter(app => app.position === positionFilter);
    }

    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(app =>
        app.candidateName.toLowerCase().includes(query) ||
        app.email.toLowerCase().includes(query) ||
        app.phone.includes(query)
      );
    }

    setFilteredApplications(filtered);
  };

  const handleApplicationAction = async (applicationId: string, action: 'approve' | 'reject', reason?: string) => {
    try {
      setIsProcessing(true);
      const response = await fetch(`/api/admin/applications/${applicationId}/decision`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, reason })
      });

      if (response.ok) {
        await fetchApplications();
        setActionModal(null);
        setActionReason('');
      }
    } catch (error) {
      console.error('Failed to process application:', error);
    } finally {
      setIsProcessing(false);
    }
  };

  const getUniquePositions = () => {
    const positions = [...new Set(applications.map(app => app.position))];
    return positions.sort();
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'PENDING':
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>;
      case 'APPROVED':
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>;
      case 'REJECTED':
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rejected</span>;
      default:
        return <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{status}</span>;
    }
  };

  // Mock data
  useEffect(() => {
    const mockApplications: Application[] = [
      {
        id: '1',
        candidateName: 'Sarah Johnson',
        email: 'sarah.johnson@email.com',
        phone: '+1234567890',
        position: 'Data Entry Specialist',
        appliedAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
        status: 'PENDING',
        documents: {
          cnic: '/documents/sarah-cnic.jpg',
          utility: '/documents/sarah-utility.pdf',
          selfie: '/documents/sarah-selfie.jpg',
          contract: '/documents/sarah-contract.pdf'
        },
        personalInfo: {
          address: '123 Main St, City, State',
          emergencyContact: 'John Johnson - +1234567891',
          experience: '2 years in data entry and administrative tasks'
        }
      },
      {
        id: '2',
        candidateName: 'Mike Wilson',
        email: 'mike.wilson@email.com',
        phone: '+1234567892',
        position: 'Virtual Assistant',
        appliedAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000).toISOString(),
        status: 'PENDING',
        documents: {
          cnic: '/documents/mike-cnic.jpg',
          utility: '/documents/mike-utility.pdf',
          selfie: '/documents/mike-selfie.jpg',
          contract: '/documents/mike-contract.pdf'
        },
        personalInfo: {
          address: '456 Oak Ave, City, State',
          emergencyContact: 'Lisa Wilson - +1234567893',
          experience: '3 years in customer service and virtual assistance'
        }
      },
      {
        id: '3',
        candidateName: 'Alice Brown',
        email: 'alice.brown@email.com',
        phone: '+1234567894',
        position: 'Content Writer',
        appliedAt: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000).toISOString(),
        status: 'APPROVED',
        documents: {
          cnic: '/documents/alice-cnic.jpg',
          utility: '/documents/alice-utility.pdf',
          selfie: '/documents/alice-selfie.jpg',
          contract: '/documents/alice-contract.pdf'
        },
        personalInfo: {
          address: '789 Pine St, City, State',
          emergencyContact: 'Bob Brown - +1234567895',
          experience: '4 years in content writing and copywriting'
        }
      }
    ];
    
    setApplications(mockApplications);
    setIsLoading(false);
  }, []);

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Applications</h1>
          <p className="text-gray-600">Review and manage job applications</p>
        </div>
        <div className="flex items-center space-x-3">
          <button
            onClick={fetchApplications}
            className="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          >
            Refresh
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Search</label>
            <input
              type="text"
              placeholder="Name, email, or phone..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value as any)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Statuses</option>
              <option value="PENDING">Pending</option>
              <option value="APPROVED">Approved</option>
              <option value="REJECTED">Rejected</option>
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Position</label>
            <select
              value={positionFilter}
              onChange={(e) => setPositionFilter(e.target.value)}
              className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Positions</option>
              {getUniquePositions().map(position => (
                <option key={position} value={position}>{position}</option>
              ))}
            </select>
          </div>
          <div className="flex items-end">
            <div className="text-sm text-gray-600">
              Showing {filteredApplications.length} of {applications.length} applications
            </div>
          </div>
        </div>
      </div>

      {/* Applications Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p className="text-gray-500">Loading applications...</p>
          </div>
        ) : filteredApplications.length === 0 ? (
          <div className="p-8 text-center">
            <svg className="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <p className="text-gray-500">No applications found</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Candidate
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Position
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Applied
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Documents
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredApplications.map((application) => (
                  <tr key={application.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900">
                          {application.candidateName}
                        </div>
                        <div className="text-sm text-gray-500">
                          {application.email}
                        </div>
                        <div className="text-sm text-gray-500">
                          {application.phone}
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {application.position}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {formatDate(application.appliedAt)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(application.status)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="flex space-x-2">
                        <button
                          onClick={() => setPreviewDocument({ type: 'CNIC', url: application.documents.cnic })}
                          className="text-blue-600 hover:text-blue-700 text-sm font-medium"
                        >
                          CNIC
                        </button>
                        <button
                          onClick={() => setPreviewDocument({ type: 'Utility Bill', url: application.documents.utility })}
                          className="text-blue-600 hover:text-blue-700 text-sm font-medium"
                        >
                          Utility
                        </button>
                        <button
                          onClick={() => setPreviewDocument({ type: 'Selfie', url: application.documents.selfie })}
                          className="text-blue-600 hover:text-blue-700 text-sm font-medium"
                        >
                          Selfie
                        </button>
                        <button
                          onClick={() => setPreviewDocument({ type: 'Contract', url: application.documents.contract })}
                          className="text-blue-600 hover:text-blue-700 text-sm font-medium"
                        >
                          Contract
                        </button>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div className="flex space-x-2">
                        <button
                          onClick={() => setSelectedApplication(application)}
                          className="text-blue-600 hover:text-blue-700"
                        >
                          View
                        </button>
                        {application.status === 'PENDING' && (
                          <>
                            <button
                              onClick={() => setActionModal({ type: 'approve', application })}
                              className="text-green-600 hover:text-green-700"
                            >
                              Approve
                            </button>
                            <button
                              onClick={() => setActionModal({ type: 'reject', application })}
                              className="text-red-600 hover:text-red-700"
                            >
                              Reject
                            </button>
                          </>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Application Detail Modal */}
      {selectedApplication && (
        <Modal
          isOpen={true}
          onClose={() => setSelectedApplication(null)}
          title={`Application - ${selectedApplication.candidateName}`}
          size="lg"
        >
          <div className="space-y-6">
            <div className="grid grid-cols-2 gap-6">
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-2">Personal Information</h4>
                <div className="space-y-2 text-sm">
                  <div><span className="font-medium">Name:</span> {selectedApplication.candidateName}</div>
                  <div><span className="font-medium">Email:</span> {selectedApplication.email}</div>
                  <div><span className="font-medium">Phone:</span> {selectedApplication.phone}</div>
                  <div><span className="font-medium">Address:</span> {selectedApplication.personalInfo.address}</div>
                  <div><span className="font-medium">Emergency Contact:</span> {selectedApplication.personalInfo.emergencyContact}</div>
                </div>
              </div>
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-2">Application Details</h4>
                <div className="space-y-2 text-sm">
                  <div><span className="font-medium">Position:</span> {selectedApplication.position}</div>
                  <div><span className="font-medium">Applied:</span> {formatDate(selectedApplication.appliedAt)}</div>
                  <div><span className="font-medium">Status:</span> {getStatusBadge(selectedApplication.status)}</div>
                  {selectedApplication.rejectionReason && (
                    <div><span className="font-medium">Rejection Reason:</span> {selectedApplication.rejectionReason}</div>
                  )}
                </div>
              </div>
            </div>
            
            <div>
              <h4 className="text-sm font-medium text-gray-900 mb-2">Experience</h4>
              <p className="text-sm text-gray-700">{selectedApplication.personalInfo.experience}</p>
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setSelectedApplication(null)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                Close
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* Document Preview Modal */}
      {previewDocument && (
        <Modal
          isOpen={true}
          onClose={() => setPreviewDocument(null)}
          title={`${previewDocument.type} Preview`}
          size="lg"
        >
          <div className="text-center">
            {previewDocument.url.endsWith('.pdf') ? (
              <div className="bg-gray-100 p-8 rounded-lg">
                <svg className="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <p className="text-gray-600 mb-4">PDF Document</p>
                <a
                  href={previewDocument.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                  Open PDF
                </a>
              </div>
            ) : (
              <img
                src={previewDocument.url}
                alt={previewDocument.type}
                className="max-w-full h-auto rounded-lg"
                onError={(e) => {
                  const target = e.target as HTMLImageElement;
                  target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAwIiBoZWlnaHQ9IjMwMCIgdmlld0JveD0iMCAwIDQwMCAzMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSI0MDAiIGhlaWdodD0iMzAwIiBmaWxsPSIjRjNGNEY2Ii8+CjxwYXRoIGQ9Ik0yMDAgMTUwTDE3NSAxMjVIMjI1TDIwMCAxNTBaIiBmaWxsPSIjOUNBM0FGIi8+CjxwYXRoIGQ9Ik0yMDAgMTUwTDE3NSAxNzVIMjI1TDIwMCAxNTBaIiBmaWxsPSIjOUNBM0FGIi8+CjwvZz4KPC9zdmc+';
                }}
              />
            )}
          </div>
          <div className="flex justify-end mt-4">
            <button
              onClick={() => setPreviewDocument(null)}
              className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
              Close
            </button>
          </div>
        </Modal>
      )}

      {/* Action Modal */}
      {actionModal && (
        <Modal
          isOpen={true}
          onClose={() => setActionModal(null)}
          title={`${actionModal.type === 'approve' ? 'Approve' : 'Reject'} Application`}
        >
          <div className="space-y-4">
            <p className="text-sm text-gray-700">
              Are you sure you want to {actionModal.type} the application from{' '}
              <span className="font-medium">{actionModal.application.candidateName}</span>?
            </p>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                {actionModal.type === 'approve' ? 'Approval Notes (Optional)' : 'Rejection Reason (Required)'}
              </label>
              <textarea
                value={actionReason}
                onChange={(e) => setActionReason(e.target.value)}
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
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                disabled={isProcessing}
              >
                Cancel
              </button>
              <button
                onClick={() => handleApplicationAction(actionModal.application.id, actionModal.type, actionReason)}
                disabled={isProcessing || (actionModal.type === 'reject' && !actionReason.trim())}
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
