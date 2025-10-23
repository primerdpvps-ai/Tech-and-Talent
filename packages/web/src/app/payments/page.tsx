'use client';

import React from 'react';
import { PaymentForm } from '../../components/payments/payment-form';

export default function PaymentsPage() {
  const handlePaymentSuccess = (paymentId: string) => {
    console.log('Payment successful:', paymentId);
    // Redirect to success page or show success message
  };

  const handlePaymentError = (error: string) => {
    console.error('Payment error:', error);
    // Show error message to user
  };

  return (
    <div className="min-vh-100 d-flex align-items-center">
      <PaymentForm
        amount={99.99}
        currency="USD"
        description="TTS PMS Subscription"
        onSuccess={handlePaymentSuccess}
        onError={handlePaymentError}
      />
    </div>
  );
}
