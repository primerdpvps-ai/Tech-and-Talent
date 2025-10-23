<?php
/**
 * TTS PMS - Visitor Evaluation Form
 * Complete evaluation form based on WorkHub sample
 */

// Load configuration
require_once '../../../../config/init.php';

// Start session
session_start();

// Check if user is logged in and has visitor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'visitor') {
    header('Location: ../../auth/sign-in.php');
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    try {
        $db = Database::getInstance();
        
        // Collect form data
        $formData = [
            'user_id' => $_SESSION['user_id'],
            'age' => (int)$_POST['q1_age'],
            'guardian_agree' => $_POST['q1b_guardian_agree'] ?? null,
            'senior_reason' => $_POST['q1b_senior_reason'] ?? null,
            'physical_fit' => $_POST['q1c_physical_fit'] ?? null,
            'caretaking' => $_POST['q1d_caretaking'] ?? null,
            'device_type' => $_POST['q2_device_type'],
            'ram' => $_POST['q3_ram'],
            'processor' => $_POST['q4_processor'],
            'stable_internet' => $_POST['q5_stable_internet'],
            'provider' => $_POST['q5b_provider'] ?? null,
            'link_speed' => $_POST['q5c_link_speed'] ?? null,
            'num_users' => (int)($_POST['q5d_num_users'] ?? 0),
            'speedtest_url' => $_POST['q5e_speedtest_url'] ?? null,
            'profession' => $_POST['q6_profession'],
            'daily_time' => $_POST['q7_daily_time'],
            'time_windows' => isset($_POST['time_windows']) ? implode(',', $_POST['time_windows']) : null,
            'qualification' => $_POST['q8_qualification'],
            'confidentiality' => $_POST['q9_confidentiality'],
            'typing_speed' => $_POST['q10_typing_speed'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Apply eligibility logic
        $eligibilityResult = applyEligibilityLogic($formData);
        $formData['status'] = $eligibilityResult['applicationStatus'];
        $formData['reasons'] = json_encode($eligibilityResult['reasons']);
        
        // Save to database
        $db->insert('tts_evaluations', $formData);
        
        $_SESSION['evaluation_result'] = $eligibilityResult;
        header('Location: evaluation.php?result=1');
        exit;
        
    } catch (Exception $e) {
        $error = 'Failed to submit evaluation: ' . $e->getMessage();
    }
}

// Apply eligibility logic function
function applyEligibilityLogic($data) {
    $reasons = [];
    $isEligible = true;
    $applicationStatus = 'Eligible';

    // Age range check
    $age = (int)$data['age'];
    if ($age < 16 || $age > 65) {
        $reasons[] = ['field' => 'q1_age', 'text' => 'Rejection: Age must be between 16 and 65.'];
        $isEligible = false;
    }
    
    // Minor guardian agreement
    if ($age >= 16 && $age <= 17 && $data['guardian_agree'] !== 'Yes') {
        $reasons[] = ['field' => 'q1b_guardian_agree', 'text' => 'Rejection: Guardian written permission is mandatory for applicants under 18.'];
        $isEligible = false;
    }

    // Device type check
    $rejectedDevices = ['Android', 'iPhone'];
    if (in_array($data['device_type'], $rejectedDevices)) {
        $reasons[] = ['field' => 'q2_device_type', 'text' => 'Rejection: Data entry requires a device with physical keyboard (PC, Laptop, Chromebook, or Tablet).'];
        $isEligible = false;
    }
    
    // RAM capacity check
    $ramMatch = preg_match('/(\d+)\s*G?B?/i', $data['ram']);
    $ramGB = $ramMatch ? (int)$ramMatch[1] : 0;
    if ($ramGB < 4) {
        $reasons[] = ['field' => 'q3_ram', 'text' => 'Rejection: Minimum required RAM capacity is 4GB.'];
        $isEligible = false;
    }
    
    // Internet connection check
    if ($data['stable_internet'] !== 'Yes') {
        $reasons[] = ['field' => 'q5_stable_internet', 'text' => 'Rejection: A stable internet connection is mandatory for remote work.'];
        $isEligible = false;
    }
    
    // Speed and sharing check
    if ($data['stable_internet'] === 'Yes' && $isEligible) {
        $speedMatch = preg_match('/(\d+\.?\d*)\s*M?B?P?S?/i', $data['link_speed']);
        $speedMbps = $speedMatch ? (float)$speedMatch[1] : 0;
        
        if ($speedMbps < 5) {
            $reasons[] = ['field' => 'q5c_link_speed', 'text' => 'Rejection: Minimum required link speed is 5 Mbps.'];
            $isEligible = false;
        } else {
            $maxUsers = $speedMbps < 8 ? 1 : ($speedMbps < 20 ? 2 : 3);
            if ($data['num_users'] > $maxUsers) {
                $reasons[] = ['field' => 'q5d_num_users', 'text' => "Rejection: For your speed, maximum allowed shared users is {$maxUsers}."];
                $isEligible = false;
            }
        }
    }
    
    // Daily availability check
    if ($data['daily_time'] !== 'Yes') {
        $reasons[] = ['field' => 'q7_daily_time', 'text' => 'Rejection: Minimum 2-4 hours of daily availability is required.'];
        $isEligible = false;
    }
    
    // Confidentiality check
    if ($data['confidentiality'] !== 'Yes') {
        $reasons[] = ['field' => 'q9_confidentiality', 'text' => 'Rejection: Commitment to confidentiality is mandatory.'];
        $isEligible = false;
    }
    
    // Typing speed check
    if ($data['typing_speed'] !== 'Yes') {
        $reasons[] = ['field' => 'q10_typing_speed', 'text' => 'Rejection: Minimum typing speed of 20 WPM is required.'];
        $isEligible = false;
    }
    
    // Senior age pending review
    if ($isEligible && $age >= 61 && $age <= 65) {
        $applicationStatus = 'Pending';
        $reasons[] = ['field' => 'q1_age', 'text' => 'Pending: Age 61-65 requires manual CEO/Manager approval.'];
    }
    
    if (!$isEligible) {
        $applicationStatus = 'Rejected';
    }

    return [
        'isEligible' => $applicationStatus === 'Eligible',
        'applicationStatus' => $applicationStatus,
        'reasons' => $reasons
    ];
}

// Check for results
$showResult = isset($_GET['result']) && isset($_SESSION['evaluation_result']);
$result = $showResult ? $_SESSION['evaluation_result'] : null;
if ($showResult) {
    unset($_SESSION['evaluation_result']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Evaluation - TTS PMS</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            background-color: #f8f9fa; 
            font-family: 'Inter', sans-serif; 
        }
        .doc-container { 
            max-width: 800px; 
        }
        .card { 
            border-radius: 1rem; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); 
        }
        .header-info { 
            background-color: #f3f4f6; 
            border-radius: 1rem 1rem 0 0; 
            padding: 1.5rem; 
        }
        .form-section { 
            border-bottom: 1px solid #e5e7eb; 
            padding-bottom: 1.5rem; 
            margin-bottom: 1.5rem; 
        }
        .form-section:last-child { 
            border-bottom: none; 
        }
        .highlight-field {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.5);
        }
        .modal-overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background-color: rgba(0, 0, 0, 0.75); z-index: 50;
            display: none; justify-content: center; align-items: center;
        }
        .modal-content {
            max-width: 500px; max-height: 90vh; overflow-y: auto;
            padding: 2rem; background-color: white; border-radius: 1rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <div class="d-flex align-items-center">
                <a href="index.php" class="btn btn-outline-secondary me-3">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                <a class="navbar-brand fw-bold text-primary" href="#">
                    <i class="fas fa-cog me-2"></i>Tech & Talent Solutions
                </a>
            </div>
            <span class="navbar-text fw-semibold text-primary">Applicant Evaluation Form</span>
        </div>
    </nav>

    <main class="container mt-4 mb-5 doc-container">
        <div class="card bg-white">
            <div class="header-info text-center">
                <h1 class="h3 fw-bold text-gray-800 mb-1">WorkHub Applicant Evaluation</h1>
                <p class="text-muted mb-0">Comprehensive assessment for remote data entry positions</p>
            </div>
            
            <div class="p-4">
                <!-- Info Banner -->
                <div class="alert alert-info text-center mb-4">
                    <strong class="text-danger">Review Window: Quarterly</strong> | 
                    <strong class="text-success">Operational Hours: 9:00 AM - 6:00 PM PKT</strong>
                    <br>
                    All communications utilize the <strong>workhub.pk</strong> domain.
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form id="applicantForm" method="POST">
                    <input type="hidden" name="submit_evaluation" value="1">

                    <!-- Q1: Age -->
                    <div class="form-section" data-field="q1_age">
                        <h5 class="fw-semibold text-dark mb-3">Q1. What is your age?</h5>
                        <select id="q1_age" name="q1_age" class="form-select" required>
                            <option value="" disabled selected>Select your age</option>
                            <?php for ($i = 16; $i <= 65; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>

                        <!-- Sub-questions for Age 16-17 -->
                        <div id="sub_q1_minor" class="d-none mt-3 p-3 bg-warning bg-opacity-10 border-start border-warning border-4 rounded-end">
                            <p class="fw-medium text-warning-emphasis mb-2">You are a minor. You will be required to provide your father's/guardian's written permission.</p>
                            <label class="form-label">Are you agree?</label>
                            <select id="q1b_guardian_agree" name="q1b_guardian_agree" class="form-select">
                                <option value="" disabled selected>Select option</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>

                        <!-- Sub-questions for Age 61-65 -->
                        <div id="sub_q1_senior" class="d-none mt-3 p-3 bg-success bg-opacity-10 border-start border-success border-4 rounded-end">
                            <p class="fw-medium text-success-emphasis mb-3">We value experience. Please help us understand your circumstances.</p>
                            
                            <label class="form-label">Briefly outline your professional commitment and key skills:</label>
                            <textarea id="q1b_senior_reason" name="q1b_senior_reason" rows="2" class="form-control mb-3" 
                                placeholder="Example: I am retired but wish to contribute 4 hours daily. My key skill is meticulous data verification."></textarea>
                            
                            <label class="form-label">Are you physically fit for 4+ hours daily remote work?</label>
                            <select id="q1c_physical_fit" name="q1c_physical_fit" class="form-select mb-3">
                                <option value="" disabled selected>Select option</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>

                            <label class="form-label">Do you have any medical condition requiring full-time caretaker?</label>
                            <select id="q1d_caretaking" name="q1d_caretaking" class="form-select">
                                <option value="" disabled selected>Select option</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>
                    </div>

                    <!-- Q2: Device Type -->
                    <div class="form-section" data-field="q2_device_type">
                        <h5 class="fw-semibold text-dark mb-3">Q2. Type of your device?</h5>
                        <select id="q2_device_type" name="q2_device_type" class="form-select" required>
                            <option value="" disabled selected>Select one device type</option>
                            <option value="Android">Android</option>
                            <option value="iPhone">iPhone</option>
                            <option value="PC">PC</option>
                            <option value="Laptop">Laptop</option>
                            <option value="Tablet">Tablet</option>
                            <option value="Chromebook">Chromebook</option>
                        </select>
                    </div>

                    <!-- Q3: RAM Capacity -->
                    <div class="form-section" data-field="q3_ram">
                        <h5 class="fw-semibold text-dark mb-3">Q3. RAM Capacity?</h5>
                        <input type="text" id="q3_ram" name="q3_ram" class="form-control" 
                               placeholder="Example: 4GB, 8GB, 16GB" required>
                    </div>

                    <!-- Q4: Processor -->
                    <div class="form-section" data-field="q4_processor">
                        <h5 class="fw-semibold text-dark mb-3">Q4. Processor model/version?</h5>
                        <input type="text" id="q4_processor" name="q4_processor" class="form-control" 
                               placeholder="Example: Intel Core i5 (8th Gen), AMD Ryzen 5 3600" required>
                    </div>

                    <!-- Q5: Internet Connection -->
                    <div class="form-section" data-field="q5_stable_internet">
                        <h5 class="fw-semibold text-dark mb-3">Q5. Do you have a stable internet connection?</h5>
                        <select id="q5_stable_internet" name="q5_stable_internet" class="form-select" required>
                            <option value="" disabled selected>Select option</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>

                        <!-- Internet Details -->
                        <div id="sub_q5_internet_details" class="d-none mt-3 p-3 bg-info bg-opacity-10 border-start border-info border-4 rounded-end">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Provider Name?</label>
                                    <input type="text" id="q5b_provider" name="q5b_provider" class="form-control" 
                                           placeholder="e.g., PTCL, StormFiber">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Link Speed (Download/Upload)?</label>
                                    <input type="text" id="q5c_link_speed" name="q5c_link_speed" class="form-control" 
                                           placeholder="e.g., 10 Mbps / 5 Mbps">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Number of users sharing this link?</label>
                                    <input type="number" id="q5d_num_users" name="q5d_num_users" class="form-control" 
                                           placeholder="e.g., 2">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Speedtest.net result URL/ID:</label>
                                    <input type="text" id="q5e_speedtest_url" name="q5e_speedtest_url" class="form-control" 
                                           placeholder="Paste URL/ID here">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Q6: Profession -->
                    <div class="form-section" data-field="q6_profession">
                        <h5 class="fw-semibold text-dark mb-3">Q6. What is your profession?</h5>
                        <select id="q6_profession" name="q6_profession" class="form-select" required>
                            <option value="" disabled selected>Select your profession</option>
                            <option value="Self employed">Self employed</option>
                            <option value="Full time employee">Full time employee</option>
                            <option value="Student">Student</option>
                            <option value="House wife">House wife</option>
                            <option value="Looking for a job">Looking for a job</option>
                            <option value="Retired">Retired</option>
                        </select>
                    </div>

                    <!-- Q7: Daily Availability -->
                    <div class="form-section" data-field="q7_daily_time">
                        <h5 class="fw-semibold text-dark mb-3">Q7. Can you spare a minimum 2-4 hours time for data entry work daily?</h5>
                        <select id="q7_daily_time" name="q7_daily_time" class="form-select" required>
                            <option value="" disabled selected>Select option</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>

                        <!-- Time Windows -->
                        <div id="sub_q7_time_windows" class="d-none mt-3 p-3 bg-warning bg-opacity-10 border-start border-warning border-4 rounded-end">
                            <p class="fw-medium text-warning-emphasis mb-3">Select your available time windows:</p>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="time_windows[]" value="11AM-4PM PKT" id="time_window_1">
                                        <label class="form-check-label" for="time_window_1">11 AM - 4 PM PKT</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="time_windows[]" value="4PM-9PM PKT" id="time_window_2">
                                        <label class="form-check-label" for="time_window_2">4 PM - 9 PM PKT</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="time_windows[]" value="9PM-2AM PKT" id="time_window_3">
                                        <label class="form-check-label" for="time_window_3">9 PM - 2 AM PKT</label>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Part-time (4 hours) select 1-2 windows; Full-time (6-8+ hours) select multiple.</small>
                        </div>
                    </div>

                    <!-- Q8: Qualification -->
                    <div class="form-section" data-field="q8_qualification">
                        <h5 class="fw-semibold text-dark mb-3">Q8. What is your Qualification?</h5>
                        <input type="text" id="q8_qualification" name="q8_qualification" class="form-control" 
                               placeholder="(Authentic documents may be required at the next stage)" required>
                    </div>

                    <!-- Q9: Confidentiality -->
                    <div class="form-section" data-field="q9_confidentiality">
                        <h5 class="fw-semibold text-dark mb-3">Q9. Are you sure that you can manage confidential data and information of our foreign valuable clients and do not share it anywhere else?</h5>
                        <select id="q9_confidentiality" name="q9_confidentiality" class="form-select" required>
                            <option value="" disabled selected>Select option</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <!-- Q10: Typing Speed -->
                    <div class="form-section" data-field="q10_typing_speed">
                        <h5 class="fw-semibold text-dark mb-3">Q10. Do you have a typing speed of at least 20 words per minute?</h5>
                        <select id="q10_typing_speed" name="q10_typing_speed" class="form-select" required>
                            <option value="" disabled selected>Select option</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-paper-plane me-2"></i>Submit and See Results
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Results Modal -->
    <div id="resultsModal" class="modal-overlay" <?php echo $showResult ? 'style="display: flex;"' : 'style="display: none;"'; ?>>
        <div class="modal-content text-center">
            <h2 id="modalTitle" class="h3 fw-bold mb-4">
                <?php 
                if ($result) {
                    echo $result['applicationStatus'] === 'Eligible' ? 'Application Status: Eligible' : 
                         ($result['applicationStatus'] === 'Pending' ? 'Application Status: Pending Review' : 'Application Status: Non-Eligible');
                }
                ?>
            </h2>
            
            <div id="modalStatusIcon" class="mx-auto my-4" style="width: 64px; height: 64px;">
                <?php if ($result): ?>
                    <?php if ($result['applicationStatus'] === 'Eligible'): ?>
                        <div class="bg-success bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center h-100">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
                        </div>
                    <?php elseif ($result['applicationStatus'] === 'Pending'): ?>
                        <div class="bg-warning bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center h-100">
                            <i class="fas fa-clock text-warning fa-2x"></i>
                        </div>
                    <?php else: ?>
                        <div class="bg-danger bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center h-100">
                            <i class="fas fa-times-circle text-danger fa-2x"></i>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <p id="modalMessage" class="mb-4">
                <?php 
                if ($result) {
                    if ($result['applicationStatus'] === 'Eligible') {
                        echo "You meet all initial critical requirements. We'll be in touch soon regarding the next steps.";
                    } elseif ($result['applicationStatus'] === 'Pending') {
                        echo "Your application is eligible for manual review due to special circumstances. We will contact you after CEO/Manager approval.";
                    } else {
                        echo "You did not meet one or more critical requirements. Please review the reasons below:";
                    }
                }
                ?>
            </p>
            
            <?php if ($result && !empty($result['reasons'])): ?>
            <div class="text-start bg-<?php echo $result['applicationStatus'] === 'Rejected' ? 'danger' : 'warning'; ?> bg-opacity-10 p-3 rounded border">
                <p class="fw-semibold mb-2">Details:</p>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($result['reasons'] as $reason): ?>
                        <li class="mb-1"><i class="fas fa-info-circle me-2"></i><?php echo htmlspecialchars($reason['text']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <button id="closeModalButton" class="btn btn-secondary mt-4">
                <i class="fas fa-times me-2"></i>Close and Review
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Age-based conditional logic
            const q1Age = document.getElementById('q1_age');
            const subQ1Minor = document.getElementById('sub_q1_minor');
            const subQ1Senior = document.getElementById('sub_q1_senior');
            
            q1Age.addEventListener('change', function() {
                subQ1Minor.classList.add('d-none');
                subQ1Senior.classList.add('d-none');
                
                const age = parseInt(this.value);
                if (age === 16 || age === 17) {
                    subQ1Minor.classList.remove('d-none');
                } else if (age >= 61 && age <= 65) {
                    subQ1Senior.classList.remove('d-none');
                }
            });
            
            // Internet connection conditional logic
            const q5Internet = document.getElementById('q5_stable_internet');
            const subQ5Details = document.getElementById('sub_q5_internet_details');
            
            q5Internet.addEventListener('change', function() {
                if (this.value === 'Yes') {
                    subQ5Details.classList.remove('d-none');
                } else {
                    subQ5Details.classList.add('d-none');
                }
            });
            
            // Daily time conditional logic
            const q7Time = document.getElementById('q7_daily_time');
            const subQ7Windows = document.getElementById('sub_q7_time_windows');
            
            q7Time.addEventListener('change', function() {
                if (this.value === 'Yes') {
                    subQ7Windows.classList.remove('d-none');
                } else {
                    subQ7Windows.classList.add('d-none');
                }
            });
            
            // Modal close functionality
            const closeModalButton = document.getElementById('closeModalButton');
            const resultsModal = document.getElementById('resultsModal');
            
            if (closeModalButton) {
                closeModalButton.addEventListener('click', function() {
                    resultsModal.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>
