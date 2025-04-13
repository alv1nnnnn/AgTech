<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="../css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div id="profile-modal" class="modal">
        <div class="modal-content">
            <button class="close-button">&times;</button>
            <form id="profile-form">
            <div class="profile-container">
            <div class="profile-header">
                <div class="profile-image-container">
                    <img id="profile-img" src="default-profile.png" alt="Profile Picture">
                </div>
                <label class="upload-label" for="profile-upload">
                        <i class="fas fa-camera"></i>
                </label>
                <input type="file" id="profile-upload" style="display:none;" />
                <div class="profile-info">
                    <h2>Alvin S. Nario
                    <a href="editprofile.php" class="edit-profile-btn">Edit Profile</a>
                    </h2>
                    <p class="info-item">
                        <i class="fas fa-phone"></i> 09774246291 <span class="separator">|</span> 
                        <i class="fas fa-map-marker-alt"></i> Tagas, Daraga
                    </p>
                </div>
            </div>
        </div>

        <hr class="info-divider">
        
                <!-- Personal Information Header -->
                <div class="personal-info-header">
                    <h3>Personal Information</h3>
                </div>

                <!-- Personal Information Box -->
                <div class="profile-info-container">
                    <div class="personal-info">
                        <label class="info-item">
                            <i class="fas fa-calendar-alt"></i> Date of Birth: 
                            <span class="info-value">2002-10-21</span>
                        </label>
                        <label class="info-item">
                            <i class="fas fa-calendar-day"></i> Age: 
                            <span class="info-value">22</span>
                        </label>
                        <label class="info-item">
                            <i class="fas fa-envelope"></i> Email: 
                            <span class="info-value">alvinnario56@gmail.com</span>
                        </label>
                        <label class="info-item">
                            <i class="fas fa-map-marker-alt"></i> Complete Address: 
                            <span class="info-value">Tagas, Daraga</span>
                        </label>
                    </div>

                <div class="form-actions">
                    <button type="submit" class="save-button">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dobInput = document.getElementById('dob');
            const ageInput = document.getElementById('age');

            // Calculate and display age based on the selected date of birth
            dobInput.addEventListener('input', () => {
                const dob = new Date(dobInput.value);
                const today = new Date();
                let age = today.getFullYear() - dob.getFullYear();
                const monthDiff = today.getMonth() - dob.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                    age--;
                }
                ageInput.value = isNaN(age) ? "" : age;
            });

            // Handle form submission
            const form = document.getElementById('profile-form');
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);

                const response = await fetch('manageprofile.php', {
                    method: 'POST',
                    body: formData,
                });

                const result = await response.json();
                alert(`${result.title}: ${result.description}`);
            });

            // Close modal
            const closeButton = document.querySelector('.close-button');
            const cancelButton = document.querySelector('.cancel-button');
            const closeModal = () => {
                alert("Profile editing has been canceled.");
                window.history.back();
            };
            closeButton.addEventListener('click', closeModal);
            cancelButton.addEventListener('click', closeModal);
        });
    </script>
    <script>
            document.getElementById("profile-upload").addEventListener("change", function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Update the profile image container with the uploaded file
                    document.getElementById("profile-img").src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
