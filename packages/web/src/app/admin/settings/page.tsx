'use client';

import { useState, useEffect } from 'react';
import { Modal } from '@/components/ui/modal';

interface SystemSettings {
  rates: {
    baseHourlyRate: number;
    managerHourlyRate: number;
    weekendMultiplier: number;
    overtimeMultiplier: number;
  };
  bonuses: {
    perfectAttendanceWeekly: number;
    perfectAttendanceMonthly: number;
    performanceBonus90: number;
    performanceBonus95: number;
    referralBonus: number;
  };
  penalties: {
    lateArrival15min: number;
    lateArrival30min: number;
    lateArrival60min: number;
    unexcusedAbsence: number;
    weekendLeave: number;
    insufficientNotice: number;
  };
  noticeWindows: {
    shortLeaveHours: number;
    oneDayLeaveHours: number;
    longLeaveHours: number;
  };
  operational: {
    workingHoursStart: string;
    workingHoursEnd: string;
    workingDays: string[];
    payrollDay: string;
    maxConsecutiveWorkDays: number;
  };
  integrations: {
    otpProvider: 'TWILIO' | 'AWS_SNS' | 'CUSTOM';
    emailProvider: 'SENDGRID' | 'AWS_SES' | 'SMTP';
    s3Bucket: string;
    s3Region: string;
  };
  company: {
    name: string;
    website: string;
    contactEmail: string;
    address: string;
  };
  policy: {
    effectiveDate: string;
    updatedDate: string;
  };
  payroll: {
    payslipNote: string;
  };
}

interface Template {
  id: string;
  name: string;
  type: 'EMAIL' | 'SMS';
  subject?: string;
  content: string;
  variables: string[];
}

interface LegalDocument {
  id: string;
  type: 'TERMS_CONDITIONS' | 'PRIVACY_POLICY' | 'EMPLOYEE_HANDBOOK';
  title: string;
  content: string;
  effectiveDate: string;
  version: string;
  published?: boolean;
}

const INITIAL_SETTINGS: SystemSettings = {
  rates: {
    baseHourlyRate: 15,
    managerHourlyRate: 25,
    weekendMultiplier: 1.5,
    overtimeMultiplier: 1.5
  },
  bonuses: {
    perfectAttendanceWeekly: 50,
    perfectAttendanceMonthly: 200,
    performanceBonus90: 100,
    performanceBonus95: 150,
    referralBonus: 500
  },
  penalties: {
    lateArrival15min: 5,
    lateArrival30min: 10,
    lateArrival60min: 25,
    unexcusedAbsence: 50,
    weekendLeave: 15,
    insufficientNotice: 25
  },
  noticeWindows: {
    shortLeaveHours: 2,
    oneDayLeaveHours: 24,
    longLeaveHours: 168
  },
  operational: {
    workingHoursStart: '09:00',
    workingHoursEnd: '17:00',
    workingDays: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
    payrollDay: 'Friday',
    maxConsecutiveWorkDays: 6
  },
  integrations: {
    otpProvider: 'TWILIO',
    emailProvider: 'SENDGRID',
    s3Bucket: 'tts-pms-storage',
    s3Region: 'us-east-1'
  },
  company: {
    name: 'Tech & Talent Solutions Ltd.',
    website: '',
    contactEmail: 'info@tts.com.pk',
    address: ''
  },
  policy: {
    effectiveDate: new Date().toISOString().split('T')[0],
    updatedDate: new Date().toISOString().split('T')[0]
  },
  payroll: {
    payslipNote: 'This payslip is auto-generated. For questions, contact HR.'
  }
};

export default function AdminSettingsPage() {
  const [settings, setSettings] = useState<SystemSettings | null>(null);
  const [templates, setTemplates] = useState<Template[]>([]);
  const [legalDocs, setLegalDocs] = useState<LegalDocument[]>([]);
  const [activeTab, setActiveTab] = useState<'rates' | 'templates' | 'legal' | 'integrations' | 'operational'>('rates');
  const [isLoading, setIsLoading] = useState(true);
  const [isSaving, setIsSaving] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState<Template | null>(null);
  const [editingLegal, setEditingLegal] = useState<LegalDocument | null>(null);
  const [showSecrets, setShowSecrets] = useState(false);

  useEffect(() => {
    fetchSettings();
    fetchTemplates();
    fetchLegalDocuments();
  }, []);

  const fetchSettings = async () => {
    try {
      setIsLoading(true);
      // Mock data
      const mockSettings: SystemSettings = {
        rates: {
          baseHourlyRate: 15,
          managerHourlyRate: 25,
          weekendMultiplier: 1.5,
          overtimeMultiplier: 1.5
        },
        bonuses: {
          perfectAttendanceWeekly: 50,
          perfectAttendanceMonthly: 200,
          performanceBonus90: 100,
          performanceBonus95: 150,
          referralBonus: 500
        },
        penalties: {
          lateArrival15min: 5,
          lateArrival30min: 10,
          lateArrival60min: 25,
          unexcusedAbsence: 50,
          weekendLeave: 15,
          insufficientNotice: 25
        },
        noticeWindows: {
          shortLeaveHours: 2,
          oneDayLeaveHours: 24,
          longLeaveHours: 168
        },
        operational: {
          workingHoursStart: '09:00',
          workingHoursEnd: '17:00',
          workingDays: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
          payrollDay: 'Friday',
          maxConsecutiveWorkDays: 6
        },
        integrations: {
          otpProvider: 'TWILIO',
          emailProvider: 'SENDGRID',
          s3Bucket: 'tts-pms-storage',
          s3Region: 'us-east-1'
        }
      };
      setSettings(mockSettings);
    } catch (error) {
      console.error('Failed to fetch settings:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const fetchTemplates = async () => {
    const mockTemplates: Template[] = [
      {
        id: '1',
        name: 'Welcome Email',
        type: 'EMAIL',
        subject: 'Welcome to TTS PMS - {{employeeName}}',
        content: 'Dear {{employeeName}},\n\nWelcome to our team! Your login credentials are:\nUsername: {{username}}\nPassword: {{password}}\n\nBest regards,\nTTS PMS Team',
        variables: ['employeeName', 'username', 'password']
      },
      {
        id: '2',
        name: 'OTP Verification',
        type: 'SMS',
        content: 'Your TTS PMS verification code is: {{otpCode}}. Valid for 5 minutes.',
        variables: ['otpCode']
      }
    ];
    setTemplates(mockTemplates);
  };

  const fetchLegalDocuments = async () => {
    const mockDocs: LegalDocument[] = [
      {
        id: '1',
        type: 'TERMS_CONDITIONS',
        title: 'Terms and Conditions',
        content: 'These terms and conditions outline the rules and regulations for the use of TTS PMS...',
        effectiveDate: '2024-01-01',
        version: '1.0'
      },
      {
        id: '2',
        type: 'PRIVACY_POLICY',
        title: 'Privacy Policy',
        content: 'This Privacy Policy describes how TTS PMS collects, uses, and protects your information...',
        effectiveDate: '2024-01-01',
        version: '1.0'
      }
    ];
    setLegalDocs(mockDocs);
  };

  const saveSettings = async (updatedSettings: SystemSettings) => {
    try {
      setIsSaving(true);
      const response = await fetch('/api/admin/settings', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(updatedSettings)
      });

      if (response.ok) {
        setSettings(updatedSettings);
      }
    } catch (error) {
      console.error('Failed to save settings:', error);
    } finally {
      setIsSaving(false);
    }
  };

  const saveTemplate = async (template: Template) => {
    try {
      const response = await fetch(`/api/admin/templates/${template.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(template)
      });

      if (response.ok) {
        await fetchTemplates();
        setEditingTemplate(null);
      }
    } catch (error) {
      console.error('Failed to save template:', error);
    }
  };

  const saveLegalDocument = async (doc: LegalDocument) => {
    try {
      const response = await fetch(`/api/admin/legal/${doc.id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(doc)
      });

      if (response.ok) {
        await fetchLegalDocuments();
        setEditingLegal(null);
      }
    } catch (error) {
      console.error('Failed to save legal document:', error);
    }
  };

  const tabs = [
    { id: 'rates', name: 'Rates & Bonuses', icon: '💰' },
    { id: 'templates', name: 'Templates', icon: '📧' },
    { id: 'legal', name: 'Legal Documents', icon: '📄' },
    { id: 'integrations', name: 'Integrations', icon: '🔗' },
    { id: 'operational', name: 'Operations', icon: '⚙️' }
  ];

  if (isLoading || !settings) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900">System Settings</h1>
        <p className="text-gray-600">Configure system rules, templates, and integrations</p>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id as any)}
              className={`py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab.id
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              }`}
            >
              <span className="mr-2">{tab.icon}</span>
              {tab.name}
            </button>
          ))}
        </nav>
      </div>

      {/* Rates & Bonuses Tab */}
      {activeTab === 'rates' && (
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* Hourly Rates */}
          <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Hourly Rates</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Base Hourly Rate ($)</label>
                <input
                  type="number"
                  value={settings.rates.baseHourlyRate}
                  onChange={(e) => setSettings({
                    ...settings,
                    rates: { ...settings.rates, baseHourlyRate: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  step="0.01"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Manager Hourly Rate ($)</label>
                <input
                  type="number"
                  value={settings.rates.managerHourlyRate}
                  onChange={(e) => setSettings({
                    ...settings,
                    rates: { ...settings.rates, managerHourlyRate: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  step="0.01"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Weekend Multiplier</label>
                <input
                  type="number"
                  value={settings.rates.weekendMultiplier}
                  onChange={(e) => setSettings({
                    ...settings,
                    rates: { ...settings.rates, weekendMultiplier: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                  step="0.1"
                />
              </div>
            </div>
          </div>

          {/* Bonuses */}
          <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Bonuses</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Perfect Attendance (Weekly) ($)</label>
                <input
                  type="number"
                  value={settings.bonuses.perfectAttendanceWeekly}
                  onChange={(e) => setSettings({
                    ...settings,
                    bonuses: { ...settings.bonuses, perfectAttendanceWeekly: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Performance 90%+ ($)</label>
                <input
                  type="number"
                  value={settings.bonuses.performanceBonus90}
                  onChange={(e) => setSettings({
                    ...settings,
                    bonuses: { ...settings.bonuses, performanceBonus90: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Referral Bonus ($)</label>
                <input
                  type="number"
                  value={settings.bonuses.referralBonus}
                  onChange={(e) => setSettings({
                    ...settings,
                    bonuses: { ...settings.bonuses, referralBonus: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
            </div>
          </div>

          {/* Penalties */}
          <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">Penalties</h3>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Late Arrival (15 min) ($)</label>
                <input
                  type="number"
                  value={settings.penalties.lateArrival15min}
                  onChange={(e) => setSettings({
                    ...settings,
                    penalties: { ...settings.penalties, lateArrival15min: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Unexcused Absence ($)</label>
                <input
                  type="number"
                  value={settings.penalties.unexcusedAbsence}
                  onChange={(e) => setSettings({
                    ...settings,
                    penalties: { ...settings.penalties, unexcusedAbsence: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Weekend Leave ($)</label>
                <input
                  type="number"
                  value={settings.penalties.weekendLeave}
                  onChange={(e) => setSettings({
                    ...settings,
                    penalties: { ...settings.penalties, weekendLeave: Number(e.target.value) }
                  })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
            </div>
          </div>
        </div>
      )}

      {/* Templates Tab */}
      {activeTab === 'templates' && (
        <div className="space-y-6">
          <div className="flex justify-between items-center">
            <h3 className="text-lg font-semibold text-gray-900">Email & SMS Templates</h3>
            <button
              onClick={() => setEditingTemplate({
                id: '',
                name: '',
                type: 'EMAIL',
                subject: '',
                content: '',
                variables: []
              })}
              className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
            >
              Add Template
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {templates.map((template) => (
              <div key={template.id} className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div className="flex justify-between items-start mb-4">
                  <div>
                    <h4 className="text-lg font-medium text-gray-900">{template.name}</h4>
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                      template.type === 'EMAIL' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'
                    }`}>
                      {template.type}
                    </span>
                  </div>
                  <button
                    onClick={() => setEditingTemplate(template)}
                    className="text-blue-600 hover:text-blue-700 text-sm font-medium"
                  >
                    Edit
                  </button>
                </div>
                {template.subject && (
                  <div className="mb-2">
                    <span className="text-sm font-medium text-gray-700">Subject:</span>
                    <p className="text-sm text-gray-600">{template.subject}</p>
                  </div>
                )}
                <div className="mb-3">
                  <span className="text-sm font-medium text-gray-700">Content:</span>
                  <p className="text-sm text-gray-600 truncate">{template.content}</p>
                </div>
                <div>
                  <span className="text-sm font-medium text-gray-700">Variables:</span>
                  <div className="flex flex-wrap gap-1 mt-1">
                    {template.variables.map((variable) => (
                      <span key={variable} className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                        {`{{${variable}}}`}
                      </span>
                    ))}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Legal Documents Tab */}
      {activeTab === 'legal' && (
        <div className="space-y-6">
          <div className="flex justify-between items-center">
            <h3 className="text-lg font-semibold text-gray-900">Legal Documents</h3>
            <button
              onClick={() => setEditingLegal({
                id: '',
                type: 'TERMS_CONDITIONS',
                title: '',
                content: '',
                effectiveDate: new Date().toISOString().split('T')[0],
                version: '1.0'
              })}
              className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
            >
              Add Document
            </button>
          </div>

          <div className="space-y-4">
            {legalDocs.map((doc) => (
              <div key={doc.id} className="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                <div className="flex justify-between items-start">
                  <div className="flex-1">
                    <h4 className="text-lg font-medium text-gray-900 mb-2">{doc.title}</h4>
                    <div className="flex items-center space-x-4 text-sm text-gray-600 mb-3">
                      <span>Version {doc.version}</span>
                      <span>Effective: {new Date(doc.effectiveDate).toLocaleDateString()}</span>
                      <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                        doc.type === 'TERMS_CONDITIONS' ? 'bg-blue-100 text-blue-800' :
                        doc.type === 'PRIVACY_POLICY' ? 'bg-green-100 text-green-800' :
                        'bg-purple-100 text-purple-800'
                      }`}>
                        {doc.type.replace('_', ' ')}
                      </span>
                    </div>
                    <p className="text-sm text-gray-600 line-clamp-3">{doc.content}</p>
                  </div>
                  <button
                    onClick={() => setEditingLegal(doc)}
                    className="ml-4 text-blue-600 hover:text-blue-700 text-sm font-medium"
                  >
                    Edit
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Save Button */}
      <div className="flex justify-end">
        <button
          onClick={() => saveSettings(settings)}
          disabled={isSaving}
          className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
        >
          {isSaving ? (
            <div className="flex items-center">
              <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
              Saving...
            </div>
          ) : (
            'Save Settings'
          )}
        </button>
      </div>

      {/* Template Edit Modal */}
      {editingTemplate && (
        <Modal
          isOpen={true}
          onClose={() => setEditingTemplate(null)}
          title={editingTemplate.id ? 'Edit Template' : 'Add Template'}
          size="lg"
        >
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input
                  type="text"
                  value={editingTemplate.name}
                  onChange={(e) => setEditingTemplate({ ...editingTemplate, name: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select
                  value={editingTemplate.type}
                  onChange={(e) => setEditingTemplate({ ...editingTemplate, type: e.target.value as any })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                >
                  <option value="EMAIL">Email</option>
                  <option value="SMS">SMS</option>
                </select>
              </div>
            </div>

            {editingTemplate.type === 'EMAIL' && (
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                <input
                  type="text"
                  value={editingTemplate.subject || ''}
                  onChange={(e) => setEditingTemplate({ ...editingTemplate, subject: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
            )}

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Content</label>
              <textarea
                value={editingTemplate.content}
                onChange={(e) => setEditingTemplate({ ...editingTemplate, content: e.target.value })}
                rows={6}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Variables (comma-separated)</label>
              <input
                type="text"
                value={editingTemplate.variables.join(', ')}
                onChange={(e) => setEditingTemplate({ 
                  ...editingTemplate, 
                  variables: e.target.value.split(',').map(v => v.trim()).filter(v => v) 
                })}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                placeholder="employeeName, username, password"
              />
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setEditingTemplate(null)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={() => saveTemplate(editingTemplate)}
                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
              >
                Save Template
              </button>
            </div>
          </div>
        </Modal>
      )}

      {/* Legal Document Edit Modal */}
      {editingLegal && (
        <Modal
          isOpen={true}
          onClose={() => setEditingLegal(null)}
          title={editingLegal.id ? 'Edit Legal Document' : 'Add Legal Document'}
          size="lg"
        >
          <div className="space-y-4">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Title</label>
                <input
                  type="text"
                  value={editingLegal.title}
                  onChange={(e) => setEditingLegal({ ...editingLegal, title: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Type</label>
                <select
                  value={editingLegal.type}
                  onChange={(e) => setEditingLegal({ ...editingLegal, type: e.target.value as any })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                >
                  <option value="TERMS_CONDITIONS">Terms & Conditions</option>
                  <option value="PRIVACY_POLICY">Privacy Policy</option>
                  <option value="EMPLOYEE_HANDBOOK">Employee Handbook</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Version</label>
                <input
                  type="text"
                  value={editingLegal.version}
                  onChange={(e) => setEditingLegal({ ...editingLegal, version: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-2">Effective Date</label>
                <input
                  type="date"
                  value={editingLegal.effectiveDate}
                  onChange={(e) => setEditingLegal({ ...editingLegal, effectiveDate: e.target.value })}
                  className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Content</label>
              <textarea
                value={editingLegal.content}
                onChange={(e) => setEditingLegal({ ...editingLegal, content: e.target.value })}
                rows={12}
                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"
              />
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setEditingLegal(null)}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={() => saveLegalDocument(editingLegal)}
                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
              >
                Save Document
              </button>
            </div>
          </div>
        </Modal>
      )}
    </div>
  );
}
