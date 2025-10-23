<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>TTS PMS - Payments</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- MDB Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #1266f1;
            --secondary-color: #6c757d;
            --success-color: #00b74a;
            --danger-color: #f93154;
            --warning-color: #fbbd08;
            --info-color: #39c0ed;
            
            /* Light Theme */
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }
        
        [data-mdb-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #e9ecef;
            --border-color: #404040;
            --shadow-color: rgba(0, 0, 0, 0.3);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        
        .payment-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .payment-card {
            background-color: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            box-shadow: 0 10px 30px var(--shadow-color);
            transition: all 0.3s ease;
        }
        
        .payment-method {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        
        .payment-method:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .payment-method.active {
            border-color: var(--primary-color);
            background-color: rgba(18, 102, 241, 0.1);
        }
        
        .form-control {
            background-color: var(--bg-primary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        .form-control:focus {
            background-color: var(--bg-primary);
            border-color: var(--primary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(18, 102, 241, 0.25);
        }
        
        .navbar {
            backdrop-filter: blur(10px);
            background-color: rgba(18, 102, 241, 0.95) !important;
        }
        
        .theme-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        @media (max-width: 768px) {
            .payment-container {
                padding: 1rem;
            }
            
            .payment-method {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-building me-2"></i>
                TTS PMS
            </a>
            
            <div class="navbar-nav ms-auto align-items-center">
                <!-- Theme Toggle -->
                <div class="nav-item me-3">
                    <span class="theme-toggle text-white" onclick="toggleTheme()">
                        <i class="fas fa-moon" id="theme-icon"></i>
                    </span>
                </div>
                
                <!-- Back to Dashboard -->
                <div class="nav-item">
                    <a class="nav-link text-white" href="dashboard.php">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Payment Form -->
    <div class="payment-container mt-5">
        <div class="payment-card">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h2 class="mb-2">
                        <i class="fas fa-credit-card text-primary me-2"></i>
                        Payment Gateway
                    </h2>
                    <p class="text-muted">Choose your preferred payment method</p>
                </div>

                <!-- Payment Amount -->
                <div class="mb-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h4 class="text-primary mb-1">$99.99</h4>
                            <small class="text-muted">TTS PMS Subscription</small>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="mb-4">
                    <h5 class="mb-3">Select Payment Method</h5>
                    
                    <!-- Stripe -->
                    <div class="payment-method active" onclick="selectPaymentMethod('stripe')">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fab fa-stripe fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Credit/Debit Card</h6>
                                <small class="text-muted">Powered by Stripe</small>
                            </div>
                            <div>
                                <i class="fas fa-check-circle text-success d-none" id="stripe-check"></i>
                            </div>
                        </div>
                    </div>

                    <!-- PayPal -->
                    <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fab fa-paypal fa-2x text-info"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">PayPal</h6>
                                <small class="text-muted">Pay with your PayPal account</small>
                            </div>
                            <div>
                                <i class="fas fa-check-circle text-success d-none" id="paypal-check"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Google Pay -->
                    <div class="payment-method" onclick="selectPaymentMethod('googlepay')">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fab fa-google-pay fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Google Pay</h6>
                                <small class="text-muted">Quick and secure payments</small>
                            </div>
                            <div>
                                <i class="fas fa-check-circle text-success d-none" id="googlepay-check"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Form -->
                <form id="payment-form">
                    <!-- Stripe Form -->
                    <div id="stripe-form" class="payment-form-section">
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CVV</label>
                                <input type="text" class="form-control" placeholder="123" maxlength="4">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Cardholder Name</label>
                                <input type="text" class="form-control" placeholder="John Doe">
                            </div>
                        </div>
                    </div>

                    <!-- PayPal Form -->
                    <div id="paypal-form" class="payment-form-section d-none">
                        <div class="text-center p-4">
                            <i class="fab fa-paypal fa-4x text-info mb-3"></i>
                            <p class="text-muted">You will be redirected to PayPal to complete your payment</p>
                        </div>
                    </div>

                    <!-- Google Pay Form -->
                    <div id="googlepay-form" class="payment-form-section d-none">
                        <div class="text-center p-4">
                            <i class="fab fa-google-pay fa-4x text-warning mb-3"></i>
                            <p class="text-muted">Use your Google Pay account for quick checkout</p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-lock me-2"></i>
                            Pay $99.99 Securely
                        </button>
                    </div>
                </form>

                <!-- Security Info -->
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Your payment information is encrypted and secure
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- MDB Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
        let selectedPaymentMethod = 'stripe';

        // Theme toggle functionality
        function toggleTheme() {
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            const currentTheme = html.getAttribute('data-mdb-theme');
            
            if (currentTheme === 'dark') {
                html.setAttribute('data-mdb-theme', 'light');
                themeIcon.className = 'fas fa-moon';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-mdb-theme', 'dark');
                themeIcon.className = 'fas fa-sun';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Payment method selection
        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            
            // Update UI
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.payment-method i.fa-check-circle').forEach(el => {
                el.classList.add('d-none');
            });
            
            event.currentTarget.classList.add('active');
            document.getElementById(method + '-check').classList.remove('d-none');
            
            // Show/hide forms
            document.querySelectorAll('.payment-form-section').forEach(el => {
                el.classList.add('d-none');
            });
            document.getElementById(method + '-form').classList.remove('d-none');
        }

        // Form submission
        document.getElementById('payment-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitBtn.disabled = true;
            
            // Simulate payment processing
            setTimeout(() => {
                alert(`Payment processed successfully using ${selectedPaymentMethod}!`);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            
            html.setAttribute('data-mdb-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            
            // Initialize first payment method
            selectPaymentMethod('stripe');
        });

        // Card number formatting
        document.addEventListener('input', function(e) {
            if (e.target.placeholder === '1234 5678 9012 3456') {
                let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                e.target.value = formattedValue;
            }
            
            if (e.target.placeholder === 'MM/YY') {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            }
        });
    </script>
</body>
</html>
