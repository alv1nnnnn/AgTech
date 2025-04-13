<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgTech - FAQ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/faq.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/home.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../images/AgTech-Logo.ico" type="image/x-icon">
</head>
<style>
    .collapsing {
        height: 0;
        overflow: hidden;
        transition: height 0.35s ease;
    }

    .collapse {
        display: none;
    }

    .collapse.show {
        display: block;
    }
</style>

<body>
    
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

    <!-- Copy mo 'to na section -->
    <section class="faq-section section-padding" id="section_4">
    <div class="container">
        <div class="row">

            <div class="col-lg-6 col-12">
                <h2 class="mb-4">Frequently Asked Questions</h2>
            </div>

            <div class="clearfix"></div>

            <div class="col-lg-5 col-12">
                <img src="images/faq_graphic.jpg" class="img-fluid" alt="FAQs">
            </div>

            <div class="col-lg-6 col-12 m-auto">
                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            What is AgTech?
                            </button>
                        </h2>

                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                            AgTech is a platform that connects farmers and traders. <strong> It lets you buy and sell livestock and agricultural products directly, making transactions simple and transparent. 
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                            How do I get started?
                        </button>
                        </h2>

                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                            It’s easy to get started</br> 1.	Sign Up: <span style="font-weight: normal;">Create an account and Login.</span> </br> 2.	Explore: <span style="font-weight: normal;">Browse or upload livestock products to start buying or selling.</span>

                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                            What can I buy on AgTech?
                        </button>
                        </h2>

                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                            You can purchase </br>•	<span style="font-weight: normal;">Livestock like pigs and chicken</span></br>•	<span style="font-weight: normal;">Fresh chicken eggs</span></br>All products come directly from local farmers.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                            How do I manage trade offers?
                        </button>
                        </h2>

                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                            AgTech makes managing trade offers simple. As a registered user, you can:</br>• Farmers POV: <span style="font-weight: normal;">Create and update your product listings with pricing and availability.</span></br>• Traders POV: <span style="font-weight: normal;">Submit offers or inquiries directly to farmers.</span>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                            Is product quality guaranteed?
                        </button>
                        </h2>

                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                            We require farmers to meet quality standards for all listed products. However, we recommend contacting the seller to confirm specific details.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSix">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                            How can I contact support?
                        </button>
                        </h2>

                        <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                            Need help? Our support team is ready to assist!</br> <span style="font-weight: normal;">You can visit the <strong>Contact Us</strong> page</span></br><span style="font-weight: normal;">Email us at </span>theagtechteam@gmail.com
                            </div>
                        </div>
                    </div>

                </div>
            </div>

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

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const accordionButtons = document.querySelectorAll(".accordion-button");

        accordionButtons.forEach((button) => {
            button.addEventListener("click", function () {
                const targetId = button.getAttribute("data-bs-target");
                const targetElement = document.querySelector(targetId);

                if (targetElement.classList.contains("collapsing")) {
                    return; // Prevent multiple animations
                }

                // Close other panels
                document.querySelectorAll(".accordion-collapse.show").forEach((openElement) => {
                    if (openElement !== targetElement) {
                        closePanel(openElement);
                    }
                });

                // Toggle the current panel
                if (targetElement.classList.contains("show")) {
                    closePanel(targetElement);
                } else {
                    openPanel(targetElement);
                }
            });
        });

        function closePanel(element) {
            element.style.height = `${element.scrollHeight}px`; // Set initial height for transition
            requestAnimationFrame(() => {
                element.classList.add("collapsing");
                element.classList.remove("collapse", "show");
                element.style.height = "0";
            });

            element.addEventListener(
                "transitionend",
                () => {
                    element.classList.remove("collapsing");
                    element.classList.add("collapse");
                    element.style.height = "";
                },
                { once: true }
            );
        }

        function openPanel(element) {
            element.classList.add("collapsing");
            element.classList.remove("collapse");
            element.style.height = "0";

            requestAnimationFrame(() => {
                element.style.height = `${element.scrollHeight}px`;
            });

            element.addEventListener(
                "transitionend",
                () => {
                    element.classList.remove("collapsing");
                    element.classList.add("collapse", "show");
                    element.style.height = "";
                },
                { once: true }
            );
        }
    });
</script>
</html>
