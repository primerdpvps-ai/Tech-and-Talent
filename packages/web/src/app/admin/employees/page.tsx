'use client';

import { useState, useEffect } from 'react';
import { Modal } from '@/components/ui/modal';

interface Employee {
  id: string;
  name: string;
  email: string;
  phone: string;
  role: 'NEW_EMPLOYEE' | 'EMPLOYEE' | 'MANAGER' | 'ADMIN';
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';
  hireDate: string;
  tenure: number; // in days
  currentStreak: number; // consecutive working days
  longestStreak: number;
  penalties: Penalty[];
  rdpDetails: {
    server: string;
    username: string;
    lastAccess: string;
    totalHours: number;
  };
  performance: {
    weeklyAverage: number;
    monthlyAverage: number;
    totalEarnings: number;
  };
}

interface Penalty {
  id: string;
  type: 'LATE' | 'ABSENCE' | 'PERFORMANCE' | 'POLICY_VIOLATION';
  description: string;
  amount: number;
  appliedAt: string;
  appliedBy: string;
}

export default function AdminEmployeesPage() {
  const [employees, setEmployees] = useState<Employee[]>([]);
  const [filteredEmployees, setFilteredEmployees] = useState<Employee[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [selectedEmployee, setSelectedEmployee] = useState<Employee | null>(null);
  const [roleChangeModal, setRoleChangeModal] = useState<{ employee: Employee; newRole: string } | null>(null);
  const [penaltyModal, setPenaltyModal] = useState<Employee | null>(null);
  const [penaltyForm, setPenaltyForm] = useState({ type: 'LATE', description: '', amount: 0 });
  const [isProcessing, setIsProcessing] = useState(false);

  // Filters
  const [roleFilter, setRoleFilter] = useState<string>('all');
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    fetchEmployees();
  }, []);

  useEffect(() => {
    filterEmployees();
  }, [employees, roleFilter, statusFilter, searchQuery]);

  const fetchEmployees = async () => {
    try {
      setIsLoading(true);
      // Mock data for demonstration
      const mockEmployees: Employee[] = [
        {
          id: '1',
          name: 'John Smith',
          email: 'john.smith@company.com',
          phone: '+1234567890',
          role: 'EMPLOYEE',
          status: 'ACTIVE',
          hireDate: '2024-01-15',
          tenure: 90,
          currentStreak: 15,
          longestStreak: 45,
          penalties: [
            {
              id: 'p1',
              type: 'LATE',
              description: 'Late arrival - 30 minutes',
              amount: 5,
              appliedAt: '2024-03-01',
              appliedBy: 'Admin'
            }
          ],
          rdpDetails: {
            server: 'RDP-SERVER-01',
            username: 'john.smith',
            lastAccess: '2024-03-15T09:00:00Z',
            totalHours: 720
          },
          performance: {
            weeklyAverage: 85,
            monthlyAverage: 82,
            totalEarnings: 2400
          }
        },
        {
          id: '2',
          name: 'Sarah Johnson',
          email: 'sarah.johnson@company.com',
          phone: '+1234567891',
          role: 'MANAGER',
          status: 'ACTIVE',
          hireDate: '2023-08-20',
          tenure: 210,
          currentStreak: 28,
          longestStreak: 60,
          penalties: [],
          rdpDetails: {
            server: 'RDP-SERVER-02',
            username: 'sarah.johnson',
            lastAccess: '2024-03-15T08:30:00Z',
            totalHours: 1680
          },
          performance: {
            weeklyAverage: 92,
            monthlyAverage: 89,
            totalEarnings: 5200
          }
        }
      ];
      
      setEmployees(mockEmployees);
    } catch (error) {
      console.error('Failed to fetch employees:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const filterEmployees = () => {
    let filtered = employees;

    if (roleFilter !== 'all') {
      filtered = filtered.filter(emp => emp.role === roleFilter);
    }

    if (statusFilter !== 'all') {
      filtered = filtered.filter(emp => emp.status === statusFilter);
    }

    if (searchQuery) {
      const query = searchQuery.toLowerCase();
      filtered = filtered.filter(emp =>
        emp.name.toLowerCase().includes(query) ||
        emp.email.toLowerCase().includes(query)
      );
    }

    setFilteredEmployees(filtered);
  };

  const handleRoleChange = async (employeeId: string, newRole: string) => {
    try {
      setIsProcessing(true);
      const response = await fetch(`/api/admin/employees/${employeeId}/role`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ role: newRole })
      });

      if (response.ok) {
        await fetchEmployees();
        setRoleChangeModal(null);
      }
    } catch (error) {
      console.error('Failed to change role:', error);
    } finally {
      setIsProcessing(false);
    }
  };

  const handleApplyPenalty = async (employeeId: string, penalty: any) => {
    try {
      setIsProcessing(true);
      const response = await fetch(`/api/admin/employees/${employeeId}/penalty`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(penalty)
      });

      if (response.ok) {
        await fetchEmployees();
        setPenaltyModal(null);
        setPenaltyForm({ type: 'LATE', description: '', amount: 0 });
      }
    } catch (error) {
      console.error('Failed to apply penalty:', error);
    } finally {
      setIsProcessing(false);
    }
  };

  const getRoleBadge = (role: string) => {
    const colors = {
      NEW_EMPLOYEE: 'bg-blue-100 text-blue-800',
      EMPLOYEE: 'bg-green-100 text-green-800',
      MANAGER: 'bg-purple-100 text-purple-800',
      ADMIN: 'bg-red-100 text-red-800'
    };
    return (
      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[role as keyof typeof colors]}`}>
        {role.replace('_', ' ')}
      </span>
    );
  };

  const getStatusBadge = (status: string) => {
    const colors = {
      ACTIVE: 'bg-green-100 text-green-800',
      INACTIVE: 'bg-gray-100 text-gray-800',
      SUSPENDED: 'bg-red-100 text-red-800'
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
          <h1 className="text-2xl font-bold text-gray-900">Employees</h1>
          <p className="text-gray-600">Manage employee records and roles</p>
        </div>
        <button
          onClick={fetchEmployees}
          className="bg-white border border-gray-300 rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Refresh
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <input
            type="text"
            placeholder="Search employees..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm"
          />
          <select
            value={roleFilter}
            onChange={(e) => setRoleFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm"
          >
            <option value="all">All Roles</option>
            <option value="NEW_EMPLOYEE">New Employee</option>
            <option value="EMPLOYEE">Employee</option>
            <option value="MANAGER">Manager</option>
            <option value="ADMIN">Admin</option>
          </select>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="border border-gray-300 rounded-lg px-3 py-2 text-sm"
          >
            <option value="all">All Statuses</option>
            <option value="ACTIVE">Active</option>
            <option value="INACTIVE">Inactive</option>
            <option value="SUSPENDED">Suspended</option>
          </select>
          <div className="text-sm text-gray-600 flex items-center">
            {filteredEmployees.length} of {employees.length} employees
          </div>
        </div>
      </div>

      {/* Employees Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p className="text-gray-500">Loading employees...</p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tenure</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Performance</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredEmployees.map((employee) => (
                  <tr key={employee.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{employee.name}</div>
                        <div className="text-sm text-gray-500">{employee.email}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">{getRoleBadge(employee.role)}</td>
                    <td className="px-6 py-4 whitespace-nowrap">{getStatusBadge(employee.status)}</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <div>{employee.tenure} days</div>
                      <div className="text-xs text-gray-500">Streak: {employee.currentStreak}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <div>{employee.performance.weeklyAverage}% weekly</div>
                      <div className="text-xs text-gray-500">${employee.performance.totalEarnings} total</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div className="flex space-x-2">
                        <button
                          onClick={() => setSelectedEmployee(employee)}
                          className="text-blue-600 hover:text-blue-700"
                        >
                          View
                        </button>
                        <button
                          onClick={() => setRoleChangeModal({ employee, newRole: employee.role })}
                          className="text-purple-600 hover:text-purple-700"
                        >
                          Role
                        </button>
                        <button
                          onClick={() => setPenaltyModal(employee)}
                          className="text-red-600 hover:text-red-700"
                        >
                          Penalty
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Employee Detail Modal */}
      {selectedEmployee && (
        <Modal
          isOpen={true}
          onClose={() => setSelectedEmployee(null)}
          title={`Employee Details - ${selectedEmployee.name}`}
          size="lg"
        >
          <div className="space-y-6">
            <div className="grid grid-cols-2 gap-6">
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-3">Basic Information</h4>
                <div className="space-y-2 text-sm">
                  <div><span className="font-medium">Name:</span> {selectedEmployee.name}</div>
                  <div><span className="font-medium">Email:</span> {selectedEmployee.email}</div>
                  <div><span className="font-medium">Phone:</span> {selectedEmployee.phone}</div>
                  <div><span className="font-medium">Role:</span> {getRoleBadge(selectedEmployee.role)}</div>
                  <div><span className="font-medium">Status:</span> {getStatusBadge(selectedEmployee.status)}</div>
                </div>
              </div>
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-3">Work Statistics</h4>
                <div className="space-y-2 text-sm">
                  <div><span className="font-medium">Hire Date:</span> {new Date(selectedEmployee.hireDate).toLocaleDateString()}</div>
                  <div><span className="font-medium">Tenure:</span> {selectedEmployee.tenure} days</div>
                  <div><span className="font-medium">Current Streak:</span> {selectedEmployee.currentStreak} days</div>
                  <div><span className="font-medium">Longest Streak:</span> {selectedEmployee.longestStreak} days</div>
                  <div><span className="font-medium">Total Earnings:</span> ${selectedEmployee.performance.totalEarnings}</div>
                </div>
              </div>
            </div>

            <div>
              <h4 className="text-sm font-medium text-gray-900 mb-3">RDP Details</h4>
              <div className="bg-gray-50 p-3 rounded-lg text-sm">
                <div><span className="font-medium">Server:</span> {selectedEmployee.rdpDetails.server}</div>
                <div><span className="font-medium">Username:</span> {selectedEmployee.rdpDetails.username}</div>
                <div><span className="font-medium">Last Access:</span> {new Date(selectedEmployee.rdpDetails.lastAccess).toLocaleString()}</div>
                <div><span className="font-medium">Total Hours:</span> {selectedEmployee.rdpDetails.totalHours}</div>
              </div>
            </div>

            {selectedEmployee.penalties.length > 0 && (
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-3">Penalties</h4>
                <div className="space-y-2">
                  {selectedEmployee.penalties.map((penalty) => (
                    <div key={penalty.id} className="bg-red-50 p-3 rounded-lg text-sm">
                      <div className="flex justify-between items-start">
                        <div>
                          <div className="font-medium text-red-800">{penalty.type}</div>
                          <div className="text-red-700">{penalty.description}</div>
                          <div className="text-xs text-red-600 mt-1">
                            Applied on {new Date(penalty.appliedAt).toLocaleDateString()} by {penalty.appliedBy}
                          </div>
                        </div>
                        <div className="text-red-800 font-medium">${penalty.amount}</div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            <div className="flex justify-end">
              <button
                onClick={() => setSelectedEmployee(null)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Close
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* Role Change Modal */}
      {roleChangeModal && (
        <Modal
          isOpen={true}
          onClose={() => setRoleChangeModal(null)}
          title={`Change Role - ${roleChangeModal.employee.name}`}
        >
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">New Role</label>
              <select
                value={roleChangeModal.newRole}
                onChange={(e) => setRoleChangeModal({ ...roleChangeModal, newRole: e.target.value })}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              >
                <option value="NEW_EMPLOYEE">New Employee</option>
                <option value="EMPLOYEE">Employee</option>
                <option value="MANAGER">Manager</option>
                <option value="ADMIN">Admin</option>
              </select>
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setRoleChangeModal(null)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                disabled={isProcessing}
              >
                Cancel
              </button>
              <button
                onClick={() => handleRoleChange(roleChangeModal.employee.id, roleChangeModal.newRole)}
                disabled={isProcessing}
                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
              >
                {isProcessing ? 'Updating...' : 'Update Role'}
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* Penalty Modal */}
      {penaltyModal && (
        <Modal
          isOpen={true}
          onClose={() => setPenaltyModal(null)}
          title={`Apply Penalty - ${penaltyModal.name}`}
        >
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Penalty Type</label>
              <select
                value={penaltyForm.type}
                onChange={(e) => setPenaltyForm({ ...penaltyForm, type: e.target.value as any })}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              >
                <option value="LATE">Late Arrival</option>
                <option value="ABSENCE">Unexcused Absence</option>
                <option value="PERFORMANCE">Performance Issue</option>
                <option value="POLICY_VIOLATION">Policy Violation</option>
              </select>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Description</label>
              <textarea
                value={penaltyForm.description}
                onChange={(e) => setPenaltyForm({ ...penaltyForm, description: e.target.value })}
                rows={3}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                placeholder="Describe the reason for this penalty..."
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Penalty Amount ($)</label>
              <input
                type="number"
                value={penaltyForm.amount}
                onChange={(e) => setPenaltyForm({ ...penaltyForm, amount: Number(e.target.value) })}
                min="0"
                step="0.01"
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              />
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setPenaltyModal(null)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                disabled={isProcessing}
              >
                Cancel
              </button>
              <button
                onClick={() => handleApplyPenalty(penaltyModal.id, penaltyForm)}
                disabled={isProcessing || !penaltyForm.description || penaltyForm.amount <= 0}
                className="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50"
              >
                {isProcessing ? 'Applying...' : 'Apply Penalty'}
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
}
