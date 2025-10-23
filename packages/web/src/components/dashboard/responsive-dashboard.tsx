'use client';

import React, { useState, useEffect } from 'react';
import { motion } from 'framer-motion';
import {
  MDBCard,
  MDBCardBody,
  MDBCardHeader,
  MDBContainer,
  MDBRow,
  MDBCol,
  MDBIcon,
  MDBProgress,
  MDBProgressBar,
  MDBBtn,
  MDBBadge,
  MDBListGroup,
  MDBListGroupItem,
  MDBTable,
  MDBTableHead,
  MDBTableBody
} from 'mdb-react-ui-kit';

interface DashboardStats {
  totalEmployees: number;
  activeProjects: number;
  pendingPayrolls: number;
  monthlyRevenue: number;
  attendanceRate: number;
  leaveRequests: number;
}

interface RecentActivity {
  id: string;
  type: 'payroll' | 'leave' | 'attendance' | 'payment';
  message: string;
  timestamp: Date;
  user: string;
}

export function ResponsiveDashboard() {
  const [stats, setStats] = useState<DashboardStats>({
    totalEmployees: 0,
    activeProjects: 0,
    pendingPayrolls: 0,
    monthlyRevenue: 0,
    attendanceRate: 0,
    leaveRequests: 0
  });

  const [recentActivities, setRecentActivities] = useState<RecentActivity[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Simulate API call
    setTimeout(() => {
      setStats({
        totalEmployees: 156,
        activeProjects: 23,
        pendingPayrolls: 8,
        monthlyRevenue: 125000,
        attendanceRate: 94.5,
        leaveRequests: 12
      });

      setRecentActivities([
        {
          id: '1',
          type: 'payroll',
          message: 'Payroll processed for December 2024',
          timestamp: new Date(),
          user: 'System'
        },
        {
          id: '2',
          type: 'leave',
          message: 'Leave request approved for John Doe',
          timestamp: new Date(Date.now() - 3600000),
          user: 'HR Manager'
        },
        {
          id: '3',
          type: 'payment',
          message: 'Payment of $2,500 received',
          timestamp: new Date(Date.now() - 7200000),
          user: 'Finance'
        }
      ]);

      setLoading(false);
    }, 1000);
  }, []);

  const containerVariants = {
    hidden: { opacity: 0 },
    visible: {
      opacity: 1,
      transition: {
        staggerChildren: 0.1
      }
    }
  };

  const cardVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { 
      opacity: 1, 
      y: 0,
      transition: { duration: 0.5 }
    }
  };

  const StatCard = ({ icon, title, value, color, trend }: {
    icon: string;
    title: string;
    value: string | number;
    color: string;
    trend?: number;
  }) => (
    <motion.div variants={cardVariants} whileHover={{ scale: 1.02 }}>
      <MDBCard className="h-100 shadow-sm">
        <MDBCardBody className="d-flex align-items-center">
          <div className={`rounded-circle p-3 me-3 bg-${color} bg-opacity-10`}>
            <MDBIcon icon={icon} className={`text-${color}`} size="2x" />
          </div>
          <div className="flex-grow-1">
            <h6 className="text-muted mb-1">{title}</h6>
            <h4 className="mb-0">{value}</h4>
            {trend && (
              <small className={`text-${trend > 0 ? 'success' : 'danger'}`}>
                <MDBIcon icon={trend > 0 ? 'arrow-up' : 'arrow-down'} className="me-1" />
                {Math.abs(trend)}%
              </small>
            )}
          </div>
        </MDBCardBody>
      </MDBCard>
    </motion.div>
  );

  const getActivityIcon = (type: string) => {
    switch (type) {
      case 'payroll': return 'money-bill-wave';
      case 'leave': return 'calendar-alt';
      case 'attendance': return 'clock';
      case 'payment': return 'credit-card';
      default: return 'info-circle';
    }
  };

  const getActivityColor = (type: string) => {
    switch (type) {
      case 'payroll': return 'success';
      case 'leave': return 'warning';
      case 'attendance': return 'info';
      case 'payment': return 'primary';
      default: return 'secondary';
    }
  };

  if (loading) {
    return (
      <MDBContainer fluid className="py-4">
        <div className="d-flex justify-content-center align-items-center" style={{ height: '400px' }}>
          <div className="text-center">
            <div className="spinner-border text-primary mb-3" role="status">
              <span className="visually-hidden">Loading...</span>
            </div>
            <p className="text-muted">Loading dashboard...</p>
          </div>
        </div>
      </MDBContainer>
    );
  }

  return (
    <MDBContainer fluid className="py-4">
      <motion.div
        variants={containerVariants}
        initial="hidden"
        animate="visible"
      >
        {/* Header */}
        <motion.div variants={cardVariants} className="mb-4">
          <div className="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
            <div>
              <h2 className="mb-1">Dashboard</h2>
              <p className="text-muted mb-0">Welcome back! Here's what's happening today.</p>
            </div>
            <div className="mt-3 mt-md-0">
              <MDBBtn color="primary" size="sm">
                <MDBIcon icon="download" className="me-2" />
                Export Report
              </MDBBtn>
            </div>
          </div>
        </motion.div>

        {/* Stats Cards */}
        <MDBRow className="mb-4">
          <MDBCol xl="3" lg="6" md="6" className="mb-4">
            <StatCard
              icon="users"
              title="Total Employees"
              value={stats.totalEmployees}
              color="primary"
              trend={5.2}
            />
          </MDBCol>
          <MDBCol xl="3" lg="6" md="6" className="mb-4">
            <StatCard
              icon="project-diagram"
              title="Active Projects"
              value={stats.activeProjects}
              color="success"
              trend={12.5}
            />
          </MDBCol>
          <MDBCol xl="3" lg="6" md="6" className="mb-4">
            <StatCard
              icon="money-bill-wave"
              title="Pending Payrolls"
              value={stats.pendingPayrolls}
              color="warning"
              trend={-2.1}
            />
          </MDBCol>
          <MDBCol xl="3" lg="6" md="6" className="mb-4">
            <StatCard
              icon="chart-line"
              title="Monthly Revenue"
              value={`$${stats.monthlyRevenue.toLocaleString()}`}
              color="info"
              trend={8.7}
            />
          </MDBCol>
        </MDBRow>

        <MDBRow>
          {/* Attendance Overview */}
          <MDBCol lg="8" className="mb-4">
            <motion.div variants={cardVariants}>
              <MDBCard className="h-100">
                <MDBCardHeader className="bg-transparent">
                  <div className="d-flex justify-content-between align-items-center">
                    <h5 className="mb-0">
                      <MDBIcon icon="chart-bar" className="me-2" />
                      Attendance Overview
                    </h5>
                    <MDBBadge color="success" pill>
                      {stats.attendanceRate}% This Month
                    </MDBBadge>
                  </div>
                </MDBCardHeader>
                <MDBCardBody>
                  <div className="mb-3">
                    <div className="d-flex justify-content-between mb-2">
                      <span>Overall Attendance</span>
                      <span>{stats.attendanceRate}%</span>
                    </div>
                    <MDBProgress height="10">
                      <MDBProgressBar 
                        width={stats.attendanceRate} 
                        valuemin={0} 
                        valuemax={100}
                        className="bg-success"
                      />
                    </MDBProgress>
                  </div>

                  <MDBRow className="text-center">
                    <MDBCol md="3" className="mb-3 mb-md-0">
                      <div className="border-end">
                        <h4 className="text-success mb-1">142</h4>
                        <small className="text-muted">Present Today</small>
                      </div>
                    </MDBCol>
                    <MDBCol md="3" className="mb-3 mb-md-0">
                      <div className="border-end">
                        <h4 className="text-warning mb-1">8</h4>
                        <small className="text-muted">On Leave</small>
                      </div>
                    </MDBCol>
                    <MDBCol md="3" className="mb-3 mb-md-0">
                      <div className="border-end">
                        <h4 className="text-danger mb-1">6</h4>
                        <small className="text-muted">Absent</small>
                      </div>
                    </MDBCol>
                    <MDBCol md="3">
                      <h4 className="text-info mb-1">12</h4>
                      <small className="text-muted">Late Arrivals</small>
                    </MDBCol>
                  </MDBRow>
                </MDBCardBody>
              </MDBCard>
            </motion.div>
          </MDBCol>

          {/* Recent Activities */}
          <MDBCol lg="4" className="mb-4">
            <motion.div variants={cardVariants}>
              <MDBCard className="h-100">
                <MDBCardHeader className="bg-transparent">
                  <h5 className="mb-0">
                    <MDBIcon icon="clock" className="me-2" />
                    Recent Activities
                  </h5>
                </MDBCardHeader>
                <MDBCardBody className="p-0">
                  <MDBListGroup flush>
                    {recentActivities.map((activity) => (
                      <MDBListGroupItem key={activity.id} className="d-flex align-items-start">
                        <div className={`rounded-circle p-2 me-3 bg-${getActivityColor(activity.type)} bg-opacity-10`}>
                          <MDBIcon 
                            icon={getActivityIcon(activity.type)} 
                            className={`text-${getActivityColor(activity.type)}`}
                            size="sm"
                          />
                        </div>
                        <div className="flex-grow-1">
                          <p className="mb-1 small">{activity.message}</p>
                          <small className="text-muted">
                            {activity.user} • {activity.timestamp.toLocaleTimeString()}
                          </small>
                        </div>
                      </MDBListGroupItem>
                    ))}
                  </MDBListGroup>
                  <div className="p-3 text-center">
                    <MDBBtn color="link" size="sm">
                      View All Activities
                    </MDBBtn>
                  </div>
                </MDBCardBody>
              </MDBCard>
            </motion.div>
          </MDBCol>
        </MDBRow>

        {/* Quick Actions */}
        <motion.div variants={cardVariants}>
          <MDBCard>
            <MDBCardHeader className="bg-transparent">
              <h5 className="mb-0">
                <MDBIcon icon="bolt" className="me-2" />
                Quick Actions
              </h5>
            </MDBCardHeader>
            <MDBCardBody>
              <MDBRow>
                <MDBCol xl="2" lg="3" md="4" sm="6" className="mb-3">
                  <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }}>
                    <MDBBtn color="primary" className="w-100" outline>
                      <MDBIcon icon="plus" className="mb-2 d-block" />
                      <small>Add Employee</small>
                    </MDBBtn>
                  </motion.div>
                </MDBCol>
                <MDBCol xl="2" lg="3" md="4" sm="6" className="mb-3">
                  <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }}>
                    <MDBBtn color="success" className="w-100" outline>
                      <MDBIcon icon="money-bill" className="mb-2 d-block" />
                      <small>Process Payroll</small>
                    </MDBBtn>
                  </motion.div>
                </MDBCol>
                <MDBCol xl="2" lg="3" md="4" sm="6" className="mb-3">
                  <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }}>
                    <MDBBtn color="warning" className="w-100" outline>
                      <MDBIcon icon="calendar-check" className="mb-2 d-block" />
                      <small>Approve Leaves</small>
                    </MDBBtn>
                  </motion.div>
                </MDBCol>
                <MDBCol xl="2" lg="3" md="4" sm="6" className="mb-3">
                  <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }}>
                    <MDBBtn color="info" className="w-100" outline>
                      <MDBIcon icon="file-invoice" className="mb-2 d-block" />
                      <small>Generate Report</small>
                    </MDBBtn>
                  </motion.div>
                </MDBCol>
                <MDBCol xl="2" lg="3" md="4" sm="6" className="mb-3">
                  <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }}>
                    <MDBBtn color="secondary" className="w-100" outline>
                      <MDBIcon icon="cog" className="mb-2 d-block" />
                      <small>Settings</small>
                    </MDBBtn>
                  </motion.div>
                </MDBCol>
                <MDBCol xl="2" lg="3" md="4" sm="6" className="mb-3">
                  <motion.div whileHover={{ scale: 1.05 }} whileTap={{ scale: 0.95 }}>
                    <MDBBtn color="dark" className="w-100" outline>
                      <MDBIcon icon="question-circle" className="mb-2 d-block" />
                      <small>Help & Support</small>
                    </MDBBtn>
                  </motion.div>
                </MDBCol>
              </MDBRow>
            </MDBCardBody>
          </MDBCard>
        </motion.div>
      </motion.div>
    </MDBContainer>
  );
}
