<?php
require_once '../../config/init.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en" data-mdb-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS PMS - Invoices</title>
    
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
        }
        
        .navbar {
            backdrop-filter: blur(10px);
            background-color: rgba(18, 102, 241, 0.95) !important;
        }
        
        .card {
            background-color: var(--bg-primary);
            border-color: var(--border-color);
            transition: all 0.3s ease;
        }
        
        .theme-toggle {
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .theme-toggle:hover {
            transform: scale(1.1);
        }
        
        .invoice-preview {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 2rem;
            background-color: var(--bg-primary);
            margin-bottom: 2rem;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
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

    <!-- Main Content -->
    <div class="container-fluid mt-4">
        <div class="fade-in">
            <!-- Header -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
                <div class="mb-3 mb-md-0">
                    <h2 class="mb-1">
                        <i class="fas fa-file-invoice text-primary me-2"></i>
                        Invoice Management
                    </h2>
                    <p class="text-muted mb-0">Create, manage and track invoices</p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInvoiceModal">
                    <i class="fas fa-plus me-2"></i>
                    Create Invoice
                </button>
            </div>

            <!-- Invoice Summary -->
            <div class="row mb-4">
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3" style="background-color: rgba(18, 102, 241, 0.1);">
                                <i class="fas fa-file-invoice text-primary fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Invoices</h6>
                                <h4 class="mb-0">247</h4>
                                <small class="text-primary">All Time</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3" style="background-color: rgba(0, 183, 74, 0.1);">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Paid</h6>
                                <h4 class="mb-0">198</h4>
                                <small class="text-success">$89,450</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3" style="background-color: rgba(251, 189, 8, 0.1);">
                                <i class="fas fa-clock text-warning fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Pending</h6>
                                <h4 class="mb-0">32</h4>
                                <small class="text-warning">$15,230</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body d-flex align-items-center">
                            <div class="rounded-circle p-3 me-3" style="background-color: rgba(249, 49, 84, 0.1);">
                                <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Overdue</h6>
                                <h4 class="mb-0">17</h4>
                                <small class="text-danger">$8,920</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice List -->
            <div class="card">
                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Recent Invoices
                    </h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="overdue">Overdue</option>
                            <option value="draft">Draft</option>
                        </select>
                        <button class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download me-1"></i>
                            Export
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold">#INV-2024-001</td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">Acme Corporation</div>
                                            <small class="text-muted">contact@acme.com</small>
                                        </div>
                                    </td>
                                    <td class="fw-bold">$2,500.00</td>
                                    <td>Oct 15, 2024</td>
                                    <td>Nov 15, 2024</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" title="Download PDF">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="Send Email">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">#INV-2024-002</td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">Tech Solutions Ltd</div>
                                            <small class="text-muted">billing@techsolutions.com</small>
                                        </div>
                                    </td>
                                    <td class="fw-bold">$1,850.00</td>
                                    <td>Oct 18, 2024</td>
                                    <td>Nov 18, 2024</td>
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" title="Download PDF">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-outline-info" title="Send Email">
                                                <i class="fas fa-envelope"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">#INV-2024-003</td>
                                    <td>
                                        <div>
                                            <div class="fw-bold">Global Enterprises</div>
                                            <small class="text-muted">accounts@global.com</small>
                                        </div>
                                    </td>
                                    <td class="fw-bold">$3,200.00</td>
                                    <td>Sep 28, 2024</td>
                                    <td>Oct 28, 2024</td>
                                    <td><span class="badge bg-danger">Overdue</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" title="Download PDF">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" title="Send Reminder">
                                                <i class="fas fa-exclamation"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Previous</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#">2</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#">3</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Invoice Modal -->
    <div class="modal fade" id="createInvoiceModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Create New Invoice
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Invoice Form -->
                        <div class="col-lg-6">
                            <form id="createInvoiceForm">
                                <h6 class="mb-3">Client Information</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Client Name *</label>
                                        <input type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email *</label>
                                        <input type="email" class="form-control" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea class="form-control" rows="2"></textarea>
                                </div>
                                
                                <h6 class="mb-3 mt-4">Invoice Details</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Invoice Number</label>
                                        <input type="text" class="form-control" value="#INV-2024-004" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Issue Date *</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Due Date *</label>
                                        <input type="date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Currency</label>
                                        <select class="form-select">
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                            <option value="GBP">GBP</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <h6 class="mb-3 mt-4">Line Items</h6>
                                <div id="lineItems">
                                    <div class="row mb-2">
                                        <div class="col-md-5">
                                            <input type="text" class="form-control" placeholder="Description">
                                        </div>
                                        <div class="col-md-2">
                                            <input type="number" class="form-control" placeholder="Qty" value="1">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="number" class="form-control" placeholder="Rate" step="0.01">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm w-100">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm mb-3" onclick="addLineItem()">
                                    <i class="fas fa-plus me-1"></i>
                                    Add Line Item
                                </button>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Tax (%)</label>
                                        <input type="number" class="form-control" value="0" step="0.01">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Discount</label>
                                        <input type="number" class="form-control" value="0" step="0.01">
                                    </div>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Invoice Preview -->
                        <div class="col-lg-6">
                            <h6 class="mb-3">Invoice Preview</h6>
                            <div class="invoice-preview">
                                <div class="d-flex justify-content-between mb-4">
                                    <div>
                                        <h4 class="text-primary">TTS PMS</h4>
                                        <p class="mb-0">123 Business Street<br>
                                        City, State 12345<br>
                                        contact@tts-pms.com</p>
                                    </div>
                                    <div class="text-end">
                                        <h5>INVOICE</h5>
                                        <p class="mb-0">#INV-2024-004</p>
                                    </div>
                                </div>
                                
                                <div class="row mb-4">
                                    <div class="col-6">
                                        <strong>Bill To:</strong><br>
                                        <span id="previewClientName">Client Name</span><br>
                                        <span id="previewClientEmail">client@email.com</span><br>
                                        <span id="previewClientAddress">Client Address</span>
                                    </div>
                                    <div class="col-6 text-end">
                                        <strong>Issue Date:</strong> <span id="previewIssueDate">-</span><br>
                                        <strong>Due Date:</strong> <span id="previewDueDate">-</span>
                                    </div>
                                </div>
                                
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>Qty</th>
                                            <th>Rate</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="previewLineItems">
                                        <tr>
                                            <td>Sample Service</td>
                                            <td>1</td>
                                            <td>$100.00</td>
                                            <td>$100.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                                
                                <div class="text-end mt-3">
                                    <p class="mb-1"><strong>Subtotal: $100.00</strong></p>
                                    <p class="mb-1">Tax: $0.00</p>
                                    <p class="mb-1">Discount: $0.00</p>
                                    <h5 class="text-primary">Total: $100.00</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-outline-primary">Save as Draft</button>
                    <button type="submit" form="createInvoiceForm" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Create Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- MDB Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.2/mdb.min.js"></script>
    
    <script>
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

        // Add line item
        function addLineItem() {
            const lineItems = document.getElementById('lineItems');
            const newItem = document.createElement('div');
            newItem.className = 'row mb-2';
            newItem.innerHTML = `
                <div class="col-md-5">
                    <input type="text" class="form-control" placeholder="Description">
                </div>
                <div class="col-md-2">
                    <input type="number" class="form-control" placeholder="Qty" value="1">
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control" placeholder="Rate" step="0.01">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeLineItem(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            lineItems.appendChild(newItem);
        }

        // Remove line item
        function removeLineItem(button) {
            button.closest('.row').remove();
        }

        // Load saved theme
        document.addEventListener('DOMContentLoaded', function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            const themeIcon = document.getElementById('theme-icon');
            
            html.setAttribute('data-mdb-theme', savedTheme);
            themeIcon.className = savedTheme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

            // Add form submission handler
            document.getElementById('createInvoiceForm').addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Invoice created successfully! (Demo functionality)');
                bootstrap.Modal.getInstance(document.getElementById('createInvoiceModal')).hide();
            });
        });
    </script>
</body>
</html>
