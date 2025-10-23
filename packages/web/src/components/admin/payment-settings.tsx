'use client';

import React, { useState } from 'react';
import { motion } from 'framer-motion';
import {
  MDBCard,
  MDBCardBody,
  MDBCardHeader,
  MDBContainer,
  MDBRow,
  MDBCol,
  MDBInput,
  MDBBtn,
  MDBSwitch,
  MDBTabs,
  MDBTabsItem,
  MDBTabsLink,
  MDBTabsContent,
  MDBTabsPane,
  MDBIcon,
  MDBAlert
} from 'mdb-react-ui-kit';

interface PaymentGateway {
  id: string;
  name: string;
  enabled: boolean;
  icon: string;
  config: Record<string, any>;
}

export function PaymentSettings() {
  const [activeTab, setActiveTab] = useState('stripe');
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'success' | 'error'>('idle');
  
  const [gateways, setGateways] = useState<PaymentGateway[]>([
    {
      id: 'stripe',
      name: 'Stripe',
      enabled: true,
      icon: 'credit-card',
      config: {
        publishableKey: '',
        secretKey: '',
        webhookSecret: '',
        currency: 'USD'
      }
    },
    {
      id: 'paypal',
      name: 'PayPal',
      enabled: false,
      icon: 'paypal',
      config: {
        clientId: '',
        clientSecret: '',
        environment: 'sandbox'
      }
    },
    {
      id: 'googlepay',
      name: 'Google Pay',
      enabled: false,
      icon: 'google',
      config: {
        merchantId: '',
        environment: 'TEST'
      }
    }
  ]);

  const updateGateway = (id: string, updates: Partial<PaymentGateway>) => {
    setGateways(prev => prev.map(gateway => 
      gateway.id === id ? { ...gateway, ...updates } : gateway
    ));
  };

  const updateGatewayConfig = (id: string, key: string, value: any) => {
    setGateways(prev => prev.map(gateway => 
      gateway.id === id 
        ? { ...gateway, config: { ...gateway.config, [key]: value } }
        : gateway
    ));
  };

  const saveSettings = async () => {
    setSaveStatus('saving');
    try {
      const response = await fetch('/api/admin/payment-settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ gateways })
      });

      if (response.ok) {
        setSaveStatus('success');
        setTimeout(() => setSaveStatus('idle'), 3000);
      } else {
        setSaveStatus('error');
      }
    } catch (error) {
      setSaveStatus('error');
    }
  };

  const testConnection = async (gatewayId: string) => {
    try {
      const response = await fetch(`/api/admin/test-payment-gateway`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ gatewayId, config: gateways.find(g => g.id === gatewayId)?.config })
      });

      const result = await response.json();
      alert(result.success ? 'Connection successful!' : `Connection failed: ${result.error}`);
    } catch (error) {
      alert('Connection test failed');
    }
  };

  return (
    <MDBContainer fluid className="py-4">
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
      >
        <MDBRow>
          <MDBCol>
            <MDBCard>
              <MDBCardHeader className="bg-primary text-white">
                <h4 className="mb-0">
                  <MDBIcon icon="credit-card" className="me-2" />
                  Payment Gateway Settings
                </h4>
              </MDBCardHeader>
              <MDBCardBody>
                {saveStatus === 'success' && (
                  <MDBAlert color="success" className="mb-3">
                    Settings saved successfully!
                  </MDBAlert>
                )}
                {saveStatus === 'error' && (
                  <MDBAlert color="danger" className="mb-3">
                    Failed to save settings. Please try again.
                  </MDBAlert>
                )}

                <MDBTabs className="mb-4">
                  {gateways.map((gateway) => (
                    <MDBTabsItem key={gateway.id}>
                      <MDBTabsLink
                        onClick={() => setActiveTab(gateway.id)}
                        active={activeTab === gateway.id}
                      >
                        <MDBIcon icon={gateway.icon} className="me-2" />
                        {gateway.name}
                        {gateway.enabled && (
                          <span className="badge bg-success ms-2">Active</span>
                        )}
                      </MDBTabsLink>
                    </MDBTabsItem>
                  ))}
                </MDBTabs>

                <MDBTabsContent>
                  {gateways.map((gateway) => (
                    <MDBTabsPane key={gateway.id} show={activeTab === gateway.id}>
                      <motion.div
                        initial={{ opacity: 0, x: 20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.3 }}
                      >
                        <MDBRow className="mb-4">
                          <MDBCol md="6">
                            <MDBSwitch
                              id={`${gateway.id}-enabled`}
                              label={`Enable ${gateway.name}`}
                              checked={gateway.enabled}
                              onChange={(e) => updateGateway(gateway.id, { enabled: e.target.checked })}
                            />
                          </MDBCol>
                          <MDBCol md="6" className="text-end">
                            <MDBBtn
                              color="info"
                              size="sm"
                              onClick={() => testConnection(gateway.id)}
                              disabled={!gateway.enabled}
                            >
                              <MDBIcon icon="plug" className="me-1" />
                              Test Connection
                            </MDBBtn>
                          </MDBCol>
                        </MDBRow>

                        {/* Stripe Configuration */}
                        {gateway.id === 'stripe' && (
                          <MDBRow>
                            <MDBCol md="6" className="mb-3">
                              <MDBInput
                                label="Publishable Key"
                                type="text"
                                value={gateway.config.publishableKey}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'publishableKey', e.target.value)}
                                disabled={!gateway.enabled}
                              />
                            </MDBCol>
                            <MDBCol md="6" className="mb-3">
                              <MDBInput
                                label="Secret Key"
                                type="password"
                                value={gateway.config.secretKey}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'secretKey', e.target.value)}
                                disabled={!gateway.enabled}
                              />
                            </MDBCol>
                            <MDBCol md="6" className="mb-3">
                              <MDBInput
                                label="Webhook Secret"
                                type="password"
                                value={gateway.config.webhookSecret}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'webhookSecret', e.target.value)}
                                disabled={!gateway.enabled}
                              />
                            </MDBCol>
                            <MDBCol md="6" className="mb-3">
                              <select
                                className="form-select"
                                value={gateway.config.currency}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'currency', e.target.value)}
                                disabled={!gateway.enabled}
                              >
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="CAD">CAD - Canadian Dollar</option>
                              </select>
                            </MDBCol>
                          </MDBRow>
                        )}

                        {/* PayPal Configuration */}
                        {gateway.id === 'paypal' && (
                          <MDBRow>
                            <MDBCol md="6" className="mb-3">
                              <MDBInput
                                label="Client ID"
                                type="text"
                                value={gateway.config.clientId}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'clientId', e.target.value)}
                                disabled={!gateway.enabled}
                              />
                            </MDBCol>
                            <MDBCol md="6" className="mb-3">
                              <MDBInput
                                label="Client Secret"
                                type="password"
                                value={gateway.config.clientSecret}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'clientSecret', e.target.value)}
                                disabled={!gateway.enabled}
                              />
                            </MDBCol>
                            <MDBCol md="6" className="mb-3">
                              <select
                                className="form-select"
                                value={gateway.config.environment}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'environment', e.target.value)}
                                disabled={!gateway.enabled}
                              >
                                <option value="sandbox">Sandbox</option>
                                <option value="live">Live</option>
                              </select>
                            </MDBCol>
                          </MDBRow>
                        )}

                        {/* Google Pay Configuration */}
                        {gateway.id === 'googlepay' && (
                          <MDBRow>
                            <MDBCol md="6" className="mb-3">
                              <MDBInput
                                label="Merchant ID"
                                type="text"
                                value={gateway.config.merchantId}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'merchantId', e.target.value)}
                                disabled={!gateway.enabled}
                              />
                            </MDBCol>
                            <MDBCol md="6" className="mb-3">
                              <select
                                className="form-select"
                                value={gateway.config.environment}
                                onChange={(e) => updateGatewayConfig(gateway.id, 'environment', e.target.value)}
                                disabled={!gateway.enabled}
                              >
                                <option value="TEST">Test</option>
                                <option value="PRODUCTION">Production</option>
                              </select>
                            </MDBCol>
                          </MDBRow>
                        )}
                      </motion.div>
                    </MDBTabsPane>
                  ))}
                </MDBTabsContent>

                <div className="text-end mt-4">
                  <MDBBtn
                    color="primary"
                    onClick={saveSettings}
                    disabled={saveStatus === 'saving'}
                  >
                    {saveStatus === 'saving' ? (
                      <>
                        <span className="spinner-border spinner-border-sm me-2" />
                        Saving...
                      </>
                    ) : (
                      <>
                        <MDBIcon icon="save" className="me-2" />
                        Save Settings
                      </>
                    )}
                  </MDBBtn>
                </div>
              </MDBCardBody>
            </MDBCard>
          </MDBCol>
        </MDBRow>
      </motion.div>
    </MDBContainer>
  );
}
