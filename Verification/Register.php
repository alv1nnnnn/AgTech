<?php
session_start();

require_once '../Connection/connection.php';
require '../vendor/autoload.php'; // Composer autoloader for PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sanitize_data($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$message = "";
$old_values = $_POST ?? []; // Initialize old_values with form data or an empty array

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize_data($_POST['first-name']);
    $last_name = sanitize_data($_POST['last-name']);
    $password = sanitize_data($_POST['password']);
    $phone = sanitize_data($_POST['phone']);
    $email = isset($_POST['email']) ? sanitize_data($_POST['email']) : '';
    $user_type = isset($_POST['user-type']) ? sanitize_data($_POST['user-type']) : 'Farmer-Trader';
    $birthdate = sanitize_data($_POST['birthdate']);
    $province = sanitize_data($_POST['province-name']);
    $municipality = sanitize_data($_POST['municipality-name']);
    $barangay = sanitize_data($_POST['barangay-name']);
    $postal_code = sanitize_data($_POST['postal-code']);

    // Check if the email already exists in the database
    $check_email_query = "SELECT * FROM user WHERE email = ?";
    $stmt = $conn->prepare($check_email_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // If email exists, show Swal alert
        $message = "Email already exists. Please use a different email address.";
    } else {
        // Remaining logic...
        $birthdateObj = new DateTime($birthdate);
        $today = new DateTime();
        $age = $today->diff($birthdateObj)->y;

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $file_paths = [];

        if (isset($_FILES['idFrontUpload']) && $_FILES['idFrontUpload']['error'] == UPLOAD_ERR_OK) {
            $idFrontTmpName = $_FILES['idFrontUpload']['tmp_name'];
            $idFrontName = basename($_FILES['idFrontUpload']['name']);
            $idFrontPath = '../images/' . $idFrontName;
            move_uploaded_file($idFrontTmpName, $idFrontPath);
            $file_paths['front'] = $idFrontPath;
        }

        if (isset($_FILES['idBackUpload']) && $_FILES['idBackUpload']['error'] == UPLOAD_ERR_OK) {
            $idBackTmpName = $_FILES['idBackUpload']['tmp_name'];
            $idBackName = basename($_FILES['idBackUpload']['name']);
            $idBackPath = '../images/' . $idBackName;
            move_uploaded_file($idBackTmpName, $idBackPath);
            $file_paths['back'] = $idBackPath;
        }

        $valid_id_json = json_encode($file_paths);

        $_SESSION['registration_data'] = [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'password' => $hashed_password,
            'phone_number' => $phone,
            'email' => $email,
            'user_type' => $user_type,
            'valid_id' => $valid_id_json,
            'birthdate' => $birthdate,
            'age' => $age,
            'province-name' => $province,
            'municipality-name' => $municipality,
            'barangay-name' => $barangay,
            'postal_code' => $postal_code
        ];

        $otp_code = rand(100000, 999999);
        $_SESSION['otp_code'] = $otp_code;
        $_SESSION['otp_email'] = $email;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'theagtechteam@gmail.com';
            $mail->Password = 'nqtqbognkhvtbkxg';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port =  587;

            $mail->setFrom('theagtechteam@gmail.com', 'AgTech Verification Code');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Your OTP Code';
            $mail->Body = "Your OTP code is <b>$otp_code</b>";
            $mail->AltBody = "Your OTP code is $otp_code";

            $mail->send();

            header("Location: Otp_email.php");
            exit();
        } catch (Exception $e) {
            $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="../css/login_register.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/philippine-location-json-for-geer@1.1.11/build/phil.min.js"></script>
</head>
<body>
    
<div class="registration-container">
        <i class="bi bi-x-lg" onclick="window.history.back();"></i>
    <div class="registration-header">
    <i class="bi bi-chevron-left" onclick="window.history.back();"></i>
    <h2>REGISTRATION FORM</h2>
</div>

    <form class="registration-form" action="Register.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" id="provinceName" name="province-name" value="<?= $_SESSION['registration_data']['province-name'] ?? ''; ?>">
    <input type="hidden" id="municipalityName" name="municipality-name" value="<?= $_SESSION['registration_data']['municipality-name'] ?? ''; ?>">
    <input type="hidden" id="barangayName" name="barangay-name" value="<?= $_SESSION['registration_data']['barangay-name'] ?? ''; ?>">
    <div class="form-group">
        <label for="first-name">First Name <i class="bi bi-asterisk"></i></label>
        <input type="text" id="first-name" name="first-name" placeholder="Enter First Name" required value="<?= htmlspecialchars($old_values['first-name'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="last-name">Last Name <i class="bi bi-asterisk"></i></label>
        <input type="text" id="last-name" name="last-name" placeholder="Enter Last Name" required value="<?= htmlspecialchars($old_values['last-name'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="birthdate">Date of Birth <i class="bi bi-asterisk"></i></label>
        <input type="date" id="birthdate" name="birthdate" required max="2004-12-31" value="<?= htmlspecialchars($old_values['birthdate'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="age">Age <i class="bi bi-asterisk"></i></label>
        <input type="text" id="age" name="age" placeholder="Age" required value="<?= htmlspecialchars($old_values['age'] ?? ''); ?>" readonly>
    </div>
    <div class="form-group">
        <label for="phone">Phone Number <i class="bi bi-asterisk"></i></label>
        <input type="tel" id="phone" name="phone" placeholder="Enter Phone Number" required pattern="\d{11}" value="<?= htmlspecialchars($old_values['phone'] ?? ''); ?>">
    </div>
        <div class="form-group">
        <label for="email">Email <i class="bi bi-asterisk"></i></label>
        <input type="email" id="email" placeholder="Enter Active Email" name="email" value="<?= htmlspecialchars($old_values['email'] ?? ''); ?>">
    </div>
    <div class="form-group">
        <label for="password">Password <i class="bi bi-asterisk"></i></label>
        <div class="input-container">
            <input type="password" id="password" name="password" placeholder="Enter Password" minlength="8" required>
            <i class="bi bi-eye icon" id="showPassword"></i>
            <i class="bi bi-eye-slash icon d-none" id="hidePassword"></i>
        </div>
        <small id="passwordHelp" class="form-text">Your password must be 8-20 characters long.</small>
        <div id="passwordStrength" class="form-text"></div>
    </div>
    <div class="form-group">
        <label for="confirm-password">Confirm Password <i class="bi bi-asterisk"></i></label>
        <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm Password" minlength="8" required>
        <small id="passwordMatch" class="form-text">Passwords must match.</small>
    </div>
    <div class="form-group">
        <label for="province">Province <i class="bi bi-asterisk"></i></label>
        <select id="provinceSelector" name="province">
            <option value="" <?= empty($old_values['province-name']) ? 'selected' : ''; ?>>Select Province</option>
            <!-- Populate provinces dynamically -->
        </select>
    </div>
    <div class="form-group">
        <label for="municipality">Municipality <i class="bi bi-asterisk"></i></label>
        <select id="municipalitySelector" name="municipality">
            <option value="" <?= empty($old_values['municipality-name']) ? 'selected' : ''; ?>>Select Municipality</option>
            <!-- Populate municipalities dynamically -->
        </select>
    </div>
    <div class="form-group">
        <label for="barangay">Barangay <i class="bi bi-asterisk"></i></label>
        <select id="barangaySelector" name="barangay">
            <option value="" <?= empty($old_values['barangay-name']) ? 'selected' : ''; ?>>Select Barangay</option>
            <!-- Populate barangays dynamically -->
        </select>
    </div>
    <div class="form-group">
        <label for="postal-code">Postal Code <i class="bi bi-asterisk"></i></label>
        <input type="text" id="postal-code" name="postal-code" placeholder="Zip code" required value="<?= htmlspecialchars($old_values['postal-code'] ?? ''); ?>">
    </div>
    <div class="footer">
        <div class="checkbox-con">
            <input type="checkbox" name="" id="" required>
            <p>I agree to the <a href="#" data-toggle="modal" data-target="#termsModal">Terms and Conditions</a> and <a href="#" data-toggle="modal" data-target="#privacyModal">Privacy Policy</a></p>
        </div>

            <!-- Scrollable Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" role="dialog" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
      </div>
         <div class="modal-body">
                    <?php
                    $termsContent = file_get_contents('../Terms&Conditions.txt');
                    echo nl2br($termsContent);
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    </form>
    
    <!-- Privacy Policy Modal -->
    <div class="modal fade" id="privacyModal" tabindex="-1" role="dialog" aria-labelledby="privacyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
                </div>
                <div class="modal-body">
                    <?php
                    $privacyContent = file_get_contents('../Policy&Privacy.txt');
                    echo nl2br($privacyContent);
                    ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

            <button type="submit" class="register-btn">SUBMIT</button>
            <p>Already have an account? <a href="../Login/Login.php" class="login-link">Login</a></p>
            </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <script>
            Swal.fire({
                title: 'Error!',
                text: "<?php echo $message; ?>",
                icon: 'error',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Link external data files from the data folder -->
    <script src="../js/province.js"></script>
    <script src="../js/municipality.js"></script>
    <script src="../js/barangay.js"></script>
    <script src="../js/zipcode.js"></script>
    
    <script>
        document.getElementById('birthdate').addEventListener('input', function() {
            var birthdate = new Date(this.value);
            var today = new Date();
            var age = today.getFullYear() - birthdate.getFullYear();
            var m = today.getMonth() - birthdate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }
            document.getElementById('age').value = age;
        });

        document.getElementById('password').addEventListener('input', function () {
            var password = this.value;
            var minLength = parseInt(this.getAttribute('minlength'));
            var passwordStrength = document.getElementById('passwordStrength');

            // Default message
            passwordStrength.textContent = '';

            // Check length
            if (password.length < minLength) {
                document.getElementById('passwordHelp').style.display = 'block';
                return;
            } else {
                document.getElementById('passwordHelp').style.display = 'none';
            }

            // Check for other criteria (e.g., numbers, uppercase, special characters)
            var regexNumber = /\d/;
            var regexUppercase = /[A-Z]/;
            var regexSpecial = /[!@#$%^&*(),.?":{}|<>]/;

            var strength = 0;
            if (regexNumber.test(password)) {
                strength++;
            }
            if (regexUppercase.test(password)) {
                strength++;
            }
            if (regexSpecial.test(password)) {
                strength++;
            }

            // Determine strength message
            if (strength < 2) {
                passwordStrength.textContent = 'Weak password';
                passwordStrength.style.color = 'gray';
            } else if (strength === 2) {
                passwordStrength.textContent = 'Moderate password';
                passwordStrength.style.color = 'orange';
            } else {
                passwordStrength.textContent = 'Strong password';
                passwordStrength.style.color = 'green';
            }
        });

        document.addEventListener("DOMContentLoaded", function() {
            var confirmPasswordInput = document.getElementById('confirm-password');
            var passwordInput = document.getElementById('password');
            var passwordMatchMessage = document.getElementById('passwordMatch');

            confirmPasswordInput.addEventListener('input', function () {
                var confirmPassword = this.value;
                var password = passwordInput.value;

                if (password !== confirmPassword) {
                    passwordMatchMessage.textContent = confirmPassword ? 'Passwords do not match' : 'Passwords must match';
                    passwordMatchMessage.style.color = 'red';
                } else {
                    passwordMatchMessage.textContent = 'Passwords match';
                    passwordMatchMessage.style.color = 'green';
                }
            });
        });
    </script>
    <script>
    const passwordInput = document.getElementById('password');
    const showPasswordIcon = document.getElementById('showPassword');
    const hidePasswordIcon = document.getElementById('hidePassword');

    // Function to toggle password visibility and icons
    function togglePasswordVisibility() {
        const hasPassword = passwordInput.value !== '';
        if (hasPassword) {
            showPasswordIcon.classList.remove('d-none');
        } else {
            showPasswordIcon.classList.add('d-none');
            hidePasswordIcon.classList.add('d-none');
        }
    }

    // Event listener for showing password
    showPasswordIcon.addEventListener('click', function() {
        passwordInput.type = 'text';
        showPasswordIcon.classList.add('d-none');
        hidePasswordIcon.classList.remove('d-none');
    });

    // Event listener for hiding password
    hidePasswordIcon.addEventListener('click', function() {
        passwordInput.type = 'password';
        hidePasswordIcon.classList.add('d-none');
        showPasswordIcon.classList.remove('d-none');
    });

    // Initially hide the icons if password field is empty
    togglePasswordVisibility();

    // Add input event listener to toggle icon visibility
    passwordInput.addEventListener('input', togglePasswordVisibility);
</script>

<script>
// Reference the selectors
const provinceSelector = document.getElementById('provinceSelector');
const municipalitySelector = document.getElementById('municipalitySelector');
const barangaySelector = document.getElementById('barangaySelector');

// Variables to store selected names
let selectedProvinceName = '';
let selectedMunicipalityName = '';
let selectedBarangayName = '';

// Populate Province Selector
function populateProvinces() {
    console.log('Populating provinces...');
    provinces.forEach(province => {
        const option = document.createElement('option');
        option.value = province.province_code; // Set code as the value
        option.textContent = province.province_name; // Display name in dropdown
        provinceSelector.appendChild(option);
    });
}

// Populate Municipality Selector
function populateMunicipalities(provinceCode) {
    console.log('Populating municipalities for province:', provinceCode);
    municipalitySelector.innerHTML = '<option value="">Select Municipality</option>';
    municipalities.forEach(city => {
        if (city.province_code === provinceCode) {
            const option = document.createElement('option');
            option.value = city.city_code;
            option.textContent = city.city_name;
            municipalitySelector.appendChild(option);
        }
    });
    municipalitySelector.disabled = false;
}

// Populate Barangay Selector
function populateBarangays(cityCode) {
    console.log('Populating barangays for municipality:', cityCode);
    barangaySelector.innerHTML = '<option value="">Select Barangay</option>';
    barangays.forEach(barangay => {
        if (barangay.city_code === cityCode) {
            const option = document.createElement('option');
            option.value = barangay.brgy_code;
            option.textContent = barangay.brgy_name;
            barangaySelector.appendChild(option);
        }
    });
    barangaySelector.disabled = false;
}

provinceSelector.addEventListener('change', () => {
    const selectedOption = provinceSelector.options[provinceSelector.selectedIndex];
    selectedProvinceName = selectedOption.textContent.trim(); // Capture selected name
    document.getElementById('provinceName').value = selectedProvinceName; // Update hidden input
    populateMunicipalities(selectedOption.value);
});

municipalitySelector.addEventListener('change', () => {
    const selectedOption = municipalitySelector.options[municipalitySelector.selectedIndex];
    selectedMunicipalityName = selectedOption.textContent.trim(); // Capture selected name
    document.getElementById('municipalityName').value = selectedMunicipalityName; // Update hidden input
    populateBarangays(selectedOption.value);
});

barangaySelector.addEventListener('change', () => {
    const selectedOption = barangaySelector.options[barangaySelector.selectedIndex];
    selectedBarangayName = selectedOption.textContent.trim(); // Capture selected name
    document.getElementById('barangayName').value = selectedBarangayName; // Update hidden input
});


// Function to get the selected names
function getSelectedData() {
    console.log('Selected Province:', selectedProvinceName);
    console.log('Selected Municipality:', selectedMunicipalityName);
    console.log('Selected Barangay:', selectedBarangayName);

    // Example: Send data to the database
    sendToDatabase({
        province: selectedProvinceName,
        municipality: selectedMunicipalityName,
        barangay: selectedBarangayName,
    });
}

// Example function to simulate database insertion
function sendToDatabase(data) {
    console.log('Inserting into database:', data);
}

// Initialize the dropdown
populateProvinces();
</script>

<script>
    document.getElementById('municipalitySelector').addEventListener('change', function () {
        const selectedMunicipality = this.options[this.selectedIndex].textContent.trim();
        let foundZipCode = "";

        // Iterate through the zipCodes object from zipcode.js
        for (const [zip, municipalityName] of Object.entries(zipCodes)) {
            // Check if the municipality matches
            if (typeof municipalityName === 'string' && municipalityName.includes(selectedMunicipality)) {
                foundZipCode = zip;
                break;
            } else if (Array.isArray(municipalityName)) {
                // Handle municipalities listed as arrays
                if (municipalityName.some(name => name.includes(selectedMunicipality))) {
                    foundZipCode = zip;
                    break;
                }
            }
        }

        // Update the postal-code field
        const postalCodeInput = document.getElementById('postal-code');
        if (foundZipCode) {
            postalCodeInput.value = foundZipCode;
        } else {
            postalCodeInput.value = ""; // Clear if no match
        }
    });
</script>


</body>
</html>
