<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - Meet the Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/home.css?v=<?php echo time(); ?>">
    <script src="js/home.js"></script>
    <link rel="icon" href="images/AgTech-Logo.ico" type="image/x-icon">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="logo">
        <img src="images/AgTech-Logo.png" alt="Logo">
        <p class="title">AgTech</p>
    </div>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto nav-links">
            <li class="nav-item active">
                <a href="index.php" class="nav-link">Home</a>
            </li>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="aboutDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Our Company</a>
                <div class="dropdown-menu" aria-labelledby="aboutDropdown">
                    <a class="dropdown-item" href="About.php">About Us</a>
                    <a class="dropdown-item" href="meet-the-team.php">Meet the Team</a>
                </div>
            </li>
            <li class="nav-item">
                <a href="FAQ.php" class="nav-link">FAQ</a>
            </li>
            
            <li class="nav-item">
                <a href="Contact.php" class="nav-link">Contact Us</a>
            </li>
            
            <li class="nav-item">
                <a href="Login/Login.php" class="nav-link">Login</a>
            </li>
        </ul>
    </div>
</nav>

 <section id="team" class="section team">
        <h2><span class="extra-light">Meet The Team</span></h2>
        <p class="team-description">A group of Information Technology students from Bicol University, College of Science. The Project Manager leads the efforts, the Front-end Developer creates the user interface, the System Analyst ensures smooth operations, and the Back-end Developer handles the server-side logic. The team bridges farmers and traders through AgTech.</p>
        <div class="team-container">
            <div class="team-member">
                <div class="team-box">
                    <img src="images\trina.png" alt="Trina H.">
                </div>
                <p class="team-name">Trina Chariz Hibo</p>
                <p class="team-role">Project Manager</p>
            </div>
            <div class="team-member">
                <div class="team-box">
                    <img src="images\rachelle.png" alt="Rachelle M.">
                </div>
                <p class="team-name">Rachelle Manila.</p>
                <p class="team-role">Front-end Developer</p>
            </div>
            <div class="team-member">
                <div class="team-box">
                    <img src="images\hazel.png" alt="Hazel M.">
                </div>
                <p class="team-name">Hazel Marqueses</p>
                <p class="team-role">Systems Analyst</p>
            </div>
            <div class="team-member">
                <div class="team-box">
                    <img src="images\alvin.png" alt="Alvin Nario">
                </div>
                <p class="team-name">Alvin Nario</p>
                <p class="team-role">Back-end Developer</p>
            </div>
        </div>
    </section>
    
 <footer>
        <div class="footer-content">
            <div class="footer-left">
                <div class="footer-logo">
                    <img src="images/AgTech-Logo.png" alt="AgTech Logo">
                    <p class="footer-title">AgTech</p>
                </div>
                <div class="footer-text">
                    <p>AgTech is dedicated to connecting farmers and buyers smoothly. We offer a platform where you can buy livestock such as pigs, chicken eggs, and chickens directly from local farmers in Albay Province, ensuring freshness and supporting sustainable agriculture practices.</p>
                </div>
            </div>
            <div class="footer-right">
                <h3>Quick Links</h3>
                <div class="link-columns">
                    <div class="link-column">
                        <ul>
                            <li><a href="About.php">About Us</a></li>
                             <li><a href="meet-the-team.php">Meet the Team</a></li>
                        </ul>
                    </div>
                    <div class="link-column">
                        <ul>
                            <li><a href="FAQ.php">Frequently Asked Questions</a></li>
                            <li><a href="Contact.php">Contact Us</a></li>
                        </ul>
                    </div>
                </div>
                                <div class="footer-social">
                        <i class="bi bi-facebook social-icon"></i>
                        <i class="bi bi-instagram social-icon"></i>
                        <i class="bi bi-twitter-x social-icon"></i>
                        <i class="bi bi-youtube social-icon"></i>
                </div>
            </div>
        </div>
        <hr>
        <div class="footer-bottom">
            <p>&copy; 2024. All Rights Reserved. Designed by AgTech Team</p>
            <div class="footer-links">
                <a href="#">Terms & Conditions</a>
                <a href="#">Privacy Policy</a>
            </div>
        </div>
    </footer>

</body>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</html>