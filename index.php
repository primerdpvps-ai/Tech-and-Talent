<?php
/**
 * TTS PMS - Main Landing Page
 * Tech & Talent Solutions Ltd. - Professional Management System
 * Divi Builder Compatible Landing Page
 */

// Load configuration
require_once 'config/init.php';

// Get services/gigs data - Focus on Data Entry & Analytics Solutions
$services = [
    [
        'id' => 1,
        'title' => 'Data Entry Services',
        'description' => 'Professional data entry solutions with 99.9% accuracy guarantee. Expert talent for all your data processing needs.',
        'price_pkr' => 15000,
        'price_usd' => 54,
        'price_basic' => 'Rs. 15,000 / $54',
        'price_standard' => 'Rs. 30,000 / $108',
        'price_premium' => 'Rs. 60,000 / $216',
        'features' => ['99.9% Accuracy', 'Fast Turnaround', 'Quality Assurance', 'Skilled Professionals'],
        'icon' => 'fas fa-keyboard'
    ],
    [
        'id' => 2,
        'title' => 'Data Analytics & Insights',
        'description' => 'Transform your raw data into actionable business insights with our expert analytics team.',
        'price_pkr' => 45000,
        'price_usd' => 162,
        'price_basic' => 'Rs. 45,000 / $162',
        'price_standard' => 'Rs. 90,000 / $324',
        'price_premium' => 'Rs. 180,000 / $648',
        'features' => ['Data Visualization', 'Predictive Analytics', 'Custom Reports', 'Expert Analysis'],
        'icon' => 'fas fa-chart-line'
    ],
    [
        'id' => 3,
        'title' => 'Database Management',
        'description' => 'Complete database solutions including design, optimization, and maintenance by certified professionals.',
        'price_pkr' => 35000,
        'price_usd' => 126,
        'price_basic' => 'Rs. 35,000 / $126',
        'price_standard' => 'Rs. 70,000 / $252',
        'price_premium' => 'Rs. 140,000 / $504',
        'features' => ['Database Design', 'Performance Optimization', 'Data Migration', 'Backup Solutions'],
        'icon' => 'fas fa-database'
    ],
    [
        'id' => 4,
        'title' => 'Excel & Spreadsheet Solutions',
        'description' => 'Advanced Excel solutions, automation, and data processing by Microsoft certified experts.',
        'price_pkr' => 20000,
        'price_usd' => 72,
        'price_basic' => 'Rs. 20,000 / $72',
        'price_standard' => 'Rs. 40,000 / $144',
        'price_premium' => 'Rs. 80,000 / $288',
        'features' => ['Advanced Formulas', 'Automation & Macros', 'Data Visualization', 'Custom Templates'],
        'icon' => 'fas fa-file-excel'
    ],
    [
        'id' => 5,
        'title' => 'Business Intelligence Solutions',
        'description' => 'Comprehensive BI solutions with dashboard creation and data warehousing expertise.',
        'price_pkr' => 75000,
        'price_usd' => 270,
        'price_basic' => 'Rs. 75,000 / $270',
        'price_standard' => 'Rs. 150,000 / $540',
        'price_premium' => 'Rs. 300,000 / $1080',
        'features' => ['Interactive Dashboards', 'Data Warehousing', 'ETL Processes', 'Real-time Analytics'],
        'icon' => 'fas fa-chart-bar'
    ],
    [
        'id' => 6,
        'title' => 'Data Processing & Cleansing',
        'description' => 'Professional data cleansing and processing services to ensure data quality and consistency.',
        'price_pkr' => 25000,
        'price_usd' => 90,
        'price_basic' => 'Rs. 25,000 / $90',
        'price_standard' => 'Rs. 50,000 / $180',
        'price_premium' => 'Rs. 100,000 / $360',
        'features' => ['Data Cleansing', 'Format Standardization', 'Duplicate Removal', 'Quality Validation'],
        'icon' => 'fas fa-broom'
    ]
];

// Get testimonials
$testimonials = [
    [
        'name' => 'Ahmed Hassan',
        'company' => 'Tech Innovations Ltd.',
        'rating' => 5,
        'comment' => 'Excellent service quality and professional team. Delivered our project on time and within budget.',
        'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=100&h=100&fit=crop&crop=face'
    ],
    [
        'name' => 'Sarah Khan',
        'company' => 'Digital Solutions Inc.',
        'rating' => 5,
        'comment' => 'Outstanding work on our mobile app. The team was responsive and delivered beyond expectations.',
        'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=100&h=100&fit=crop&crop=face'
    ],
    [
        'name' => 'Muhammad Ali',
        'company' => 'StartUp Hub',
        'rating' => 5,
        'comment' => 'Professional team with excellent technical skills. Highly recommend their services.',
        'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100&h=100&fit=crop&crop=face'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech & Talent Solutions Ltd. - Professional Services & Workforce Management</title>
    <meta name="description" content="Leading provider of professional tech services and workforce management solutions in Pakistan. Hire skilled professionals or join our talent network.">
    
    <!-- Divi Builder Compatible CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1266f1;
            --secondary-color: #6c757d;
            --success-color: #00b74a;
            --danger-color: #f93154;
            --warning-color: #fbbd08;
            --info-color: #39c0ed;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
        }
        
        /* Divi Compatible Classes */
        .et_pb_section {
            position: relative;
        }
        
        .et_pb_row {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .et_pb_column {
            position: relative;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            color: white;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        /* Navigation */
        .navbar {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }
        
        /* Service Cards */
        .service-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid #e9ecef;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }
        
        .price-tier {
            background: var(--light-color);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            margin: 0.25rem;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Testimonials */
        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            height: 100%;
        }
        
        .testimonial-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            object-fit: cover;
        }
        
        .rating {
            color: #ffc107;
            margin-bottom: 1rem;
        }
        
        /* Operational Hours */
        .hours-highlight {
            background: linear-gradient(135deg, var(--success-color), #28a745);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
        }
        
        /* CTA Buttons */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(18, 102, 241, 0.3);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .service-card {
                margin-bottom: 2rem;
            }
        }
        
        /* Animation Classes */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-building me-2"></i>
                Tech & Talent Solutions
            </a>
            <button class="navbar-toggler" type="button" data-mdb-toggle="collapse" data-mdb-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reviews">Reviews</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#careers">Join Our Team</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary-custom text-white ms-2 px-3" href="packages/web/auth/sign-in.php">
                            <i class="fas fa-sign-in-alt me-1"></i>
                            Sign In
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section et_pb_section">
        <div class="container et_pb_row">
            <div class="hero-content et_pb_column">
                <h1 class="hero-title fade-in">
                    Transform Your Business with
                    <span class="text-warning">Professional Services</span>
                </h1>
                <p class="hero-subtitle fade-in">
                    Leading provider of professional tech services and workforce management solutions. 
                    We connect businesses with skilled professionals and provide comprehensive project management.
                </p>
                
                <!-- Operational Hours Highlight -->
                <div class="row justify-content-center mb-4">
                    <div class="col-md-6">
                        <div class="hours-highlight fade-in">
                            <h5><i class="fas fa-clock me-2"></i>Operational Hours</h5>
                            <p class="mb-2"><strong>Primary Window:</strong> 11:00 AM - 2:00 AM PKT</p>
                            <p class="mb-0"><small>Special extended hours for senior employees: 2:00 AM - 6:00 AM PKT</small></p>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-3 flex-wrap justify-content-center fade-in">
                    <a href="#services" class="btn btn-primary-custom btn-lg text-white">
                        <i class="fas fa-briefcase me-2"></i>
                        Hire Our Services
                    </a>
                    <a href="#careers" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-users me-2"></i>
                        Join Our Workforce
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="py-5 bg-light et_pb_section">
        <div class="container et_pb_row">
            <div class="text-center mb-5">
                <h2 class="fw-bold fade-in">Our Professional Services</h2>
                <p class="text-muted fade-in">Choose from our comprehensive range of technology and business solutions</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($services as $service): ?>
                <div class="col-lg-4 col-md-6 fade-in et_pb_column">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="<?php echo $service['icon']; ?>"></i>
                        </div>
                        <h5 class="text-center mb-3"><?php echo htmlspecialchars($service['title']); ?></h5>
                        <p class="text-muted text-center mb-4"><?php echo htmlspecialchars($service['description']); ?></p>
                        
                        <!-- Pricing Display -->
                        <div class="text-center mb-4">
                            <div class="price-display">
                                <div class="main-price mb-2">
                                    <span class="price-amount"><?php echo $service['price_basic']; ?></span>
                                    <small class="text-muted d-block">Starting Price</small>
                                </div>
                                <div class="pricing-tiers small">
                                    <div>Standard: <?php echo $service['price_standard']; ?></div>
                                    <div>Premium: <?php echo $service['price_premium']; ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Features -->
                        <ul class="list-unstyled mb-4">
                            <?php foreach ($service['features'] as $feature): ?>
                            <li class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                <?php echo htmlspecialchars($feature); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="text-center">
                            <div class="d-grid gap-2">
                                <button class="btn btn-success btn-lg" onclick="buyService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['title']); ?>', <?php echo $service['price_pkr']; ?>, <?php echo $service['price_usd']; ?>)">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Buy Now - <?php echo $service['price_basic']; ?>
                                </button>
                                <button class="btn btn-outline-primary" onclick="requestProposal(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['title']); ?>')">
                                    <i class="fas fa-paper-plane me-2"></i>
                                    Custom Quote
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Reviews/Testimonials Section -->
    <section id="reviews" class="py-5 et_pb_section">
        <div class="container et_pb_row">
            <div class="text-center mb-5">
                <h2 class="fw-bold fade-in">What Our Clients Say</h2>
                <p class="text-muted fade-in">Trusted by businesses across Pakistan and beyond</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($testimonials as $testimonial): ?>
                <div class="col-lg-4 fade-in et_pb_column">
                    <div class="testimonial-card">
                        <img src="<?php echo $testimonial['avatar']; ?>" alt="<?php echo htmlspecialchars($testimonial['name']); ?>" class="testimonial-avatar">
                        <div class="rating mb-3">
                            <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                            <i class="fas fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="mb-3">"<?php echo htmlspecialchars($testimonial['comment']); ?>"</p>
                        <h6 class="mb-1"><?php echo htmlspecialchars($testimonial['name']); ?></h6>
                        <small class="text-muted"><?php echo htmlspecialchars($testimonial['company']); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Careers/Join Team Section -->
    <section id="careers" class="py-5 bg-light et_pb_section">
        <div class="container et_pb_row">
            <div class="row align-items-center">
                <div class="col-lg-6 fade-in et_pb_column">
                    <h2 class="fw-bold mb-4">Join Our Professional Network</h2>
                    <p class="mb-4">
                        Are you a skilled professional looking for flexible work opportunities? 
                        Join our talent network and work with leading businesses across various industries.
                    </p>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-money-bill-wave text-success me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-1">Competitive Pay</h6>
                                    <small class="text-muted">Rs. 125/hour base rate</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-check text-info me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-1">Flexible Schedule</h6>
                                    <small class="text-muted">Full-time & Part-time options</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-trophy text-warning me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-1">Performance Bonus</h6>
                                    <small class="text-muted">28-day streak bonus: +Rs. 500/week</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-primary me-3 fa-2x"></i>
                                <div>
                                    <h6 class="mb-1">Secure Platform</h6>
                                    <small class="text-muted">Professional work environment</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="packages/web/auth/sign-up.php" class="btn btn-primary-custom btn-lg">
                        <i class="fas fa-user-plus me-2"></i>
                        Start Your Application
                    </a>
                </div>
                <div class="col-lg-6 fade-in et_pb_column">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Application Process</h5>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <span class="fw-bold">1</span>
                                </div>
                                <div>
                                    <h6 class="mb-1">Sign Up & Evaluation</h6>
                                    <small class="text-muted">Complete pre-requirements evaluation</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <span class="fw-bold">2</span>
                                </div>
                                <div>
                                    <h6 class="mb-1">KYC & Documentation</h6>
                                    <small class="text-muted">Submit required documents and verification</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <span class="fw-bold">3</span>
                                </div>
                                <div>
                                    <h6 class="mb-1">Training & Onboarding</h6>
                                    <small class="text-muted">Complete training modules</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                    <span class="fw-bold">4</span>
                                </div>
                                <div>
                                    <h6 class="mb-1">Start Working</h6>
                                    <small class="text-muted">Begin earning with professional projects</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 et_pb_section">
        <div class="container et_pb_row">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center mb-5">
                        <h2 class="fw-bold fade-in">Get In Touch</h2>
                        <p class="text-muted fade-in">Ready to start your project or have questions? Contact us today.</p>
                    </div>
                    
                    <div class="card fade-in">
                        <div class="card-body p-5">
                            <form id="contactForm" onsubmit="submitContactForm(event)">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="businessName" class="form-label">Business Name</label>
                                        <input type="text" class="form-control" id="businessName" name="business_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contactPerson" class="form-label">Contact Person</label>
                                        <input type="text" class="form-control" id="contactPerson" name="contact_person" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="serviceType" class="form-label">Service Interest</label>
                                        <select class="form-select" id="serviceType" name="service_type" required>
                                            <option value="">Select a service</option>
                                            <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['title']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="projectBrief" class="form-label">Project Brief</label>
                                        <textarea class="form-control" id="projectBrief" name="project_brief" rows="4" required placeholder="Tell us about your project requirements..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label for="budget" class="form-label">Budget Range</label>
                                        <select class="form-select" id="budget" name="budget">
                                            <option value="">Select budget range</option>
                                            <option value="under-25k">Under Rs. 25,000</option>
                                            <option value="25k-50k">Rs. 25,000 - 50,000</option>
                                            <option value="50k-100k">Rs. 50,000 - 100,000</option>
                                            <option value="100k-plus">Rs. 100,000+</option>
                                        </select>
                                    </div>
                                    <div class="col-12 text-center">
                                        <button type="submit" class="btn btn-primary-custom btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>
                                            Send Message
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5 class="mb-3">Tech & Talent Solutions Ltd.</h5>
                    <p class="mb-3">Leading provider of professional services and workforce management solutions in Pakistan.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="text-white"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Services</h6>
                    <ul class="list-unstyled">
                        <li><a href="#services" class="text-light text-decoration-none">Web Development</a></li>
                        <li><a href="#services" class="text-light text-decoration-none">Mobile Apps</a></li>
                        <li><a href="#services" class="text-light text-decoration-none">Digital Marketing</a></li>
                        <li><a href="#services" class="text-light text-decoration-none">Cloud Solutions</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Company</h6>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light text-decoration-none">About Us</a></li>
                        <li><a href="#careers" class="text-light text-decoration-none">Careers</a></li>
                        <li><a href="#contact" class="text-light text-decoration-none">Contact</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Blog</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Legal</h6>
                    <ul class="list-unstyled">
                        <li><a href="legal/terms.php" class="text-light text-decoration-none">Terms & Conditions</a></li>
                        <li><a href="legal/privacy.php" class="text-light text-decoration-none">Privacy Policy</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Cookie Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6 class="mb-3">Contact Info</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i>info@tts.com.pk</li>
                        <li><i class="fas fa-phone me-2"></i>+92 300 1234567</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i>Lahore, Pakistan</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2025 Tech & Talent Solutions Ltd. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-server me-1"></i>
                        Powered by TTS PMS | 
                        <i class="fas fa-shield-alt me-1"></i>
                        Secure & Professional
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Custom Proposal Modal -->
    <div class="modal fade" id="proposalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Custom Proposal</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="proposalForm" onsubmit="submitProposal(event)">
                        <input type="hidden" id="proposalServiceId" name="service_id">
                        <div class="mb-3">
                            <label for="proposalService" class="form-label">Service</label>
                            <input type="text" class="form-control" id="proposalService" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="proposalBusinessName" class="form-label">Business Name</label>
                            <input type="text" class="form-control" id="proposalBusinessName" name="business_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="proposalContact" class="form-label">Contact Information</label>
                            <input type="text" class="form-control" id="proposalContact" name="contact" required>
                        </div>
                        <div class="mb-3">
                            <label for="proposalBrief" class="form-label">Project Brief</label>
                            <textarea class="form-control" id="proposalBrief" name="brief" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary-custom w-100">
                            <i class="fas fa-paper-plane me-2"></i>
                            Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Stripe Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Payment</h5>
                    <button type="button" class="btn-close" data-mdb-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Service Details</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 id="serviceTitle" class="card-title"></h6>
                                    <div class="mb-3">
                                        <strong>Choose Currency:</strong>
                                        <div class="form-check form-check-inline mt-2">
                                            <input class="form-check-input" type="radio" name="currency" id="currencyPKR" value="pkr" onchange="updateCurrency('pkr')">
                                            <label class="form-check-label" for="currencyPKR">
                                                <span id="servicePricePKR"></span>
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="currency" id="currencyUSD" value="usd" onchange="updateCurrency('usd')">
                                            <label class="form-check-label" for="currencyUSD">
                                                <span id="servicePriceUSD"></span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Total Amount:</span>
                                        <strong id="selectedPrice"></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Payment Information</h6>
                            <div id="payment-element">
                                <!-- Stripe Elements will create form elements here -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="submit-payment">
                        <i class="fas fa-lock me-2"></i>Pay Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://js.stripe.com/v3/"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        // Animation on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.fade-in');
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;
                
                if (elementTop < window.innerHeight - elementVisible) {
                    element.classList.add('visible');
                }
            });
        }

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Request proposal function
        function requestProposal(serviceId, serviceName) {
            document.getElementById('proposalServiceId').value = serviceId;
            document.getElementById('proposalService').value = serviceName;
            const modal = new mdb.Modal(document.getElementById('proposalModal'));
            modal.show();
        }

        // Submit proposal form
        async function submitProposal(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            try {
                const response = await fetch('api/submit-proposal.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Proposal request submitted successfully! We will contact you within 24 hours.');
                    const modal = mdb.Modal.getInstance(document.getElementById('proposalModal'));
                    modal.hide();
                    event.target.reset();
                } else {
                    alert('Error submitting proposal: ' + result.message);
                }
            } catch (error) {
                alert('Error submitting proposal. Please try again.');
            }
        }

        // Submit contact form
        async function submitContactForm(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            try {
                const response = await fetch('api/submit-contact.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Message sent successfully! We will get back to you soon.');
                    event.target.reset();
                } else {
                    alert('Error sending message: ' + result.message);
                }
            } catch (error) {
                alert('Error sending message. Please try again.');
            }
        }

        // Stripe configuration
        const stripe = Stripe('pk_test_51234567890abcdef'); // Replace with your Stripe publishable key
        let elements;
        let currentServiceId;
        let currentServicePrice;
        let currentCurrency = 'pkr';
        
        function buyService(serviceId, serviceName, pricePKR, priceUSD) {
            currentServiceId = serviceId;
            currentServicePrice = pricePKR;
            
            document.getElementById('serviceTitle').textContent = serviceName;
            document.getElementById('servicePricePKR').textContent = 'PKR ' + new Intl.NumberFormat().format(pricePKR);
            document.getElementById('servicePriceUSD').textContent = '$' + priceUSD;
            
            // Set default currency to PKR
            document.getElementById('currencyPKR').checked = true;
            updateCurrency('pkr');
            
            // Initialize Stripe Elements
            initializeStripe(pricePKR, 'pkr');
            
            const modal = new mdb.Modal(document.getElementById('paymentModal'));
            modal.show();
        }
        
        function updateCurrency(currency) {
            currentCurrency = currency;
            const pricePKR = parseInt(document.getElementById('servicePricePKR').textContent.replace(/[^\d]/g, ''));
            const priceUSD = parseInt(document.getElementById('servicePriceUSD').textContent.replace(/[^\d]/g, ''));
            
            if (currency === 'pkr') {
                currentServicePrice = pricePKR;
                document.getElementById('selectedPrice').textContent = 'PKR ' + new Intl.NumberFormat().format(pricePKR);
            } else {
                currentServicePrice = priceUSD;
                document.getElementById('selectedPrice').textContent = '$' + priceUSD;
            }
            
            // Reinitialize Stripe with new currency
            initializeStripe(currentServicePrice, currency);
        }
        
        async function initializeStripe(amount, currency) {
            try {
                const response = await fetch('api/create-payment-intent.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        amount: currency === 'pkr' ? amount * 100 : amount * 100, // Convert to cents
                        currency: currency,
                        service_id: currentServiceId
                    })
                });
                
                const { client_secret } = await response.json();
                
                // Clear previous elements
                const paymentElement = document.getElementById('payment-element');
                paymentElement.innerHTML = '';
                
                elements = stripe.elements({ clientSecret: client_secret });
                
                const paymentElementStripe = elements.create('payment');
                paymentElementStripe.mount('#payment-element');
            } catch (error) {
                console.error('Error initializing Stripe:', error);
                alert('Error initializing payment. Please try again.');
            }
        }
        
        document.getElementById('submit-payment').addEventListener('click', async () => {
            const submitButton = document.getElementById('submit-payment');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            
            try {
                const { error } = await stripe.confirmPayment({
                    elements,
                    confirmParams: {
                        return_url: window.location.origin + '/payment-success.php?service_id=' + currentServiceId
                    }
                });
                
                if (error) {
                    alert('Payment failed: ' + error.message);
                }
            } catch (error) {
                alert('Payment error: ' + error.message);
            } finally {
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-lock me-2"></i>Pay Now';
            }
        });

        // Initialize animations
        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);
        
        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.98)';
            } else {
                navbar.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            }
        });

        // Fix back button navigation
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });

        // Handle browser back/forward buttons
        window.addEventListener('popstate', function(event) {
            if (event.state) {
                window.location.href = event.state.url;
            }
        });

        // Add state to history for better navigation
        if (window.history && window.history.pushState) {
            window.history.replaceState({url: window.location.href}, document.title, window.location.href);
        }
    </script>
</body>
</html>
