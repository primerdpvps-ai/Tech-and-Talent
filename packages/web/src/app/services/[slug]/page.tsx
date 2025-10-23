import Link from 'next/link';
import { notFound } from 'next/navigation';

// Mock data - in production this would come from the API
const mockGigs: Record<string, any> = {
  'data-entry-specialist': {
    id: '1',
    slug: 'data-entry-specialist',
    title: 'Data Entry Specialist',
    description: 'Accurate and efficient data entry services with 99.9% accuracy guarantee. Perfect for businesses looking to digitize their records or maintain databases.',
    price: 25,
    badges: ['Data Entry', 'Excel', 'Accuracy'],
    active: true,
    longDescription: `Our data entry specialists are highly trained professionals who ensure your data is processed with maximum accuracy and efficiency. We handle various types of data including:

• Customer information and contact details
• Product catalogs and inventory data
• Financial records and transactions
• Survey responses and research data
• Document digitization and OCR processing

All work is double-checked for accuracy and delivered within agreed timelines. We use secure systems and follow strict confidentiality protocols to protect your sensitive information.`,
    features: [
      '99.9% accuracy guarantee',
      'Secure data handling',
      'Fast turnaround times',
      'Multiple format support',
      'Quality assurance checks',
      '24/7 support available'
    ],
    deliverables: [
      'Clean, organized data files',
      'Quality assurance report',
      'Data validation summary',
      'Format conversion if needed'
    ]
  },
  'business-analysis-expert': {
    id: '2',
    slug: 'business-analysis-expert',
    title: 'Business Analysis Expert',
    description: 'Comprehensive business analysis and reporting to drive growth. Includes market research, competitor analysis, and strategic recommendations.',
    price: 45,
    badges: ['Analysis', 'Research', 'Strategy'],
    active: true,
    longDescription: `Our business analysis experts provide comprehensive insights to help your business make informed decisions and drive growth. Our services include:

• Market research and competitive analysis
• Financial performance evaluation
• Process optimization recommendations
• Risk assessment and mitigation strategies
• Growth opportunity identification
• Strategic planning and roadmap development

We use industry-standard tools and methodologies to deliver actionable insights that can immediately impact your business performance.`,
    features: [
      'Comprehensive market research',
      'Competitive landscape analysis',
      'Financial modeling',
      'Strategic recommendations',
      'Risk assessment',
      'Implementation roadmap'
    ],
    deliverables: [
      'Detailed analysis report',
      'Executive summary',
      'Action plan with timelines',
      'Supporting data and charts'
    ]
  }
};

interface PageProps {
  params: {
    slug: string;
  };
}

export default function ServiceDetailPage({ params }: PageProps) {
  const gig = mockGigs[params.slug];

  if (!gig) {
    notFound();
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Navigation */}
      <nav className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <Link href="/" className="text-2xl font-bold text-blue-600">
                TTS PMS
              </Link>
            </div>
            <div className="flex items-center space-x-4">
              <Link href="/services" className="text-blue-600 font-medium">
                Services
              </Link>
              <Link href="/contact" className="text-gray-600 hover:text-gray-900">
                Contact
              </Link>
              <Link 
                href="/auth/sign-in"
                className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
              >
                Sign In
              </Link>
            </div>
          </div>
        </div>
      </nav>

      {/* Breadcrumb */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <nav className="flex" aria-label="Breadcrumb">
            <ol className="flex items-center space-x-4">
              <li>
                <Link href="/" className="text-gray-500 hover:text-gray-700">
                  Home
                </Link>
              </li>
              <li>
                <svg className="flex-shrink-0 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
                </svg>
              </li>
              <li>
                <Link href="/services" className="text-gray-500 hover:text-gray-700">
                  Services
                </Link>
              </li>
              <li>
                <svg className="flex-shrink-0 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
                </svg>
              </li>
              <li>
                <span className="text-gray-900 font-medium">{gig.title}</span>
              </li>
            </ol>
          </nav>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-12">
          {/* Main Content */}
          <div className="lg:col-span-2">
            {/* Header */}
            <div className="mb-8">
              <div className="flex flex-wrap gap-2 mb-4">
                {gig.badges.map((badge: string) => (
                  <span
                    key={badge}
                    className="px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full"
                  >
                    {badge}
                  </span>
                ))}
              </div>
              
              <h1 className="text-4xl font-bold text-gray-900 mb-4">
                {gig.title}
              </h1>
              
              <p className="text-xl text-gray-600">
                {gig.description}
              </p>
            </div>

            {/* Description */}
            <div className="bg-white rounded-xl shadow-lg p-8 mb-8">
              <h2 className="text-2xl font-bold text-gray-900 mb-6">Service Overview</h2>
              <div className="prose prose-gray max-w-none">
                {gig.longDescription.split('\n\n').map((paragraph: string, index: number) => (
                  <p key={index} className="mb-4 text-gray-600 leading-relaxed">
                    {paragraph}
                  </p>
                ))}
              </div>
            </div>

            {/* Features */}
            <div className="bg-white rounded-xl shadow-lg p-8 mb-8">
              <h2 className="text-2xl font-bold text-gray-900 mb-6">What's Included</h2>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {gig.features.map((feature: string, index: number) => (
                  <div key={index} className="flex items-start">
                    <svg className="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                    </svg>
                    <span className="text-gray-700">{feature}</span>
                  </div>
                ))}
              </div>
            </div>

            {/* Deliverables */}
            <div className="bg-white rounded-xl shadow-lg p-8">
              <h2 className="text-2xl font-bold text-gray-900 mb-6">Deliverables</h2>
              <ul className="space-y-3">
                {gig.deliverables.map((deliverable: string, index: number) => (
                  <li key={index} className="flex items-start">
                    <svg className="flex-shrink-0 h-5 w-5 text-blue-500 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span className="text-gray-700">{deliverable}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>

          {/* Sidebar */}
          <div className="lg:col-span-1">
            <div className="sticky top-8">
              {/* Pricing Card */}
              <div className="bg-white rounded-xl shadow-lg p-8 mb-8">
                <div className="text-center mb-6">
                  <div className="text-4xl font-bold text-blue-600 mb-2">
                    ${gig.price}/hour
                  </div>
                  <p className="text-gray-600">Starting rate</p>
                </div>
                
                <div className="space-y-4">
                  <Link
                    href="/contact"
                    className="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-center block"
                  >
                    Get Started
                  </Link>
                  
                  <Link
                    href="/contact"
                    className="w-full bg-gray-100 text-gray-700 py-3 px-6 rounded-lg font-semibold hover:bg-gray-200 transition-colors text-center block"
                  >
                    Request Quote
                  </Link>
                </div>
                
                <div className="mt-6 pt-6 border-t border-gray-200">
                  <div className="flex items-center text-sm text-gray-600 mb-2">
                    <svg className="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Typical delivery: 2-5 business days
                  </div>
                  <div className="flex items-center text-sm text-gray-600">
                    <svg className="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    100% satisfaction guarantee
                  </div>
                </div>
              </div>

              {/* Contact Info */}
              <div className="bg-white rounded-xl shadow-lg p-8">
                <h3 className="text-lg font-semibold text-gray-900 mb-4">
                  Need Help Choosing?
                </h3>
                <p className="text-gray-600 mb-6">
                  Our team is here to help you find the perfect solution for your needs.
                </p>
                <Link
                  href="/contact"
                  className="text-blue-600 font-medium hover:text-blue-700"
                >
                  Contact our experts →
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Related Services */}
      <div className="bg-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-12">
            <h2 className="text-3xl font-bold text-gray-900 mb-4">
              Related Services
            </h2>
            <p className="text-xl text-gray-600">
              Explore other services that might interest you
            </p>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-8">
            {Object.values(mockGigs)
              .filter((relatedGig: any) => relatedGig.slug !== gig.slug)
              .slice(0, 3)
              .map((relatedGig: any) => (
                <div key={relatedGig.id} className="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow">
                  <h3 className="text-xl font-semibold text-gray-900 mb-3">
                    {relatedGig.title}
                  </h3>
                  <p className="text-gray-600 mb-4 line-clamp-2">
                    {relatedGig.description}
                  </p>
                  <div className="flex items-center justify-between">
                    <span className="text-lg font-bold text-blue-600">
                      ${relatedGig.price}/hour
                    </span>
                    <Link
                      href={`/services/${relatedGig.slug}`}
                      className="text-blue-600 font-medium hover:text-blue-700"
                    >
                      Learn More →
                    </Link>
                  </div>
                </div>
              ))}
          </div>
        </div>
      </div>
    </div>
  );
}
