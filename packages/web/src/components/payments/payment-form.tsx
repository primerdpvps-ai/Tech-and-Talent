'use client';

import React, { useState } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import {
  MDBCard,
  MDBCardBody,
  MDBCardHeader,
  MDBContainer,
  MDBRow,
  MDBCol,
  MDBInput,
  MDBBtn,
  MDBIcon,
  MDBSpinner,
  MDBAlert,
  MDBTabs,
  MDBTabsItem,
  MDBTabsLink,
  MDBTabsContent,
  MDBTabsPane
} from 'mdb-react-ui-kit';

interface PaymentFormProps {
  amount: number;
  currency?: string;
  description?: string;
  onSuccess?: (paymentId: string) => void;
  onError?: (error: string) => void;
}

export function PaymentForm({ 
  amount, 
  currency = 'USD', 
  description = 'Payment',
  onSuccess,
  onError 
}: PaymentFormProps) {
  const [activeTab, setActiveTab] = useState('stripe');
  const [isProcessing, setIsProcessing] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const [cardData, setCardData] = useState({
    cardNumber: '',
    expiryDate: '',
    cvv: '',
    cardholderName: ''
  });

  const [billingData, setBillingData] = useState({
    email: '',
    firstName: '',
    lastName: '',
    address: '',
    city: '',
    zipCode: '',
    country: 'US'
  });

  const formatCardNumber = (value: string) => {
    const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    const matches = v.match(/\d{4,16}/g);
    const match = matches && matches[0] || '';
    const parts = [];
    for (let i = 0, len = match.length; i < len; i += 4) {
      parts.push(match.substring(i, i + 4));
    }
    if (parts.length) {
      return parts.join(' ');
    } else {
      return v;
    }
  };

  const formatExpiryDate = (value: string) => {
    const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
    if (v.length >= 2) {
      return v.substring(0, 2) + '/' + v.substring(2, 4);
    }
    return v;
  };

  const handleCardInputChange = (field: string, value: string) => {
    let formattedValue = value;
    
    if (field === 'cardNumber') {
      formattedValue = formatCardNumber(value);
    } else if (field === 'expiryDate') {
      formattedValue = formatExpiryDate(value);
    } else if (field === 'cvv') {
      formattedValue = value.replace(/[^0-9]/g, '').substring(0, 4);
    }

    setCardData(prev => ({ ...prev, [field]: formattedValue }));
  };

  const processPayment = async () => {
    setIsProcessing(true);
    setError('');
    setSuccess('');

    try {
      const response = await fetch('/api/payments/create-session', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          amount,
          currency,
          description,
          paymentMethod: activeTab,
          metadata: {
            billingData,
            cardData: activeTab === 'stripe' ? cardData : undefined
          }
        })
      });

      const result = await response.json();

      if (result.success) {
        if (activeTab === 'stripe' && result.checkoutUrl) {
          // Redirect to Stripe Checkout
          window.location.href = result.checkoutUrl;
        } else {
          setSuccess('Payment processed successfully!');
          onSuccess?.(result.paymentId);
        }
      } else {
        throw new Error(result.error || 'Payment failed');
      }
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Payment processing failed';
      setError(errorMessage);
      onError?.(errorMessage);
    } finally {
      setIsProcessing(false);
    }
  };

  const isFormValid = () => {
    if (!billingData.email || !billingData.firstName || !billingData.lastName) {
      return false;
    }

    if (activeTab === 'stripe') {
      return cardData.cardNumber.replace(/\s/g, '').length >= 13 &&
             cardData.expiryDate.length === 5 &&
             cardData.cvv.length >= 3 &&
             cardData.cardholderName.length > 0;
    }

    return true;
  };

  const cardVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { opacity: 1, y: 0 },
    exit: { opacity: 0, y: -20 }
  };

  return (
    <MDBContainer className="py-4">
      <motion.div
        initial="hidden"
        animate="visible"
        variants={cardVariants}
        transition={{ duration: 0.5 }}
      >
        <MDBRow className="justify-content-center">
          <MDBCol lg="8" xl="6">
            <MDBCard className="shadow-lg">
              <MDBCardHeader className="bg-primary text-white text-center py-4">
                <h3 className="mb-0">
                  <MDBIcon icon="credit-card" className="me-2" />
                  Secure Payment
                </h3>
                <p className="mb-0 mt-2">
                  {description} - {new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: currency
                  }).format(amount)}
                </p>
              </MDBCardHeader>

              <MDBCardBody className="p-4">
                {error && (
                  <motion.div
                    initial={{ opacity: 0, scale: 0.9 }}
                    animate={{ opacity: 1, scale: 1 }}
                    className="mb-4"
                  >
                    <MDBAlert color="danger">
                      <MDBIcon icon="exclamation-triangle" className="me-2" />
                      {error}
                    </MDBAlert>
                  </motion.div>
                )}

                {success && (
                  <motion.div
                    initial={{ opacity: 0, scale: 0.9 }}
                    animate={{ opacity: 1, scale: 1 }}
                    className="mb-4"
                  >
                    <MDBAlert color="success">
                      <MDBIcon icon="check-circle" className="me-2" />
                      {success}
                    </MDBAlert>
                  </motion.div>
                )}

                {/* Payment Method Tabs */}
                <MDBTabs className="mb-4">
                  <MDBTabsItem>
                    <MDBTabsLink
                      onClick={() => setActiveTab('stripe')}
                      active={activeTab === 'stripe'}
                      className="d-flex align-items-center"
                    >
                      <MDBIcon icon="credit-card" className="me-2" />
                      <span className="d-none d-sm-inline">Credit Card</span>
                    </MDBTabsLink>
                  </MDBTabsItem>
                  <MDBTabsItem>
                    <MDBTabsLink
                      onClick={() => setActiveTab('paypal')}
                      active={activeTab === 'paypal'}
                      className="d-flex align-items-center"
                    >
                      <MDBIcon fab icon="paypal" className="me-2" />
                      <span className="d-none d-sm-inline">PayPal</span>
                    </MDBTabsLink>
                  </MDBTabsItem>
                  <MDBTabsItem>
                    <MDBTabsLink
                      onClick={() => setActiveTab('googlepay')}
                      active={activeTab === 'googlepay'}
                      className="d-flex align-items-center"
                    >
                      <MDBIcon fab icon="google-pay" className="me-2" />
                      <span className="d-none d-sm-inline">Google Pay</span>
                    </MDBTabsLink>
                  </MDBTabsItem>
                </MDBTabs>

                <MDBTabsContent>
                  {/* Stripe Credit Card Form */}
                  <MDBTabsPane show={activeTab === 'stripe'}>
                    <AnimatePresence mode="wait">
                      <motion.div
                        key="stripe"
                        variants={cardVariants}
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        transition={{ duration: 0.3 }}
                      >
                        <MDBRow>
                          <MDBCol md="12" className="mb-3">
                            <MDBInput
                              label="Cardholder Name"
                              type="text"
                              value={cardData.cardholderName}
                              onChange={(e) => handleCardInputChange('cardholderName', e.target.value)}
                              required
                            />
                          </MDBCol>
                          <MDBCol md="12" className="mb-3">
                            <MDBInput
                              label="Card Number"
                              type="text"
                              value={cardData.cardNumber}
                              onChange={(e) => handleCardInputChange('cardNumber', e.target.value)}
                              maxLength={19}
                              required
                            />
                          </MDBCol>
                          <MDBCol md="6" className="mb-3">
                            <MDBInput
                              label="MM/YY"
                              type="text"
                              value={cardData.expiryDate}
                              onChange={(e) => handleCardInputChange('expiryDate', e.target.value)}
                              maxLength={5}
                              required
                            />
                          </MDBCol>
                          <MDBCol md="6" className="mb-3">
                            <MDBInput
                              label="CVV"
                              type="text"
                              value={cardData.cvv}
                              onChange={(e) => handleCardInputChange('cvv', e.target.value)}
                              maxLength={4}
                              required
                            />
                          </MDBCol>
                        </MDBRow>
                      </motion.div>
                    </AnimatePresence>
                  </MDBTabsPane>

                  {/* PayPal */}
                  <MDBTabsPane show={activeTab === 'paypal'}>
                    <motion.div
                      variants={cardVariants}
                      initial="hidden"
                      animate="visible"
                      transition={{ duration: 0.3 }}
                      className="text-center py-4"
                    >
                      <MDBIcon fab icon="paypal" size="3x" className="text-primary mb-3" />
                      <p>You will be redirected to PayPal to complete your payment securely.</p>
                    </motion.div>
                  </MDBTabsPane>

                  {/* Google Pay */}
                  <MDBTabsPane show={activeTab === 'googlepay'}>
                    <motion.div
                      variants={cardVariants}
                      initial="hidden"
                      animate="visible"
                      transition={{ duration: 0.3 }}
                      className="text-center py-4"
                    >
                      <MDBIcon fab icon="google-pay" size="3x" className="text-success mb-3" />
                      <p>Pay quickly and securely with Google Pay.</p>
                    </motion.div>
                  </MDBTabsPane>
                </MDBTabsContent>

                {/* Billing Information */}
                <div className="mt-4">
                  <h5 className="mb-3">
                    <MDBIcon icon="user" className="me-2" />
                    Billing Information
                  </h5>
                  <MDBRow>
                    <MDBCol md="6" className="mb-3">
                      <MDBInput
                        label="First Name"
                        type="text"
                        value={billingData.firstName}
                        onChange={(e) => setBillingData(prev => ({ ...prev, firstName: e.target.value }))}
                        required
                      />
                    </MDBCol>
                    <MDBCol md="6" className="mb-3">
                      <MDBInput
                        label="Last Name"
                        type="text"
                        value={billingData.lastName}
                        onChange={(e) => setBillingData(prev => ({ ...prev, lastName: e.target.value }))}
                        required
                      />
                    </MDBCol>
                    <MDBCol md="12" className="mb-3">
                      <MDBInput
                        label="Email Address"
                        type="email"
                        value={billingData.email}
                        onChange={(e) => setBillingData(prev => ({ ...prev, email: e.target.value }))}
                        required
                      />
                    </MDBCol>
                    <MDBCol md="12" className="mb-3">
                      <MDBInput
                        label="Address"
                        type="text"
                        value={billingData.address}
                        onChange={(e) => setBillingData(prev => ({ ...prev, address: e.target.value }))}
                      />
                    </MDBCol>
                    <MDBCol md="6" className="mb-3">
                      <MDBInput
                        label="City"
                        type="text"
                        value={billingData.city}
                        onChange={(e) => setBillingData(prev => ({ ...prev, city: e.target.value }))}
                      />
                    </MDBCol>
                    <MDBCol md="6" className="mb-3">
                      <MDBInput
                        label="ZIP Code"
                        type="text"
                        value={billingData.zipCode}
                        onChange={(e) => setBillingData(prev => ({ ...prev, zipCode: e.target.value }))}
                      />
                    </MDBCol>
                  </MDBRow>
                </div>

                {/* Security Notice */}
                <div className="bg-light p-3 rounded mb-4">
                  <div className="d-flex align-items-center">
                    <MDBIcon icon="shield-alt" className="text-success me-2" />
                    <small className="text-muted">
                      Your payment information is encrypted and secure. We never store your card details.
                    </small>
                  </div>
                </div>

                {/* Submit Button */}
                <motion.div
                  whileHover={{ scale: 1.02 }}
                  whileTap={{ scale: 0.98 }}
                >
                  <MDBBtn
                    color="primary"
                    size="lg"
                    block
                    onClick={processPayment}
                    disabled={!isFormValid() || isProcessing}
                    className="mt-3"
                  >
                    {isProcessing ? (
                      <>
                        <MDBSpinner size="sm" className="me-2" />
                        Processing...
                      </>
                    ) : (
                      <>
                        <MDBIcon icon="lock" className="me-2" />
                        Pay {new Intl.NumberFormat('en-US', {
                          style: 'currency',
                          currency: currency
                        }).format(amount)}
                      </>
                    )}
                  </MDBBtn>
                </motion.div>
              </MDBCardBody>
            </MDBCard>
          </MDBCol>
        </MDBRow>
      </motion.div>
    </MDBContainer>
  );
}
