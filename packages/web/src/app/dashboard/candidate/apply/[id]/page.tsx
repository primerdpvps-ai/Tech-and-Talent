'use client';

import { useState } from 'react';
import { useSession } from 'next-auth/react';
import { redirect, useRouter } from 'next/navigation';
import { WebcamCapture } from '@/components/forms/webcam-capture';
import { SignaturePad } from '@/components/forms/signature-pad';

interface ApplicationStep {
  step: number;
  title: string;
  description: string;
}

const steps: ApplicationStep[] = [
  { step: 1, title: 'Personal Information', description: 'Complete your personal details' },
  { step: 2, title: 'KYC & Verification', description: 'Identity verification and selfie' },
  { step: 3, title: 'Contract & Signature', description: 'Review and sign your contract' },
];

interface PageProps {
  params: {
    id: string;
  };
}

export default function ApplicationWizard({ params }: PageProps) {
  const { data: session, status } = useSession();
  const router = useRouter();
  const [currentStep, setCurrentStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  // Form data state
  const [personalData, setPersonalData] = useState({
    fullName: '',
    email: '',
    phone: '',
    dateOfBirth: '',
    address: '',
    city: '',
    province: '',
    country: '',
    emergencyContact: '',
    emergencyPhone: '',
  });

  const [kycData, setKycData] = useState({
    idType: '',
    idNumber: '',
    idFrontImage: '',
    idBackImage: '',
    selfieImage: '',
  });

  const [contractData, setContractData] = useState({
    agreedToTerms: false,
    signature: '',
    signedAt: '',
  });

  if (status === 'loading') {
    return <div className="p-6">Loading...</div>;
  }

  if (!session || session.user.role !== 'CANDIDATE') {
    redirect('/');
  }

  // Mock job data (in production, fetch from API)
  const jobTitle = params.id === '1' ? 'Data Entry Specialist' : 'Virtual Assistant';

  const handlePersonalSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setCurrentStep(2);
  };

  const handleKycSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!kycData.selfieImage) {
      alert('Please take a selfie to continue');
      return;
    }
    setCurrentStep(3);
  };

  const handleFinalSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!contractData.signature) {
      alert('Please sign the contract to continue');
      return;
    }

    setIsSubmitting(true);
    try {
      const applicationData = {
        jobId: params.id,
        personalData,
        kycData,
        contractData: {
          ...contractData,
          signedAt: new Date().toISOString(),
        },
      };

      const response = await fetch('/api/applications', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(applicationData),
      });

      if (response.ok) {
        router.push('/dashboard/candidate?success=Application submitted successfully');
      } else {
        alert('Failed to submit application. Please try again.');
      }
    } catch (error) {
      alert('An error occurred. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handlePersonalChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setPersonalData({
      ...personalData,
      [e.target.name]: e.target.value,
    });
  };

  const handleKycChange = (e: React.ChangeEvent<HTMLInputElement | HTMLSelectElement>) => {
    setKycData({
      ...kycData,
      [e.target.name]: e.target.value,
    });
  };

  const handleSelfieCapture = (imageData: string) => {
    setKycData({
      ...kycData,
      selfieImage: imageData,
    });
  };

  const handleSignature = (signatureData: string) => {
    setContractData({
      ...contractData,
      signature: signatureData,
    });
  };

  const renderStepContent = () => {
    switch (currentStep) {
      case 1:
        return (
          <form onSubmit={handlePersonalSubmit} className="space-y-6">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label htmlFor="fullName" className="block text-sm font-medium text-gray-700 mb-2">
                  Full Name *
                </label>
                <input
                  type="text"
                  id="fullName"
                  name="fullName"
                  required
                  value={personalData.fullName}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Enter your full name"
                />
              </div>

              <div>
                <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-2">
                  Email Address *
                </label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  required
                  value={personalData.email}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="your@email.com"
                />
              </div>

              <div>
                <label htmlFor="phone" className="block text-sm font-medium text-gray-700 mb-2">
                  Phone Number *
                </label>
                <input
                  type="tel"
                  id="phone"
                  name="phone"
                  required
                  value={personalData.phone}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="+1 (555) 123-4567"
                />
              </div>

              <div>
                <label htmlFor="dateOfBirth" className="block text-sm font-medium text-gray-700 mb-2">
                  Date of Birth *
                </label>
                <input
                  type="date"
                  id="dateOfBirth"
                  name="dateOfBirth"
                  required
                  value={personalData.dateOfBirth}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>

              <div className="md:col-span-2">
                <label htmlFor="address" className="block text-sm font-medium text-gray-700 mb-2">
                  Street Address *
                </label>
                <input
                  type="text"
                  id="address"
                  name="address"
                  required
                  value={personalData.address}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="123 Main Street"
                />
              </div>

              <div>
                <label htmlFor="city" className="block text-sm font-medium text-gray-700 mb-2">
                  City *
                </label>
                <input
                  type="text"
                  id="city"
                  name="city"
                  required
                  value={personalData.city}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="New York"
                />
              </div>

              <div>
                <label htmlFor="province" className="block text-sm font-medium text-gray-700 mb-2">
                  State/Province *
                </label>
                <input
                  type="text"
                  id="province"
                  name="province"
                  required
                  value={personalData.province}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="NY"
                />
              </div>

              <div>
                <label htmlFor="country" className="block text-sm font-medium text-gray-700 mb-2">
                  Country *
                </label>
                <select
                  id="country"
                  name="country"
                  required
                  value={personalData.country}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Select Country</option>
                  <option value="US">United States</option>
                  <option value="CA">Canada</option>
                  <option value="UK">United Kingdom</option>
                  <option value="PK">Pakistan</option>
                  <option value="IN">India</option>
                </select>
              </div>

              <div>
                <label htmlFor="emergencyContact" className="block text-sm font-medium text-gray-700 mb-2">
                  Emergency Contact Name *
                </label>
                <input
                  type="text"
                  id="emergencyContact"
                  name="emergencyContact"
                  required
                  value={personalData.emergencyContact}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Contact person name"
                />
              </div>

              <div>
                <label htmlFor="emergencyPhone" className="block text-sm font-medium text-gray-700 mb-2">
                  Emergency Contact Phone *
                </label>
                <input
                  type="tel"
                  id="emergencyPhone"
                  name="emergencyPhone"
                  required
                  value={personalData.emergencyPhone}
                  onChange={handlePersonalChange}
                  className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="+1 (555) 123-4567"
                />
              </div>
            </div>

            <button
              type="submit"
              className="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
            >
              Continue to KYC Verification
            </button>
          </form>
        );

      case 2:
        return (
          <form onSubmit={handleKycSubmit} className="space-y-8">
            {/* ID Verification */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Identity Verification</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label htmlFor="idType" className="block text-sm font-medium text-gray-700 mb-2">
                    ID Type *
                  </label>
                  <select
                    id="idType"
                    name="idType"
                    required
                    value={kycData.idType}
                    onChange={handleKycChange}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  >
                    <option value="">Select ID Type</option>
                    <option value="passport">Passport</option>
                    <option value="drivers_license">Driver's License</option>
                    <option value="national_id">National ID Card</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="idNumber" className="block text-sm font-medium text-gray-700 mb-2">
                    ID Number *
                  </label>
                  <input
                    type="text"
                    id="idNumber"
                    name="idNumber"
                    required
                    value={kycData.idNumber}
                    onChange={handleKycChange}
                    className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    placeholder="Enter ID number"
                  />
                </div>
              </div>
            </div>

            {/* Selfie Capture */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Selfie Verification</h3>
              <p className="text-gray-600 mb-6">
                Please take a clear selfie for identity verification. Make sure your face is well-lit and clearly visible.
              </p>
              <WebcamCapture onCapture={handleSelfieCapture} />
            </div>

            <button
              type="submit"
              className="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
            >
              Continue to Contract
            </button>
          </form>
        );

      case 3:
        return (
          <form onSubmit={handleFinalSubmit} className="space-y-8">
            {/* Contract Terms */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Employment Contract</h3>
              <div className="bg-gray-50 border border-gray-200 rounded-lg p-6 max-h-96 overflow-y-auto">
                <div className="prose prose-sm">
                  <h4 className="font-semibold">EMPLOYMENT AGREEMENT</h4>
                  <p>
                    This Employment Agreement ("Agreement") is entered into between TTS PMS ("Company") 
                    and {personalData.fullName} ("Employee") for the position of {jobTitle}.
                  </p>
                  
                  <h5 className="font-semibold mt-4">1. Position and Duties</h5>
                  <p>
                    Employee agrees to perform the duties of {jobTitle} as assigned by the Company. 
                    Employee will work remotely and maintain professional standards at all times.
                  </p>

                  <h5 className="font-semibold mt-4">2. Compensation</h5>
                  <p>
                    Employee will be compensated on an hourly basis as per the agreed rate. 
                    Payment will be made weekly based on verified work hours.
                  </p>

                  <h5 className="font-semibold mt-4">3. Confidentiality</h5>
                  <p>
                    Employee agrees to maintain strict confidentiality regarding all Company 
                    information, client data, and business processes.
                  </p>

                  <h5 className="font-semibold mt-4">4. Work Schedule</h5>
                  <p>
                    Employee must work during designated operational hours (11:00 AM - 2:00 AM PKT) 
                    and maintain minimum daily work requirements.
                  </p>

                  <h5 className="font-semibold mt-4">5. Termination</h5>
                  <p>
                    Either party may terminate this agreement with 14 days written notice. 
                    Company reserves the right to terminate immediately for cause.
                  </p>

                  <h5 className="font-semibold mt-4">6. Data Protection</h5>
                  <p>
                    Employee consents to monitoring of work activities including screen recording 
                    and activity tracking for quality assurance and security purposes.
                  </p>
                </div>
              </div>
            </div>

            {/* Agreement Checkbox */}
            <div className="flex items-start">
              <input
                type="checkbox"
                id="agreedToTerms"
                checked={contractData.agreedToTerms}
                onChange={(e) => setContractData({ ...contractData, agreedToTerms: e.target.checked })}
                className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-1"
              />
              <label htmlFor="agreedToTerms" className="ml-3 text-sm text-gray-700">
                I have read, understood, and agree to the terms and conditions of this employment contract. 
                I consent to the data processing and monitoring activities described above.
              </label>
            </div>

            {/* Digital Signature */}
            <div>
              <h3 className="text-lg font-semibold text-gray-900 mb-4">Digital Signature</h3>
              <p className="text-gray-600 mb-4">
                Please sign below to complete your application:
              </p>
              <SignaturePad onSignature={handleSignature} />
            </div>

            <button
              type="submit"
              disabled={!contractData.agreedToTerms || !contractData.signature || isSubmitting}
              className="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isSubmitting ? 'Submitting Application...' : 'Submit Application'}
            </button>
          </form>
        );

      default:
        return null;
    }
  };

  return (
    <div className="max-w-4xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">
          Apply for {jobTitle}
        </h1>
        <p className="text-gray-600">
          Complete the application process in 3 simple steps
        </p>
      </div>

      {/* Progress Steps */}
      <div className="mb-8">
        <div className="flex items-center justify-between">
          {steps.map((step, index) => (
            <div key={step.step} className="flex items-center">
              <div
                className={`flex items-center justify-center w-10 h-10 rounded-full text-sm font-medium ${
                  currentStep >= step.step
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-200 text-gray-500'
                }`}
              >
                {currentStep > step.step ? (
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                  </svg>
                ) : (
                  step.step
                )}
              </div>
              {index < steps.length - 1 && (
                <div
                  className={`w-24 h-0.5 mx-4 ${
                    currentStep > step.step ? 'bg-blue-600' : 'bg-gray-200'
                  }`}
                />
              )}
            </div>
          ))}
        </div>
        <div className="mt-4 text-center">
          <h3 className="text-lg font-medium text-gray-900">
            {steps[currentStep - 1]?.title}
          </h3>
          <p className="text-sm text-gray-600">
            {steps[currentStep - 1]?.description}
          </p>
        </div>
      </div>

      {/* Step Content */}
      <div className="bg-white rounded-xl shadow-lg p-8">
        {renderStepContent()}
      </div>

      {/* Navigation */}
      {currentStep > 1 && (
        <div className="mt-6 flex justify-start">
          <button
            onClick={() => setCurrentStep(currentStep - 1)}
            className="px-6 py-2 text-gray-600 hover:text-gray-800 font-medium"
          >
            ← Back to Previous Step
          </button>
        </div>
      )}
    </div>
  );
}
