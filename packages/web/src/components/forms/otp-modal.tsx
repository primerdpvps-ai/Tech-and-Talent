'use client';

import React, { useState, useEffect } from 'react';
import { Modal } from '@/components/ui/modal';
import { OTPInput } from '@/components/ui/otp-input';
import { CountdownTimer } from '@/components/ui/countdown-timer';

interface OTPModalProps {
  isOpen: boolean;
  onClose: () => void;
  onVerify: (otp: string) => Promise<boolean>;
  onResend: () => Promise<boolean>;
  title: string;
  description: string;
  contactInfo: string; // email or phone number
  type: 'email' | 'sms';
  purpose: string;
}

export function OTPModal({
  isOpen,
  onClose,
  onVerify,
  onResend,
  title,
  description,
  contactInfo,
  type,
  purpose
}: OTPModalProps) {
  const [otp, setOtp] = useState('');
  const [isVerifying, setIsVerifying] = useState(false);
  const [error, setError] = useState('');
  const [canResend, setCanResend] = useState(false);
  const [resendCount, setResendCount] = useState(0);
  const [isResending, setIsResending] = useState(false);

  const maxResends = 3;
  const cooldownSeconds = 60;

  useEffect(() => {
    if (isOpen) {
      setOtp('');
      setError('');
      setCanResend(false);
      setResendCount(0);
    }
  }, [isOpen]);

  const handleOTPComplete = async (otpValue: string) => {
    setOtp(otpValue);
    setError('');
    setIsVerifying(true);

    try {
      const success = await onVerify(otpValue);
      if (success) {
        onClose();
      } else {
        setError('Invalid verification code. Please try again.');
        setOtp('');
      }
    } catch (err) {
      setError('Verification failed. Please try again.');
      setOtp('');
    } finally {
      setIsVerifying(false);
    }
  };

  const handleResend = async () => {
    if (!canResend || resendCount >= maxResends || isResending) return;

    setIsResending(true);
    setError('');

    try {
      const success = await onResend();
      if (success) {
        setResendCount((prev: number) => prev + 1);
        setCanResend(false);
        setOtp('');
      } else {
        setError('Failed to resend code. Please try again.');
      }
    } catch (err) {
      setError('Failed to resend code. Please try again.');
    } finally {
      setIsResending(false);
    }
  };

  const handleCountdownComplete = () => {
    setCanResend(true);
  };

  const getContactDisplay = () => {
    if (type === 'email') {
      const [local, domain] = contactInfo.split('@');
      return `${local.slice(0, 2)}***@${domain}`;
    } else {
      return `***-***-${contactInfo.slice(-4)}`;
    }
  };

  const getIcon = () => {
    if (type === 'email') {
      return (
        <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
      );
    } else {
      return (
        <svg className="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
        </svg>
      );
    }
  };

  const modalContent = (
    <div className="text-center">
        {/* Icon */}
        <div className="mx-auto flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-6">
          {getIcon()}
        </div>

        {/* Description */}
        <p className="text-gray-600 mb-2">{description}</p>
        <p className="text-sm text-gray-500 mb-8">
          We sent a 6-digit code to <span className="font-medium">{getContactDisplay()}</span>
        </p>

        {/* OTP Input */}
        <div className="mb-6">
          <OTPInput
            length={6}
            onComplete={handleOTPComplete}
            disabled={isVerifying}
            error={!!error}
          />
        </div>

        {/* Error Message */}
        {error && (
          <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p className="text-sm text-red-600">{error}</p>
          </div>
        )}

        {/* Loading State */}
        {isVerifying && (
          <div className="mb-4 flex items-center justify-center">
            <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-2"></div>
            <span className="text-sm text-gray-600">Verifying...</span>
          </div>
        )}

        {/* Resend Section */}
        <div className="text-center">
          <p className="text-sm text-gray-600 mb-2">
            Didn't receive the code?
          </p>
          
          {canResend && resendCount < maxResends ? (
            <button
              onClick={handleResend}
              disabled={isResending}
              className="text-blue-600 hover:text-blue-700 font-medium text-sm disabled:opacity-50"
            >
              {isResending ? 'Sending...' : 'Resend Code'}
            </button>
          ) : (
            <div className="text-sm text-gray-500">
              {resendCount >= maxResends ? (
                <span>Maximum resend attempts reached</span>
              ) : (
                <span>
                  Resend in <CountdownTimer initialSeconds={cooldownSeconds} onComplete={handleCountdownComplete} format="ss" /> seconds
                </span>
              )}
            </div>
          )}

          {resendCount > 0 && resendCount < maxResends && (
            <p className="text-xs text-gray-400 mt-1">
              {resendCount}/{maxResends} attempts used
            </p>
          )}
        </div>

        {/* Cancel Button */}
        <div className="mt-8 pt-4 border-t border-gray-200">
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700 text-sm font-medium"
          >
            Cancel
          </button>
        </div>
      </div>
  );

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={title} children={modalContent} />
  );
}
