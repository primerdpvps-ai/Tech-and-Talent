'use client';

import { useState, useEffect } from 'react';
import { Modal } from '@/components/ui/modal';

interface PayrollBatch {
  id: string;
  weekOf: string;
  status: 'DRAFT' | 'PREVIEW' | 'FINALIZED' | 'PAID';
  employeeCount: number;
  totalAmount: number;
  createdAt: string;
  finalizedAt?: string;
  paidAt?: string;
  paymentReference?: string;
}

interface PayrollEmployee {
  id: string;
  name: string;
  role: string;
  hoursWorked: number;
  baseRate: number;
  bonuses: number;
  penalties: number;
  grossPay: number;
  deductions: number;
  netPay: number;
  status: 'INCLUDED' | 'EXCLUDED' | 'PENDING';
}

export default function AdminPayrollPage() {
  const [batches, setBatches] = useState<PayrollBatch[]>([]);
  const [currentBatch, setCurrentBatch] = useState<PayrollBatch | null>(null);
  const [batchEmployees, setBatchEmployees] = useState<PayrollEmployee[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isCreatingBatch, setIsCreatingBatch] = useState(false);
  const [previewModal, setPreviewModal] = useState(false);
  const [finalizeModal, setFinalizeModal] = useState(false);
  const [paymentReference, setPaymentReference] = useState('');
  const [selectedWeek, setSelectedWeek] = useState('');

  useEffect(() => {
    fetchPayrollBatches();
    setSelectedWeek(getCurrentWeek());
  }, []);

  const getCurrentWeek = () => {
    const now = new Date();
    const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
    return startOfWeek.toISOString().split('T')[0];
  };

  const fetchPayrollBatches = async () => {
    try {
      setIsLoading(true);
      // Mock data
      const mockBatches: PayrollBatch[] = [
        {
          id: '1',
          weekOf: '2024-03-11',
          status: 'PAID',
          employeeCount: 45,
          totalAmount: 18750,
          createdAt: '2024-03-15T10:00:00Z',
          finalizedAt: '2024-03-16T14:00:00Z',
          paidAt: '2024-03-16T16:00:00Z',
          paymentReference: 'PAY-2024-03-11-001'
        },
        {
          id: '2',
          weekOf: '2024-03-18',
          status: 'FINALIZED',
          employeeCount: 47,
          totalAmount: 19200,
          createdAt: '2024-03-22T10:00:00Z',
          finalizedAt: '2024-03-23T14:00:00Z'
        }
      ];
      setBatches(mockBatches);
    } catch (error) {
      console.error('Failed to fetch payroll batches:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const createPayrollBatch = async () => {
    try {
      setIsCreatingBatch(true);
      const response = await fetch('/api/admin/payroll/create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ weekOf: selectedWeek })
      });

      if (response.ok) {
        const batch = await response.json();
        setCurrentBatch(batch);
        await fetchBatchEmployees(batch.id);
        await fetchPayrollBatches();
      }
    } catch (error) {
      console.error('Failed to create payroll batch:', error);
    } finally {
      setIsCreatingBatch(false);
    }
  };

  const fetchBatchEmployees = async (batchId: string) => {
    try {
      // Mock data
      const mockEmployees: PayrollEmployee[] = [
        {
          id: '1',
          name: 'John Smith',
          role: 'Data Entry',
          hoursWorked: 40,
          baseRate: 15,
          bonuses: 50,
          penalties: 10,
          grossPay: 640,
          deductions: 64,
          netPay: 576,
          status: 'INCLUDED'
        },
        {
          id: '2',
          name: 'Sarah Johnson',
          role: 'Manager',
          role: 'Virtual Assistant',
          hoursWorked: 38,
          baseRate: 12,
          bonuses: 25,
          penalties: 0,
          grossPay: 481,
          deductions: 48.1,
          netPay: 432.9,
          status: 'INCLUDED'
        }
      ];
      setBatchEmployees(mockEmployees);
    } catch (error) {
      console.error('Failed to fetch batch employees:', error);
    }
  };

  const finalizeBatch = async () => {
    if (!currentBatch) return;
    
    try {
      const response = await fetch(`/api/admin/payroll/${currentBatch.id}/finalize`, {
        method: 'POST'
      });

      if (response.ok) {
        await fetchPayrollBatches();
        setFinalizeModal(false);
        setCurrentBatch(null);
      }
    } catch (error) {
      console.error('Failed to finalize batch:', error);
    }
  };

  const markAsPaid = async (batchId: string, reference: string) => {
    try {
      const response = await fetch(`/api/admin/payroll/${batchId}/paid`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ paymentReference: reference })
      });

      if (response.ok) {
        await fetchPayrollBatches();
        setPaymentReference('');
      }
    } catch (error) {
      console.error('Failed to mark as paid:', error);
    }
  };

  const getStatusBadge = (status: string) => {
    const colors = {
      DRAFT: 'bg-gray-100 text-gray-800',
      PREVIEW: 'bg-blue-100 text-blue-800',
      FINALIZED: 'bg-yellow-100 text-yellow-800',
      PAID: 'bg-green-100 text-green-800'
    };
    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[status as keyof typeof colors]}`}>
        {status}
      </span>
    );
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Payroll Management</h1>
          <p className="text-gray-600">Create and manage weekly payroll batches</p>
        </div>
      </div>

      {/* Create New Batch */}
      <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Create New Payroll Batch</h3>
        <div className="flex items-end space-x-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">Week Of</label>
            <input
              type="date"
              value={selectedWeek}
              onChange={(e) => setSelectedWeek(e.target.value)}
              className="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <button
            onClick={createPayrollBatch}
            disabled={isCreatingBatch || !selectedWeek}
            className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isCreatingBatch ? (
              <div className="flex items-center">
                <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                Creating...
              </div>
            ) : (
              'Create Batch'
            )}
          </button>
        </div>
      </div>

      {/* Current Batch Composer */}
      {currentBatch && (
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
          <div className="flex justify-between items-start mb-6">
            <div>
              <h3 className="text-lg font-semibold text-gray-900">
                Payroll Batch - Week of {new Date(currentBatch.weekOf).toLocaleDateString()}
              </h3>
              <p className="text-gray-600">
                {currentBatch.employeeCount} employees • ${currentBatch.totalAmount.toLocaleString()} total
              </p>
            </div>
            <div className="flex space-x-3">
              <button
                onClick={() => setPreviewModal(true)}
                className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm"
              >
                Preview
              </button>
              <button
                onClick={() => setFinalizeModal(true)}
                className="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm"
              >
                Finalize
              </button>
            </div>
          </div>

          {/* Employee List */}
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bonuses</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Penalties</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Net Pay</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {batchEmployees.map((employee) => (
                  <tr key={employee.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{employee.name}</div>
                        <div className="text-sm text-gray-500">{employee.role}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {employee.hoursWorked}h
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      ${employee.baseRate}/hr
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                      +${employee.bonuses}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                      -${employee.penalties}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      ${employee.netPay.toFixed(2)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        employee.status === 'INCLUDED' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                      }`}>
                        {employee.status}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Payroll History */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200">
        <div className="px-6 py-4 border-b border-gray-200">
          <h3 className="text-lg font-semibold text-gray-900">Payroll History</h3>
        </div>
        
        {isLoading ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p className="text-gray-500">Loading payroll batches...</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Week Of</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employees</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {batches.map((batch) => (
                  <tr key={batch.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {new Date(batch.weekOf).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {batch.employeeCount}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      ${batch.totalAmount.toLocaleString()}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      {getStatusBadge(batch.status)}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {new Date(batch.createdAt).toLocaleDateString()}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div className="flex space-x-2">
                        <button
                          onClick={() => {
                            setCurrentBatch(batch);
                            fetchBatchEmployees(batch.id);
                          }}
                          className="text-blue-600 hover:text-blue-700"
                        >
                          View
                        </button>
                        {batch.status === 'FINALIZED' && (
                          <button
                            onClick={() => {
                              const reference = prompt('Enter payment reference:');
                              if (reference) markAsPaid(batch.id, reference);
                            }}
                            className="text-green-600 hover:text-green-700"
                          >
                            Mark Paid
                          </button>
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

      {/* Preview Modal */}
      {previewModal && currentBatch && (
        <Modal
          isOpen={true}
          onClose={() => setPreviewModal(false)}
          title="Payroll Batch Preview"
          size="lg"
        >
          <div className="space-y-6">
            <div className="bg-blue-50 p-4 rounded-lg">
              <h4 className="font-medium text-blue-900 mb-2">Batch Summary</h4>
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div><span className="font-medium">Week Of:</span> {new Date(currentBatch.weekOf).toLocaleDateString()}</div>
                <div><span className="font-medium">Employees:</span> {currentBatch.employeeCount}</div>
                <div><span className="font-medium">Total Gross:</span> ${(currentBatch.totalAmount * 1.1).toFixed(2)}</div>
                <div><span className="font-medium">Total Deductions:</span> ${(currentBatch.totalAmount * 0.1).toFixed(2)}</div>
                <div><span className="font-medium">Total Net:</span> ${currentBatch.totalAmount.toFixed(2)}</div>
              </div>
            </div>

            <div>
              <h4 className="font-medium text-gray-900 mb-3">Payment Breakdown</h4>
              <div className="space-y-2 text-sm">
                {batchEmployees.map((employee) => (
                  <div key={employee.id} className="flex justify-between items-center py-2 border-b border-gray-100">
                    <span>{employee.name}</span>
                    <span className="font-medium">${employee.netPay.toFixed(2)}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setPreviewModal(false)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Close
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* Finalize Modal */}
      {finalizeModal && currentBatch && (
        <Modal
          isOpen={true}
          onClose={() => setFinalizeModal(false)}
          title="Finalize Payroll Batch"
        >
          <div className="space-y-4">
            <div className="bg-yellow-50 p-4 rounded-lg">
              <div className="flex">
                <svg className="w-5 h-5 text-yellow-400 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div>
                  <h4 className="text-sm font-medium text-yellow-800">Confirm Finalization</h4>
                  <p className="text-sm text-yellow-700 mt-1">
                    Once finalized, this payroll batch cannot be modified. Please review all details carefully.
                  </p>
                </div>
              </div>
            </div>

            <div className="text-sm">
              <div><span className="font-medium">Week Of:</span> {new Date(currentBatch.weekOf).toLocaleDateString()}</div>
              <div><span className="font-medium">Total Employees:</span> {currentBatch.employeeCount}</div>
              <div><span className="font-medium">Total Amount:</span> ${currentBatch.totalAmount.toLocaleString()}</div>
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setFinalizeModal(false)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={finalizeBatch}
                className="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700"
              >
                Finalize Batch
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
}
