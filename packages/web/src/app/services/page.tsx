import Link from 'next/link';

// This would typically come from the API
const mockGigs = [
  {
    id: '1',
    slug: 'data-entry-specialist',
    title: 'Data Entry Specialist',
    description: 'Accurate and efficient data entry services with 99.9% accuracy guarantee. Perfect for businesses looking to digitize their records or maintain databases.',
    price: 25,
    badges: ['Data Entry', 'Excel', 'Accuracy'],
    active: true,
  },
  {
    id: '2',
    slug: 'business-analysis-expert',
    title: 'Business Analysis Expert',
    description: 'Comprehensive business analysis and reporting to drive growth. Includes market research, competitor analysis, and strategic recommendations.',
    price: 45,
    badges: ['Analysis', 'Research', 'Strategy'],
    active: true,
  },
  {
    id: '3',
    slug: 'process-automation-consultant',
    title: 'Process Automation Consultant',
    description: 'Streamline your operations with custom automation solutions. Reduce manual work and increase efficiency with proven methodologies.',
    price: 65,
    badges: ['Automation', 'Efficiency', 'Consulting'],
    active: true,
  },
  {
    id: '4',
    slug: 'virtual-assistant',
    title: 'Virtual Assistant',
    description: 'Professional virtual assistant services for administrative tasks, scheduling, email management, and customer support.',
    price: 20,
    badges: ['Admin', 'Support', 'Communication'],
    active: true,
  },
  {
    id: '5',
    slug: 'content-creation',
    title: 'Content Creation',
    description: 'High-quality content creation including blog posts, social media content, and marketing materials tailored to your brand.',
    price: 35,
    badges: ['Writing', 'Marketing', 'Creative'],
    active: true,
  },
  {
    id: '6',
    slug: 'financial-analysis',
    title: 'Financial Analysis',
    description: 'Detailed financial analysis and reporting services including budget planning, cost analysis, and financial forecasting.',
    price: 55,
    badges: ['Finance', 'Analysis', 'Planning'],
    active: true,
  },
];

export default function ServicesPage() {
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

      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="text-center">
            <h1 className="text-4xl font-bold text-gray-900 mb-4">
              Professional Services
            </h1>
            <p className="text-xl text-gray-600 max-w-3xl mx-auto">
              Discover our comprehensive range of professional services delivered by verified experts. 
              From data entry to complex business analysis, we have the talent to meet your needs.
            </p>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
          <div className="flex flex-wrap items-center gap-4">
            <span className="text-sm font-medium text-gray-700">Filter by:</span>
            <div className="flex flex-wrap gap-2">
              <button className="px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-medium hover:bg-blue-200 transition-colors">
                All Services
              </button>
              <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200 transition-colors">
                Data Entry
              </button>
              <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200 transition-colors">
                Analysis
              </button>
              <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200 transition-colors">
                Automation
              </button>
              <button className="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-gray-200 transition-colors">
                Support
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Services Grid */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {mockGigs.map((gig) => (
            <div key={gig.id} className="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow">
              <div className="p-6">
                <div className="flex flex-wrap gap-2 mb-4">
                  {gig.badges.map((badge) => (
                    <span
                      key={badge}
                      className="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-full"
                    >
                      {badge}
                    </span>
                  ))}
                </div>
                
                <h3 className="text-xl font-semibold text-gray-900 mb-3">
                  {gig.title}
                </h3>
                
                <p className="text-gray-600 mb-6 line-clamp-3">
                  {gig.description}
                </p>
                
                <div className="flex items-center justify-between">
                  <div className="text-2xl font-bold text-blue-600">
                    ${gig.price}/hour
                  </div>
                  <Link
                    href={`/services/${gig.slug}`}
                    className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium"
                  >
                    Learn More
                  </Link>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* CTA Section */}
      <div className="bg-blue-600 py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl font-bold text-white mb-4">
            Need a Custom Solution?
          </h2>
          <p className="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
            Can't find exactly what you're looking for? Our team can create a custom solution tailored to your specific needs.
          </p>
          <Link
            href="/contact"
            className="bg-white text-blue-600 px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transition-colors inline-block"
          >
            Contact Us
          </Link>
        </div>
      </div>

      {/* Footer */}
      <footer className="bg-gray-900 text-white py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <div className="text-2xl font-bold text-blue-400 mb-4">TTS PMS</div>
              <p className="text-gray-400">
                Professional services delivered by verified experts worldwide.
              </p>
            </div>
            <div>
              <h4 className="font-semibold mb-4">Services</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/services" className="hover:text-white">Data Entry</Link></li>
                <li><Link href="/services" className="hover:text-white">Business Analysis</Link></li>
                <li><Link href="/services" className="hover:text-white">Process Automation</Link></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-4">Company</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/contact" className="hover:text-white">Contact</Link></li>
                <li><Link href="/auth/sign-up" className="hover:text-white">Careers</Link></li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-4">Support</h4>
              <ul className="space-y-2 text-gray-400">
                <li><Link href="/auth/sign-in" className="hover:text-white">Login</Link></li>
                <li><Link href="/contact" className="hover:text-white">Help Center</Link></li>
              </ul>
            </div>
          </div>
          <div className="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; 2024 TTS PMS. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}
