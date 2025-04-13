<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="managepassword.css">
</head>
<body>
    <div class="modal-overlay hidden" id="modal-overlay"></div>
    <div class="modal hidden" id="password-modal">
        <div class="modal-header">
            <h1 class="modal-title">Manage Account Password</h1>
            <span class="close-btn" id="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <div id="error-message" class="alert alert-danger" style="display: none;"></div>
            <div id="success-message" class="alert alert-success" style="display: none;">Password changed successfully.</div>

            <form id="password-form" class="space-y-6">
                <div class="space-y-4">
                    <div>
                        <label for="current-password" class="label">CURRENT PASSWORD</label>
                        <input
                            id="current-password"
                            name="current_password"
                            type="password"
                            required
                            class="input"
                        />
                    </div>
                    <div>
                        <label for="new-password" class="label">NEW PASSWORD</label>
                        <input
                            id="new-password"
                            name="new_password"
                            type="password"
                            required
                            class="input"
                        />
                    </div>
                    <div>
                        <label for="confirm-password" class="label">CONFIRM NEW PASSWORD</label>
                        <input
                            id="confirm-password"
                            name="confirm_password"
                            type="password"
                            required
                            class="input"
                        />
                    </div>
                </div>
                <hr class="divider"/>
                <div class="password-requirements">
                    <h2>Required Password Format:</h2>
                    <ul>
                        <li id="length-requirement">- Must be 8 characters or more</li>
                        <li id="uppercase-requirement">- At least one uppercase character</li>
                        <li id="lowercase-requirement">- At least one lowercase character</li>
                        <li id="number-requirement">- At least one number</li>
                        <li id="symbol-requirement">- At least one symbol</li>
                    </ul>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn">Save changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('password-modal');
        const overlay = document.getElementById('modal-overlay');
        const closeModal = document.getElementById('close-modal');

        // Open modal
        document.body.addEventListener('click', () => {
            modal.classList.remove('hidden');
            overlay.classList.remove('hidden');
        });

        // Close modal
        closeModal.addEventListener('click', () => {
            modal.classList.add('hidden');
            overlay.classList.add('hidden');
        });

        overlay.addEventListener('click', () => {
            modal.classList.add('hidden');
            overlay.classList.add('hidden');
        });
    </script>
</body>
</html>
