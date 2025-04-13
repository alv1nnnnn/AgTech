<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="css/home.css?v=<?php echo time(); ?>">
    <script src="js/home.js"></script>
    <link rel="icon" href="images/AgTech-Logo.ico" type="image/x-icon">
</head>
<body>

<!-- Top Loader Bar -->
<div id="top-loader" style="
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background-color: #198754; /* Bootstrap success green */
    z-index: 2000;
    animation: loader-progress 2s ease-in-out infinite;
    display: none;
"></div>
    
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="logo" onclick="window.location.href='index.php';" style="cursor: pointer;">
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

    <section id="home" class="section home">
    <div class="home-container animate__animated animate__fadeInRight">
        <div class="content">
            <div class="agtech-title">
                <img src="images/AgTech-Farmer-Trader.png" alt="Logo" class="home-logo">
            </div>
            <h1 class="livestock-label">Unlock new opportunities with AgTech</h1>
            <p class="agtech-text">Register now to connect with livestock farmers and traders. Simplify transactions, access real-time market insights, and promote transparency for a sustainable future in livestock farming.</p>
            <div class="buttons">
                <a href="Verification/Register.php" class="button register">REGISTER</a>
            </div>
        </div>
    </div>
</section>


<section id="about" class="section about">
    <div class="about-agtech-content">
        <div class="about-agtech-title animate__animated">
            <img src="images/AgTech-Logo.png" alt="Logo" class="">
            <h1>AgTech</h1>
        </div>
        <h1 class="about-livestock-label animate__animated">
            Bringing <span>Farmers and Traders</span><br>Together to Spotlight Local Livestock in the Market
        </h1>
        <p class="about-agtech-text animate__animated">
            “Pinagsasaro an parasaka asin parasurug nin hayop para iangat an lokal na livestock sa merkado.
            Huli sa satong pagkasararo, nagiging mas halangkaw an oportunidad sa agrikultura.
            Padagos tang iangat an lokal para sa satong gabos na progreso.”
        </p>
        <div class="about-buttons animate__animated">
            <a href="About.php" class="button register">Learn More</a>
        </div>
    </div>
</section>

<section id="offers" class="section offers">
    <div class="offers-header animate__animated">
        <h1>Our Products</h1>
    </div>
    <p class="product-text animate__animated">
        <p class="text-label animate__animated">The product range includes:</p>
        <p class="swines-des animate__animated"><strong>Swines: </strong>Raised on farms with care, swines are available in various breeds and sizes to suit different preferences and needs.</p>
        <p class="chickens-des animate__animated"><strong>Chickens: </strong>Offering both free-range and conventionally raised poultry, there are options to meet a range of requirements.</p>
        <p class="eggs-des animate__animated"><strong>Fresh Eggs: </strong>Sourced from chickens cared for by local farmers, eggs are available in different quantities, catering to both personal and commercial needs.</p>
    </p>
    <div class="offers-container">
        <div class="offer animate__animated">
            <img src="images/pig.jpg" alt="Pig">
            <div class="description-container animate__animated">
                <h5>Swines</h5>
            </div>
        </div>
        <div class="offer animate__animated">
            <img src="images/chicken.jpg" alt="Chickens">
            <div class="description-container animate__animated">
                <h5>Chickens</h5>
            </div>
        </div>
        <div class="offer animate__animated">
            <img src="images/eggs.jpg" alt="Eggs">
            <div class="description-container animate__animated">
                <h5>Eggs</h5>
            </div>
        </div>
    </div>
</section>

<section id="updates" class="section updates">
    <div class="updates-content animate__animated">
        <h1>How to use AgTech App?</h1>
        <iframe 
        src="https://www.youtube.com/embed/OVT4AiLxpr8" 
        title="YouTube video player" 
        frameborder="0" 
        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
        allowfullscreen>
    </iframe>
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
                    <p>AgTech is a platform designed to connect farmers and traders conveniently. It enables users to purchase livestock, including pigs, chickens, and chicken eggs, directly from local farmers in Albay Province. By fostering direct connections, AgTech supports local livestock market visibility, transparency, and opportunities.</p>
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
                <a href="Terms-&-Condition.php">Terms & Conditions</a>
                <a href="PrivacyPolicy.php">Privacy Policy</a>
            </div>
        </div>
    </footer>

</body>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const topLoader = document.getElementById('top-loader');
    topLoader.style.display = 'block';
});

window.addEventListener('load', function () {
    const topLoader = document.getElementById('top-loader');
    setTimeout(() => {
        topLoader.style.display = 'none';
    }, 500); // delay to allow smooth transition
});
</script>


<script>
// Show loader during page load
document.addEventListener('DOMContentLoaded', function () {
    const loader = document.getElementById('loader');
    const overlay = document.getElementById('loader-overlay');

    overlay.style.display = 'block'; // Show overlay
    loader.style.display = 'block'; // Show loader
});

// Hide loader after all resources have loaded
window.addEventListener('load', function () {
    const loader = document.getElementById('loader');
    const overlay = document.getElementById('loader-overlay');

    setTimeout(() => {
        overlay.style.display = 'none'; // Hide overlay
        loader.style.display = 'none'; // Hide loader
    }, 300); // Optional small delay for smoother transition
});

// Show loader during navigation click
document.querySelector('.logo').addEventListener('click', function () {
    const loader = document.getElementById('loader');
    const overlay = document.getElementById('loader-overlay');

    overlay.style.display = 'block'; // Show overlay
    loader.style.display = 'block'; // Show loader
    window.location.href = 'index.php'; // Redirect
});

document.addEventListener("DOMContentLoaded", () => {
    // Select all sections to observe, except the 'home' section
    const sections = document.querySelectorAll('.section:not(#home)');  // Exclude the home section

    // Create an IntersectionObserver to observe when sections come into view
    const observer = new IntersectionObserver(
        ([entry]) => {
            // When the section is in view, trigger the animation
            if (entry.isIntersecting) {
                // Select all child elements with the animate__animated class inside the section
                const animatedChildren = entry.target.querySelectorAll('.animate__animated');
                
                // Add the fadeInUp animation class to all child elements
                animatedChildren.forEach(child => {
                    child.classList.add('animate__fadeInUp');
                });

                // Unobserve the section once it's animated (optional, to improve performance)
                observer.unobserve(entry.target);
            }
        },
        { threshold: 0.2 } // Trigger when at least 10% of the section is visible
    );

    // Observe each section
    sections.forEach(section => {
        observer.observe(section);
    });
});
</script>

<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
</html>
