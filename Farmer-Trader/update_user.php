<?php

header('Content-Type: application/json');

session_start();
include '../Connection/connection.php'; // Include your database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch existing data to preserve unmodified fields
    $userId = $_SESSION['user_id'];
    $fetchQuery = "SELECT * FROM user WHERE user_id = ?";
    $stmt = $conn->prepare($fetchQuery);
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found.']);
        exit;
    }

    $existingData = $result->fetch_assoc();

    // Retrieve data from POST request
    $firstName = $_POST['first_name'] ?: $existingData['first_name'];
    $lastName = $_POST['last_name'] ?: $existingData['last_name'];
    $phone = $_POST['phone'] ?: $existingData['phone_number'];
    $email = $_POST['email'] ?: $existingData['email'];
    $dob = $_POST['dob'] ?: $existingData['birthdate'];
    $age = $_POST['age'] ?: $existingData['age'];
    $province = $_POST['province'] ?: $existingData['province'];
    $municipality = $_POST['municipality'] ?: $existingData['municipality'];
    $barangay = $_POST['barangay'] ?: $existingData['barangay'];
    $postalCode = $_POST['postal_code'] ?: $existingData['postal_code'];

    // Handle profile picture upload
    $profilePath = $existingData['profile'];
    if (isset($_FILES['profile']) && $_FILES['profile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profile_pictures/';
        $profilePath = $uploadDir . basename($_FILES['profile']['name']);

        if (!move_uploaded_file($_FILES['profile']['tmp_name'], $profilePath)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload profile picture.']);
            exit;
        }
    }

    // Update the database
    $updateQuery = "UPDATE user SET 
        first_name = ?, last_name = ?, phone_number = ?, email = ?, birthdate = ?, age = ?, 
        province = ?, municipality = ?, barangay = ?, postal_code = ?, profile = ? WHERE user_id = ?";

    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param(
        'sssssssssssi',
        $firstName, $lastName, $phone, $email, $dob, $age,
        $province, $municipality, $barangay, $postalCode, $profilePath, $userId
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'profile' => $profilePath,
            'data' => [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => $email,
                'dob' => $dob,
                'age' => $age,
                'province' => $province,
                'municipality' => $municipality,
                'barangay' => $barangay,
                'postal_code' => $postalCode,
            ],
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update profile in the database.']);
    }
    $stmt->close();
    $conn->close();
}
?>
