'use client';

import { useState, useEffect } from 'react';
import { useSession } from 'next-auth/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { 
  DollarSign, 
  Clock, 
  CheckCircle,
  AlertCircle,
  Download,
  Play,
  Users,
  Calendar
} from 'lucide-react';

interface PayrollRecord {
  id: string;
  employeeId: string;
  employeeName: string;
  weekStart: string;
  weekEnd: string;
  hours: number;
  baseAmount: number;
  streakBonus: number;
  deductions: number;
  finalAmount: number;
  status: 'pending' | 'approved' | 'processing' | 'completed';
  approvedBy?: string;
  approvedAt?: string;
}

interface PayrollStats {
  totalEmployees: number;
  pendingApproval: number;
  weeklyTotal: number;
  processingQueue: number;
}

export default function PayrollDashboard() {
  const { data: session } = useSession();
  const [payrollRecords, setPayrollRecords] = useState<PayrollRecord[]>([]);
  const [stats, setStats] = useState<PayrollStats>({
    totalEmployees: 0,
    pendingApproval: 0,
    weeklyTotal: 0,
    processingQueue: 0
  });
  const [selectedRecords, setSelectedRecords] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);
  const [automationRunning, setAutomationRunning] = useState(false);

  useEffect(() => {
    fetchPayrollData();
  }, []);

  const fetchPayrollData = async () => {
    try {
      // Mock data - replace with actual API calls
      const mockRecords: PayrollRecord[] = [
        {
          id: '1',
          employeeId: 'EMP-001',
          employeeName: 'John Doe',
          weekStart: '2024-01-15',
          weekEnd: '2024-01-21',
          hours: 42.5,
          baseAmount: 5312.50,
          streakBonus: 500,
          deductions: 0,
          finalAmount: 5812.50,
          status: 'pending'
        },
        {
          id: '2',
          employeeId: 'EMP-002',
          employeeName: 'Jane Smith',
          weekStart: '2024-01-15',
          weekEnd: '2024-01-21',
          hours: 40.0,
          baseAmount: 5000.00,
          streakBonus: 500,
          deductions: 200,
          finalAmount: 5300.00,
          status: 'approved',
          approvedBy: 'CEO',
          approvedAt: '2024-01-22T10:30:00Z'
        }
      ];

      const mockStats: PayrollStats = {
        totalEmployees: 45,
        pendingApproval: 8,
        weeklyTotal: 125000,
        processingQueue: 3
      };

      setPayrollRecords(mockRecords);
      setStats(mockStats);
    } catch (error) {
      console.error('Failed to fetch payroll data:', error);
    } finally {
      setLoading(false);
    }
  };

  const runWeeklyAutomation = async () => {
    setAutomationRunning(true);
    try {
      // Simulate automation process
      await new Promise(resolve => setTimeout(resolve, 3000));
      
      // Refresh data after automation
      await fetchPayrollData();
      
      alert('Weekly payroll automation completed successfully!');
    } catch (error) {
      console.error('Automation failed:', error);
      alert('Automation failed. Please try again.');
    } finally {
      setAutomationRunning(false);
    }
  };

  const handleBulkApproval = async () => {
    if (selectedRecords.length === 0) {
      alert('Please select records to approve');
      return;
    }

    try {
      // Simulate bulk approval
      const updatedRecords = payrollRecords.map(record => {
        if (selectedRecords.includes(record.id)) {
          return {
            ...record,
            status: 'approved' as const,
            approvedBy: session?.user?.name || 'Current User',
            approvedAt: new Date().toISOString()
          };
        }
        return record;
      });

      setPayrollRecords(updatedRecords);
      setSelectedRecords([]);
      
      alert(`Approved ${selectedRecords.length} payroll records`);
    } catch (error) {
      console.error('Bulk approval failed:', error);
      alert('Bulk approval failed. Please try again.');
    }
  };

  const toggleRecordSelection = (recordId: string) => {
    setSelectedRecords(prev => 
      prev.includes(recordId) 
        ? prev.filter(id => id !== recordId)
        : [...prev, recordId]
    );
  };

  const getStatusBadge = (status: string) => {
    const variants = {
      pending: 'bg-yellow-100 text-yellow-800',
      approved: 'bg-green-100 text-green-800',
      processing: 'bg-blue-100 text-blue-800',
      completed: 'bg-gray-100 text-gray-800'
    };

    return (
      <Badge className={variants[status as keyof typeof variants] || variants.pending}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </Badge>
    );
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Payroll Management</h1>
          <p className="text-gray-600">Automated payroll processing and approval</p>
        </div>
        <div className="flex space-x-2">
          <Button 
            onClick={runWeeklyAutomation}
            disabled={automationRunning}
            className="bg-blue-600 hover:bg-blue-700"
          >
            {automationRunning ? (
              <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
            ) : (
              <Play className="h-4 w-4 mr-2" />
            )}
            Run Weekly Automation
          </Button>
          {session?.user?.role === 'ceo' && selectedRecords.length > 0 && (
            <Button onClick={handleBulkApproval} variant="outline">
              <CheckCircle className="h-4 w-4 mr-2" />
              Approve Selected ({selectedRecords.length})
            </Button>
          )}
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Employees</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.totalEmployees}</div>
            <p className="text-xs text-muted-foreground">Active employees</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending Approval</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-yellow-600">{stats.pendingApproval}</div>
            <p className="text-xs text-muted-foreground">Awaiting CEO approval</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Weekly Total</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">PKR {stats.weeklyTotal.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">This week's payroll</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Processing Queue</CardTitle>
            <AlertCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold text-blue-600">{stats.processingQueue}</div>
            <p className="text-xs text-muted-foreground">Ready for processing</p>
          </CardContent>
        </Card>
      </div>

      {/* Automation Status */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center">
            <Calendar className="h-5 w-5 mr-2" />
            Automation Status
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="flex items-center space-x-3">
              <div className="w-3 h-3 bg-green-500 rounded-full"></div>
              <span className="text-sm">Last Week: Generated</span>
            </div>
            <div className="flex items-center space-x-3">
              <div className="w-3 h-3 bg-yellow-500 rounded-full"></div>
              <span className="text-sm">Current Week: Pending</span>
            </div>
            <div className="flex items-center space-x-3">
              <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
              <span className="text-sm">Next Run: Tomorrow 9:00 AM</span>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Payroll Records Table */}
      <Card>
        <CardHeader>
          <CardTitle>Payroll Records</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead>
                <tr className="border-b">
                  <th className="text-left p-2">
                    <input 
                      type="checkbox" 
                      onChange={(e) => {
                        if (e.target.checked) {
                          setSelectedRecords(payrollRecords.map(r => r.id));
                        } else {
                          setSelectedRecords([]);
                        }
                      }}
                      checked={selectedRecords.length === payrollRecords.length}
                    />
                  </th>
                  <th className="text-left p-2">Employee</th>
                  <th className="text-left p-2">Week Period</th>
                  <th className="text-left p-2">Hours</th>
                  <th className="text-left p-2">Base Amount</th>
                  <th className="text-left p-2">Bonus</th>
                  <th className="text-left p-2">Deductions</th>
                  <th className="text-left p-2">Final Amount</th>
                  <th className="text-left p-2">Status</th>
                  <th className="text-left p-2">Actions</th>
                </tr>
              </thead>
              <tbody>
                {payrollRecords.map((record) => (
                  <tr key={record.id} className="border-b hover:bg-gray-50">
                    <td className="p-2">
                      <input 
                        type="checkbox"
                        checked={selectedRecords.includes(record.id)}
                        onChange={() => toggleRecordSelection(record.id)}
                      />
                    </td>
                    <td className="p-2">
                      <div>
                        <div className="font-medium">{record.employeeName}</div>
                        <div className="text-sm text-gray-500">{record.employeeId}</div>
                      </div>
                    </td>
                    <td className="p-2">
                      <div className="text-sm">
                        {new Date(record.weekStart).toLocaleDateString()} - 
                        {new Date(record.weekEnd).toLocaleDateString()}
                      </div>
                    </td>
                    <td className="p-2">{record.hours}h</td>
                    <td className="p-2">PKR {record.baseAmount.toLocaleString()}</td>
                    <td className="p-2">PKR {record.streakBonus.toLocaleString()}</td>
                    <td className="p-2">PKR {record.deductions.toLocaleString()}</td>
                    <td className="p-2 font-medium">PKR {record.finalAmount.toLocaleString()}</td>
                    <td className="p-2">{getStatusBadge(record.status)}</td>
                    <td className="p-2">
                      <div className="flex space-x-1">
                        {record.status === 'completed' && (
                          <Button size="sm" variant="outline">
                            <Download className="h-3 w-3" />
                          </Button>
                        )}
                        {record.status === 'pending' && session?.user?.role === 'ceo' && (
                          <Button size="sm" onClick={() => handleBulkApproval()}>
                            Approve
                          </Button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
