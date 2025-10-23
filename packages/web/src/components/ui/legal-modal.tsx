'use client';

import { useState } from 'react';
import { Modal } from './modal';

interface LegalDocument {
  id: string;
  title: string;
  effectiveDate: string;
  content: string;
}

interface LegalModalProps {
  isOpen: boolean;
  onClose: () => void;
  onAccept: () => void;
  documents: LegalDocument[];
  title?: string;
  description?: string;
  acceptButtonText?: string;
  isLoading?: boolean;
}

export function LegalModal({
  isOpen,
  onClose,
  onAccept,
  documents,
  title = "Legal Documents",
  description = "Please review and accept the following documents to continue.",
  acceptButtonText = "Accept & Continue",
  isLoading = false
}: LegalModalProps) {
  const [acceptedDocuments, setAcceptedDocuments] = useState<Set<string>>(new Set());
  const [activeDocument, setActiveDocument] = useState<string | null>(null);

  const handleDocumentAccept = (documentId: string) => {
    setAcceptedDocuments(prev => new Set([...prev, documentId]));
  };

  const handleDocumentReject = (documentId: string) => {
    setAcceptedDocuments(prev => {
      const newSet = new Set(prev);
      newSet.delete(documentId);
      return newSet;
    });
  };

  const allDocumentsAccepted = documents.every(doc => acceptedDocuments.has(doc.id));

  const handleAccept = () => {
    if (allDocumentsAccepted) {
      onAccept();
    }
  };

  return (
    <Modal isOpen={isOpen} onClose={onClose} size="xl">
      <div className="max-h-[80vh] flex flex-col">
        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h2 className="text-2xl font-bold text-gray-900">{title}</h2>
            <p className="text-gray-600 mt-1">{description}</p>
          </div>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg p-1"
            aria-label="Close modal"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Document List */}
        <div className="flex-1 overflow-hidden">
          {activeDocument ? (
            // Document Detail View
            <div className="h-full flex flex-col">
              {(() => {
                const doc = documents.find(d => d.id === activeDocument);
                if (!doc) return null;
                
                return (
                  <>
                    <div className="flex items-center justify-between mb-4">
                      <button
                        onClick={() => setActiveDocument(null)}
                        className="flex items-center text-blue-600 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg px-2 py-1"
                        aria-label="Back to document list"
                      >
                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                      </button>
                      <span className="text-sm text-gray-500">
                        Effective: {new Date(doc.effectiveDate).toLocaleDateString()}
                      </span>
                    </div>
                    
                    <h3 className="text-xl font-semibold text-gray-900 mb-4">{doc.title}</h3>
                    
                    <div className="flex-1 bg-gray-50 rounded-lg p-6 overflow-y-auto mb-6">
                      <div className="prose prose-sm max-w-none">
                        {doc.content.split('\n\n').map((paragraph, index) => (
                          <p key={index} className="mb-4 text-gray-700 leading-relaxed">
                            {paragraph}
                          </p>
                        ))}
                      </div>
                    </div>
                    
                    <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                      <div className="flex items-center">
                        <input
                          type="checkbox"
                          id={`accept-${doc.id}`}
                          checked={acceptedDocuments.has(doc.id)}
                          onChange={(e) => {
                            if (e.target.checked) {
                              handleDocumentAccept(doc.id);
                            } else {
                              handleDocumentReject(doc.id);
                            }
                          }}
                          className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                          aria-describedby={`accept-${doc.id}-description`}
                        />
                        <label 
                          htmlFor={`accept-${doc.id}`}
                          className="ml-3 text-sm font-medium text-gray-900 cursor-pointer"
                        >
                          I have read and accept this document
                        </label>
                      </div>
                      <span 
                        id={`accept-${doc.id}-description`}
                        className={`text-sm font-medium ${
                          acceptedDocuments.has(doc.id) ? 'text-green-600' : 'text-gray-500'
                        }`}
                      >
                        {acceptedDocuments.has(doc.id) ? '✓ Accepted' : 'Not accepted'}
                      </span>
                    </div>
                  </>
                );
              })()}
            </div>
          ) : (
            // Document List View
            <div className="space-y-4">
              {documents.map((doc) => (
                <div
                  key={doc.id}
                  className={`border rounded-lg p-4 transition-colors ${
                    acceptedDocuments.has(doc.id)
                      ? 'border-green-200 bg-green-50'
                      : 'border-gray-200 bg-white hover:bg-gray-50'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex-1">
                      <h4 className="text-lg font-semibold text-gray-900 mb-1">
                        {doc.title}
                      </h4>
                      <p className="text-sm text-gray-600">
                        Effective Date: {new Date(doc.effectiveDate).toLocaleDateString()}
                      </p>
                    </div>
                    
                    <div className="flex items-center space-x-3">
                      {acceptedDocuments.has(doc.id) && (
                        <span className="flex items-center text-green-600 text-sm font-medium">
                          <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                          </svg>
                          Accepted
                        </span>
                      )}
                      
                      <button
                        onClick={() => setActiveDocument(doc.id)}
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors font-medium"
                        aria-label={`Review ${doc.title}`}
                      >
                        Review
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Footer */}
        {!activeDocument && (
          <div className="mt-6 pt-6 border-t border-gray-200">
            <div className="flex items-center justify-between">
              <div className="text-sm text-gray-600">
                {acceptedDocuments.size} of {documents.length} documents accepted
              </div>
              
              <div className="flex space-x-3">
                <button
                  onClick={onClose}
                  className="px-6 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors font-medium"
                  disabled={isLoading}
                >
                  Cancel
                </button>
                
                <button
                  onClick={handleAccept}
                  disabled={!allDocumentsAccepted || isLoading}
                  className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-semibold"
                  aria-describedby="accept-button-description"
                >
                  {isLoading ? (
                    <div className="flex items-center">
                      <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                      Processing...
                    </div>
                  ) : (
                    acceptButtonText
                  )}
                </button>
              </div>
            </div>
            
            <p id="accept-button-description" className="text-xs text-gray-500 mt-2 text-right">
              {!allDocumentsAccepted && "Please review and accept all documents to continue"}
            </p>
          </div>
        )}
      </div>
    </Modal>
  );
}
