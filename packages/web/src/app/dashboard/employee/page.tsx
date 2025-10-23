'use client';

import { useSession } from 'next-auth/react';
import { redirect } from 'next/navigation';
import { useState, useEffect } from 'react';
import Link from 'next/link';

// Mock data for charts and stats
const mockEarningsData = {
  today: { hours: 6.5, amount: 812.50 },
  yesterday: { hours: 7.2, amount: 900.00 },
  thisWeek: { hours: 32.5, amount: 4062.50 },
  thisMonth: { hours: 142.3, amount: 17787.50 },
};

const mockTimerState = {
  isActive: false,
  startTime: null as Date | null,
  elapsedTime: 0,
  todayTotal: 23400, // seconds (6.5 hours)
};

export default function EmployeeDashboard() {
  const { data: session, status } = useSession();
  const [timerState, setTimerState] = useState(mockTimerState);
  const [currentTime, setCurrentTime] = useState(new Date());

  if (status === 'loading') {
    return <div className="p-6">Loading...</div>;
  }

  if (!session || session.user.role !== 'EMPLOYEE') {
    redirect('/');
  }

  // Update current time every second
  useEffect(() => {
    const timer = setInterval(() => {
      setCurrentTime(new Date());
      if (timerState.isActive && timerState.startTime) {
        setTimerState(prev => ({
          ...prev,
          elapsedTime: Math.floor((Date.now() - prev.startTime!.getTime()) / 1000)
        }));
      }
    }, 1000);

    return () => clearInterval(timer);
  }, [timerState.isActive, timerState.startTime]);

  const handleTimerToggle = () => {
    if (timerState.isActive) {
      // Stop timer
      setTimerState(prev => ({
        ...prev,
        isActive: false,
        startTime: null,
        todayTotal: prev.todayTotal + prev.elapsedTime,
        elapsedTime: 0,
      }));
    } else {
      // Start timer
      setTimerState(prev => ({
        ...prev,
        isActive: true,
        startTime: new Date(),
        elapsedTime: 0,
      }));
    }
  };

  const formatTime = (seconds: number) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  const formatHours = (seconds: number) => {
    return (seconds / 3600).toFixed(1);
  };

  const currentSessionTime = timerState.elapsedTime;
  const totalTodayTime = timerState.todayTotal + currentSessionTime;

  return (
    <div className="p-6">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">
          Welcome back, {session.user.fullName}!
        </h1>
        <p className="mt-2 text-gray-600">
          Track your time, monitor your earnings, and access your work tools.
        </p>
      </div>

      {/* Time Tracker Card */}
      <div className="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-xl shadow-lg p-8 mb-8">
        <div className="text-center">
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Time Tracker</h2>
          <p className="text-gray-600 mb-6">Current session time</p>
          
          <div className="text-6xl font-mono font-bold text-blue-600 mb-6">
            {formatTime(currentSessionTime)}
          </div>
          
          <div className="flex justify-center space-x-4 mb-6">
            <button
              onClick={handleTimerToggle}
              className={`px-8 py-4 rounded-lg font-semibold text-lg transition-colors ${
                timerState.isActive
                  ? 'bg-red-600 text-white hover:bg-red-700'
                  : 'bg-green-600 text-white hover:bg-green-700'
              }`}
            >
              {timerState.isActive ? '⏸️ Pause Timer' : '▶️ Start Timer'}
            </button>
          </div>
          
          <div className="grid grid-cols-2 gap-4 text-center">
            <div className="bg-white bg-opacity-50 rounded-lg p-4">
              <p className="text-sm text-gray-600">Today's Total</p>
              <p className="text-xl font-bold text-gray-900">{formatHours(totalTodayTime)}h</p>
            </div>
            <div className="bg-white bg-opacity-50 rounded-lg p-4">
              <p className="text-sm text-gray-600">Status</p>
              <p className={`text-xl font-bold ${timerState.isActive ? 'text-green-600' : 'text-gray-500'}`}>
                {timerState.isActive ? 'Working' : 'Offline'}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div className="bg-white rounded-xl shadow-lg p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
              <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
              </svg>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">${mockEarningsData.today.amount}</p>
              <p className="text-sm text-gray-600">Today's Earnings</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-lg p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
              <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">{mockEarningsData.thisWeek.hours}h</p>
              <p className="text-sm text-gray-600">This Week</p>
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
              <p className="text-2xl font-bold text-gray-900">95%</p>
              <p className="text-sm text-gray-600">Productivity</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl shadow-lg p-6">
          <div className="flex items-center">
            <div className="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
              <svg className="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
            </div>
            <div>
              <p className="text-2xl font-bold text-gray-900">12</p>
              <p className="text-sm text-gray-600">Day Streak</p>
            </div>
          </div>
        </div>
      </div>

      {/* Main Content Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left Column - Charts and Earnings */}
        <div className="lg:col-span-2 space-y-8">
          {/* Earnings Chart */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-6">Weekly Earnings</h3>
            <div className="h-64 flex items-end justify-between space-x-2">
              {['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'].map((day, index) => {
                const height = Math.random() * 80 + 20; // Mock data
                const amount = (height * 10).toFixed(0);
                return (
                  <div key={day} className="flex-1 flex flex-col items-center">
                    <div className="text-xs text-gray-600 mb-2">${amount}</div>
                    <div 
                      className="w-full bg-blue-500 rounded-t-sm transition-all hover:bg-blue-600"
                      style={{ height: `${height}%` }}
                    ></div>
                    <div className="text-xs text-gray-500 mt-2">{day}</div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* Activity Chart */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-bold text-gray-900 mb-6">Daily Activity</h3>
            <div className="h-48 flex items-center justify-center">
              <div className="relative w-32 h-32">
                <svg className="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                  <path
                    d="M18 2.0845
                      a 15.9155 15.9155 0 0 1 0 31.831
                      a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none"
                    stroke="#e5e7eb"
                    strokeWidth="3"
                  />
                  <path
                    d="M18 2.0845
                      a 15.9155 15.9155 0 0 1 0 31.831
                      a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none"
                    stroke="#3b82f6"
                    strokeWidth="3"
                    strokeDasharray="95, 100"
                  />
                </svg>
                <div className="absolute inset-0 flex items-center justify-center">
                  <span className="text-2xl font-bold text-gray-900">95%</span>
                </div>
              </div>
            </div>
            <div className="text-center mt-4">
              <p className="text-sm text-gray-600">Active time vs total time</p>
            </div>
          </div>
        </div>

        {/* Right Column - Tools and Actions */}
        <div className="space-y-6">
          {/* RDP Access */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <div className="flex items-center mb-4">
              <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                <svg className="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
              </div>
              <h3 className="text-lg font-semibold text-gray-900">RDP Access</h3>
            </div>
            <p className="text-gray-600 mb-4 text-sm">
              Connect to your assigned remote desktop for work tasks.
            </p>
            <button className="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition-colors font-medium">
              🖥️ Launch RDP
            </button>
            <div className="mt-3 text-xs text-gray-500">
              <p>Host: rdp-server-01.tts-pms.com</p>
              <p>Status: <span className="text-green-600">● Online</span></p>
            </div>
          </div>

          {/* Quick Actions */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div className="space-y-3">
              <Link
                href="/dashboard/employee/leaves"
                className="w-full bg-gray-50 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-100 transition-colors font-medium text-center block"
              >
                📅 Request Leave
              </Link>
              <Link
                href="/dashboard/employee/upload"
                className="w-full bg-gray-50 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-100 transition-colors font-medium text-center block"
              >
                📁 Weekly Upload
              </Link>
              <Link
                href="/dashboard/employee/earnings"
                className="w-full bg-gray-50 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-100 transition-colors font-medium text-center block"
              >
                💰 View Earnings
              </Link>
            </div>
          </div>

          {/* Today's Summary */}
          <div className="bg-white rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Today's Summary</h3>
            <div className="space-y-3">
              <div className="flex justify-between">
                <span className="text-gray-600">Hours Worked</span>
                <span className="font-medium">{formatHours(totalTodayTime)}h</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Earnings</span>
                <span className="font-medium text-green-600">${mockEarningsData.today.amount}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Screenshots</span>
                <span className="font-medium">47</span>
              </div>
              <div className="flex justify-between">
                <span className="text-gray-600">Activity Score</span>
                <span className="font-medium text-blue-600">95%</span>
              </div>
            </div>
          </div>

          {/* Operational Hours */}
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div className="ml-3">
                <h4 className="text-sm font-medium text-blue-800">Operational Hours</h4>
                <p className="text-sm text-blue-700 mt-1">
                  Standard: 11:00 AM - 2:00 AM PKT<br />
                  Current PKT Time: {currentTime.toLocaleTimeString('en-US', { 
                    timeZone: 'Asia/Karachi',
                    hour12: true 
                  })}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
