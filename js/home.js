document.addEventListener("DOMContentLoaded", function() {
    const navLinks = document.querySelectorAll('.nav-links li a');
    const dropdownBtn = document.getElementById('aboutDropdown'); // Selector for the ABOUT dropdown button

    // Function to activate ABOUT link in navbar
    function activateAboutLink() {
        dropdownBtn.classList.add('active');
    }

    // Function to deactivate ABOUT link in navbar
    function deactivateAboutLink() {
        dropdownBtn.classList.remove('active');
    }

    // Event listener for clicks on navigation links
    navLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            const href = this.getAttribute('href');

            if (href.startsWith('#')) { // Only prevent default for internal links
                event.preventDefault();

                // Remove 'active' class from all links
                navLinks.forEach(navLink => navLink.classList.remove('active'));

                // Add 'active' class to the clicked link
                this.classList.add('active');

                // Scroll to the target section
                const targetId = href.substring(1); // Get target id without '#'
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    window.scrollTo({
                        top: targetSection.offsetTop - 80, // Adjust scroll position as needed
                        behavior: 'smooth' // Smooth scrolling behavior
                    });
                }

                // Check if clicked link is under 'ABOUT' dropdown
                if (this.closest('.about-dropdown')) {
                    activateAboutLink(); // Activate the ABOUT dropdown link in the navbar
                } else {
                    deactivateAboutLink(); // Deactivate the ABOUT dropdown link in the navbar
                }
            } else if (href.includes('FAQ.php')) { // Navigate to FAQ.php
                window.location.href = href;
            }
        });
    });

    // Event listener for clicks on dropdown items under 'ABOUT'
    const aboutDropdownItems = document.querySelectorAll('.about-dropdown a');
    aboutDropdownItems.forEach(item => {
        item.addEventListener('click', function(event) {
            const href = this.getAttribute('href');

            if (href.startsWith('#')) { // Only prevent default for internal links
                event.preventDefault();

                // Activate the ABOUT link in navbar
                activateAboutLink();

                // Remove 'active' class from all links
                navLinks.forEach(navLink => navLink.classList.remove('active'));

                // Add 'active' class to the ABOUT dropdown button itself
                dropdownBtn.classList.add('active');

                // Scroll to the target section
                const targetId = href.substring(1); // Get target id without '#'
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    window.scrollTo({
                        top: targetSection.offsetTop - 80, // Adjust scroll position as needed
                        behavior: 'smooth' // Smooth scrolling behavior
                    });
                }
            }
        });
    });

    // Event listener for clicks on specific ABOUT dropdown links
    const aboutDropdownLinks = document.querySelectorAll('.about-dropdown a[href="#offers"], .about-dropdown a[href="#updates"], .about-dropdown a[href="#team"]');
    aboutDropdownLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            const href = this.getAttribute('href');

            if (href.startsWith('#')) { // Only prevent default for internal links
                event.preventDefault();

                // Activate the ABOUT dropdown button
                activateAboutLink();

                // Remove 'active' class from all links
                navLinks.forEach(navLink => navLink.classList.remove('active'));

                // Add 'active' class to the ABOUT dropdown button itself
                dropdownBtn.classList.add('active');

                // Scroll to the target section
                const targetId = href.substring(1); // Get target id without '#'
                const targetSection = document.getElementById(targetId);
                if (targetSection) {
                    window.scrollTo({
                        top: targetSection.offsetTop - 80, // Adjust scroll position as needed
                        behavior: 'smooth' // Smooth scrolling behavior
                    });
                }
            }
        });
    });

    // Event listener for scroll to highlight current section in navbar
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('section');
        let scrollPos = window.scrollY + 100; // Adjust scroll position as needed

        sections.forEach(section => {
            if (scrollPos >= section.offsetTop && scrollPos < section.offsetTop + section.offsetHeight) {
                let currentId = section.getAttribute('id');
                navLinks.forEach(navLink => navLink.classList.remove('active')); // Remove 'active' class from all links
                const currentLink = document.querySelector(`.nav-links li a[href="#${currentId}"]`);
                if (currentLink) {
                    currentLink.classList.add('active'); // Add 'active' class to the current section link

                    // Check if current section is under 'ABOUT' dropdown
                    if (currentId.startsWith('about')) {
                        activateAboutLink(); // Ensure ABOUT link stays active if any ABOUT section is active
                    } else {
                        deactivateAboutLink(); // Deactivate the ABOUT dropdown link in the navbar
                    }
                }
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", () => {
    const homeContainer = document.querySelector('.home-container');
    const observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                homeContainer.classList.add('animate__animated', 'animate__fadeInRight');
            }
        },
        { threshold: 0.1 }
    );
    observer.observe(homeContainer);
});
