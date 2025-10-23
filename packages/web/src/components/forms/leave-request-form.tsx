'use client';

import React, { useState, useEffect } from 'react';

interface LeaveRequestFormProps {
  onSubmit: (data: LeaveRequestData) => Promise<boolean>;
  isLoading?: boolean;
  className?: string;
}

interface LeaveRequestData {
  type: 'SHORT' | 'ONE_DAY' | 'LONG';
  dateFrom: string;
  dateTo: string;
  reason: string;
  isEmergency?: boolean;
}

interface LeavePolicy {
  type: 'SHORT' | 'ONE_DAY' | 'LONG';
  name: string;
  description: string;
  minNoticeHours: number;
  maxDuration: number;
  weeklyLimit?: number;
  monthlyLimit?: number;
}

const leavePolicies: LeavePolicy[] = [
  {
    type: 'SHORT',
    name: 'Short Leave',
    description: 'Up to 4 hours within a day',
    minNoticeHours: 2,
    maxDuration: 4,
    weeklyLimit: 8,
    monthlyLimit: 16
  },
  {
    type: 'ONE_DAY',
    name: 'One Day Leave',
    description: 'Full day off',
    minNoticeHours: 24,
    maxDuration: 1,
    weeklyLimit: 1,
    monthlyLimit: 4
  },
  {
    type: 'LONG',
    name: 'Long Leave',
    description: '2+ consecutive days',
    minNoticeHours: 168, // 7 days
    maxDuration: 14,
    monthlyLimit: 7
  }
];

export function LeaveRequestForm({ onSubmit, isLoading = false, className = '' }: LeaveRequestFormProps) {
  const [formData, setFormData] = useState<LeaveRequestData>({
    type: 'ONE_DAY',
    dateFrom: '',
    dateTo: '',
    reason: '',
    isEmergency: false
  });
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [warnings, setWarnings] = useState<string[]>([]);

  const selectedPolicy = leavePolicies.find(policy => policy.type === formData.type);

  useEffect(() => {
    // Auto-set dateTo for SHORT and ONE_DAY leaves
    if (formData.dateFrom) {
      if (formData.type === 'SHORT' || formData.type === 'ONE_DAY') {
        setFormData((prev: LeaveRequestData) => ({ ...prev, dateTo: prev.dateFrom }));
      }
    }
  }, [formData.type, formData.dateFrom]);

  useEffect(() => {
    validateForm();
  }, [formData]);

  const validateForm = () => {
    const newErrors: Record<string, string> = {};
    const newWarnings: string[] = [];

    // Date validation
    if (!formData.dateFrom) {
      newErrors.dateFrom = 'Start date is required';
    }

    if (!formData.dateTo) {
      newErrors.dateTo = 'End date is required';
    }

    if (formData.dateFrom && formData.dateTo) {
      const startDate = new Date(formData.dateFrom);
      const endDate = new Date(formData.dateTo);
      const now = new Date();

      // Check if dates are in the past
      if (startDate < now && !formData.isEmergency) {
        newErrors.dateFrom = 'Start date cannot be in the past';
      }

      // Check if end date is before start date
      if (endDate < startDate) {
        newErrors.dateTo = 'End date cannot be before start date';
      }

      // Calculate duration and notice period
      const durationDays = Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24)) + 1;
      const noticeHours = (startDate.getTime() - now.getTime()) / (1000 * 60 * 60);

      if (selectedPolicy) {
        // Check duration limits
        if (formData.type === 'SHORT') {
          // For short leave, we don't check days but assume it's within the same day
          if (startDate.toDateString() !== endDate.toDateString()) {
            newErrors.dateTo = 'Short leave must be within the same day';
          }
        } else if (formData.type === 'ONE_DAY') {
          if (durationDays > 1) {
            newErrors.dateTo = 'One day leave cannot exceed 1 day';
          }
        } else if (formData.type === 'LONG') {
          if (durationDays < 2) {
            newErrors.dateFrom = 'Long leave must be at least 2 days';
          }
          if (durationDays > selectedPolicy.maxDuration) {
            newErrors.dateTo = `Long leave cannot exceed ${selectedPolicy.maxDuration} days`;
          }
        }

        // Check notice period
        if (!formData.isEmergency && noticeHours < selectedPolicy.minNoticeHours) {
          const requiredNotice = selectedPolicy.minNoticeHours >= 24 
            ? `${selectedPolicy.minNoticeHours / 24} day(s)`
            : `${selectedPolicy.minNoticeHours} hour(s)`;
          newWarnings.push(`This request requires ${requiredNotice} notice. Consider marking as emergency if urgent.`);
        }

        // Weekend warnings
        const isWeekend = (date: Date) => date.getDay() === 0 || date.getDay() === 6;
        if (isWeekend(startDate) || isWeekend(endDate)) {
          newWarnings.push('Weekend leave requests may incur additional penalties.');
        }
      }
    }

    // Reason validation
    if (!formData.reason.trim()) {
      newErrors.reason = 'Reason is required';
    } else if (formData.reason.trim().length < 10) {
      newErrors.reason = 'Reason must be at least 10 characters';
    }

    setErrors(newErrors);
    setWarnings(newWarnings);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (Object.keys(errors).length > 0) {
      return;
    }

    try {
      const success = await onSubmit(formData);
      if (success) {
        // Reset form
        setFormData({
          type: 'ONE_DAY',
          dateFrom: '',
          dateTo: '',
          reason: '',
          isEmergency: false
        });
      }
    } catch (error) {
      console.error('Failed to submit leave request:', error);
    }
  };

  const handleChange = (field: keyof LeaveRequestData, value: any) => {
    setFormData((prev: LeaveRequestData) => ({ ...prev, [field]: value }));
  };

  const getTomorrowDate = () => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    return tomorrow.toISOString().split('T')[0];
  };

  return (
    <form onSubmit={handleSubmit} className={`space-y-6 ${className}`}>
      {/* Leave Type Selection */}
      <div>
        <label className="block text-sm font-medium text-gray-700 mb-3">
          Leave Type
        </label>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {leavePolicies.map((policy) => (
            <div
              key={policy.type}
              className={`relative rounded-lg border p-4 cursor-pointer transition-colors ${
                formData.type === policy.type
                  ? 'border-blue-500 bg-blue-50'
                  : 'border-gray-300 hover:border-gray-400'
              }`}
              onClick={() => handleChange('type', policy.type)}
            >
              <input
                type="radio"
                name="leaveType"
                value={policy.type}
                checked={formData.type === policy.type}
                onChange={() => handleChange('type', policy.type)}
                className="sr-only"
                aria-describedby={`${policy.type}-description`}
              />
              <div>
                <h4 className="text-sm font-medium text-gray-900 mb-1">
                  {policy.name}
                </h4>
                <p id={`${policy.type}-description`} className="text-xs text-gray-600 mb-2">
                  {policy.description}
                </p>
                <div className="text-xs text-gray-500">
                  <div>Notice: {policy.minNoticeHours >= 24 ? `${policy.minNoticeHours / 24} day(s)` : `${policy.minNoticeHours}h`}</div>
                  {policy.weeklyLimit && <div>Weekly limit: {policy.weeklyLimit}</div>}
                  {policy.monthlyLimit && <div>Monthly limit: {policy.monthlyLimit}</div>}
                </div>
              </div>
              {formData.type === policy.type && (
                <div className="absolute top-2 right-2">
                  <svg className="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                </div>
              )}
            </div>
          ))}
        </div>
      </div>

      {/* Date Range */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label htmlFor="dateFrom" className="block text-sm font-medium text-gray-700 mb-2">
            Start Date
          </label>
          <input
            type="date"
            id="dateFrom"
            value={formData.dateFrom}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleChange('dateFrom', e.target.value)}
            min={formData.isEmergency ? undefined : getTomorrowDate()}
            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
              errors.dateFrom ? 'border-red-300' : 'border-gray-300'
            }`}
            aria-describedby={errors.dateFrom ? 'dateFrom-error' : undefined}
          />
          {errors.dateFrom && (
            <p id="dateFrom-error" className="mt-1 text-sm text-red-600" role="alert">
              {errors.dateFrom}
            </p>
          )}
        </div>

        <div>
          <label htmlFor="dateTo" className="block text-sm font-medium text-gray-700 mb-2">
            End Date
          </label>
          <input
            type="date"
            id="dateTo"
            value={formData.dateTo}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleChange('dateTo', e.target.value)}
            min={formData.dateFrom || getTomorrowDate()}
            disabled={formData.type === 'SHORT' || formData.type === 'ONE_DAY'}
            className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
              errors.dateTo ? 'border-red-300' : 'border-gray-300'
            } ${
              formData.type === 'SHORT' || formData.type === 'ONE_DAY' ? 'bg-gray-100' : ''
            }`}
            aria-describedby={errors.dateTo ? 'dateTo-error' : undefined}
          />
          {errors.dateTo && (
            <p id="dateTo-error" className="mt-1 text-sm text-red-600" role="alert">
              {errors.dateTo}
            </p>
          )}
        </div>
      </div>

      {/* Emergency Checkbox */}
      <div className="flex items-start">
        <input
          type="checkbox"
          id="isEmergency"
          checked={formData.isEmergency}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleChange('isEmergency', e.target.checked)}
          className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1"
        />
        <label htmlFor="isEmergency" className="ml-3 text-sm text-gray-700">
          <span className="font-medium">Emergency Leave</span>
          <p className="text-gray-500 mt-1">
            Check this if this is an urgent request that cannot meet the standard notice requirements.
            Emergency leaves may be subject to additional review.
          </p>
        </label>
      </div>

      {/* Reason */}
      <div>
        <label htmlFor="reason" className="block text-sm font-medium text-gray-700 mb-2">
          Reason for Leave
        </label>
        <textarea
          id="reason"
          value={formData.reason}
          onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => handleChange('reason', e.target.value)}
          rows={4}
          placeholder="Please provide a detailed reason for your leave request..."
          className={`w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent ${
            errors.reason ? 'border-red-300' : 'border-gray-300'
          }`}
          aria-describedby={errors.reason ? 'reason-error' : 'reason-help'}
        />
        {errors.reason ? (
          <p id="reason-error" className="mt-1 text-sm text-red-600" role="alert">
            {errors.reason}
          </p>
        ) : (
          <p id="reason-help" className="mt-1 text-sm text-gray-500">
            Minimum 10 characters required. Be specific about your situation.
          </p>
        )}
      </div>

      {/* Warnings */}
      {warnings.length > 0 && (
        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4" role="alert">
          <div className="flex">
            <svg className="w-5 h-5 text-yellow-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            <div>
              <h4 className="text-sm font-medium text-yellow-800 mb-1">Please Note:</h4>
              <ul className="text-sm text-yellow-700 space-y-1">
                {warnings.map((warning: string, index: number) => (
                  <li key={index}>• {warning}</li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      )}

      {/* Policy Information */}
      {selectedPolicy && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 className="text-sm font-medium text-blue-800 mb-2">
            {selectedPolicy.name} Policy
          </h4>
          <div className="text-sm text-blue-700 space-y-1">
            <p>• Minimum notice required: {selectedPolicy.minNoticeHours >= 24 ? `${selectedPolicy.minNoticeHours / 24} day(s)` : `${selectedPolicy.minNoticeHours} hours`}</p>
            {selectedPolicy.weeklyLimit && <p>• Weekly limit: {selectedPolicy.weeklyLimit} {selectedPolicy.type === 'SHORT' ? 'hours' : 'days'}</p>}
            {selectedPolicy.monthlyLimit && <p>• Monthly limit: {selectedPolicy.monthlyLimit} {selectedPolicy.type === 'SHORT' ? 'hours' : 'days'}</p>}
            <p>• All leave requests require manager approval</p>
          </div>
        </div>
      )}

      {/* Submit Button */}
      <div className="flex justify-end">
        <button
          type="submit"
          disabled={Object.keys(errors).length > 0 || isLoading}
          className="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-semibold"
        >
          {isLoading ? (
            <div className="flex items-center">
              <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              Submitting...
            </div>
          ) : (
            'Submit Leave Request'
          )}
        </button>
      </div>
    </form>
  );
}
