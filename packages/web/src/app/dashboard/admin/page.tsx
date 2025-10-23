'use client';

import { useState, useEffect } from 'react';
import { useSession } from 'next-auth/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Users, 
  DollarSign, 
  Clock, 
  TrendingUp,
  FileText,
  UserCheck,
  AlertCircle,
  Calendar
} from 'lucide-react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, BarChart, Bar } from 'recharts';

interface DashboardStats {
  totalEmployees: number;
  activeEmployees: number;
  totalPayroll: number;
  pendingApprovals: number;
  recentApplications: number;
  systemHealth: string;
}

interface ChartData {
  name: string;
  value: number;
  payroll?: number;
}

export default function AdminDashboard() {
  const { data: session } = useSession();
  const [stats, setStats] = useState<DashboardStats>({
    totalEmployees: 0,
    activeEmployees: 0,
    totalPayroll: 0,
    pendingApprovals: 0,
    recentApplications: 0,
    systemHealth: 'Good'
  });
  const [chartData, setChartData] = useState<ChartData[]>([]);
  const [payrollData, setPayrollData] = useState<ChartData[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      // Simulate API calls - replace with actual API endpoints
      const mockStats: DashboardStats = {
        totalEmployees: 45,
        activeEmployees: 42,
        totalPayroll: 125000,
        pendingApprovals: 8,
        recentApplications: 12,
        systemHealth: 'Excellent'
      };

      const mockChartData: ChartData[] = [
        { name: 'Jan', value: 35 },
        { name: 'Feb', value: 38 },
        { name: 'Mar', value: 42 },
        { name: 'Apr', value: 45 },
        { name: 'May', value: 43 },
        { name: 'Jun', value: 45 }
      ];

      const mockPayrollData: ChartData[] = [
        { name: 'Week 1', payroll: 28000 },
        { name: 'Week 2', payroll: 32000 },
        { name: 'Week 3', payroll: 30000 },
        { name: 'Week 4', payroll: 35000 }
      ];

      setStats(mockStats);
      setChartData(mockChartData);
      setPayrollData(mockPayrollData);
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
    } finally {
      setLoading(false);
    }
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
          <h1 className="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
          <p className="text-gray-600">Welcome back, {session?.user?.name}</p>
        </div>
        <div className="flex space-x-2">
          <Button variant="outline" size="sm">
            <FileText className="h-4 w-4 mr-2" />
            Generate Report
          </Button>
          <Button size="sm">
            <UserCheck className="h-4 w-4 mr-2" />
            Manage Users
          </Button>
        </div>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Employees</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.totalEmployees}</div>
            <p className="text-xs text-muted-foreground">
              {stats.activeEmployees} active
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Monthly Payroll</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">PKR {stats.totalPayroll.toLocaleString()}</div>
            <p className="text-xs text-muted-foreground">
              +12% from last month
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending Approvals</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats.pendingApprovals}</div>
            <p className="text-xs text-muted-foreground">
              Requires attention
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">System Health</CardTitle>
            <TrendingUp className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              <Badge variant="secondary" className="bg-green-100 text-green-800">
                {stats.systemHealth}
              </Badge>
            </div>
            <p className="text-xs text-muted-foreground">
              All systems operational
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Employee Growth Chart */}
        <Card>
          <CardHeader>
            <CardTitle>Employee Growth</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <LineChart data={chartData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip />
                <Line 
                  type="monotone" 
                  dataKey="value" 
                  stroke="#2563eb" 
                  strokeWidth={2}
                  dot={{ fill: '#2563eb' }}
                />
              </LineChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>

        {/* Weekly Payroll Chart */}
        <Card>
          <CardHeader>
            <CardTitle>Weekly Payroll</CardTitle>
          </CardHeader>
          <CardContent>
            <ResponsiveContainer width="100%" height={300}>
              <BarChart data={payrollData}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="name" />
                <YAxis />
                <Tooltip formatter={(value) => [`PKR ${value?.toLocaleString()}`, 'Payroll']} />
                <Bar dataKey="payroll" fill="#10b981" />
              </BarChart>
            </ResponsiveContainer>
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center">
              <AlertCircle className="h-5 w-5 mr-2 text-orange-500" />
              Pending Actions
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-sm">Payroll Approvals</span>
              <Badge variant="outline">{stats.pendingApprovals}</Badge>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm">Leave Requests</span>
              <Badge variant="outline">3</Badge>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm">New Applications</span>
              <Badge variant="outline">{stats.recentApplications}</Badge>
            </div>
            <Button className="w-full mt-4" size="sm">
              Review All
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center">
              <Calendar className="h-5 w-5 mr-2 text-blue-500" />
              Recent Activity
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="text-sm">
              <div className="font-medium">Payroll processed</div>
              <div className="text-gray-500">2 hours ago</div>
            </div>
            <div className="text-sm">
              <div className="font-medium">New employee onboarded</div>
              <div className="text-gray-500">1 day ago</div>
            </div>
            <div className="text-sm">
              <div className="font-medium">System backup completed</div>
              <div className="text-gray-500">2 days ago</div>
            </div>
            <Button variant="outline" className="w-full mt-4" size="sm">
              View All Activity
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center">
              <TrendingUp className="h-5 w-5 mr-2 text-green-500" />
              Performance Metrics
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="flex justify-between items-center">
              <span className="text-sm">Avg. Productivity</span>
              <span className="font-medium text-green-600">94%</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm">On-time Delivery</span>
              <span className="font-medium text-green-600">98%</span>
            </div>
            <div className="flex justify-between items-center">
              <span className="text-sm">Employee Satisfaction</span>
              <span className="font-medium text-green-600">4.8/5</span>
            </div>
            <Button variant="outline" className="w-full mt-4" size="sm">
              Detailed Report
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
