<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
            position: relative;
        }
        
        /* Header styles */
        header {
            background-color: #333;
            color: white;
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            z-index: 1000;
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .navbar-brand {
            flex-shrink: 0;
            padding-left: 0;
            margin-left: 0;
        }

        .navbar-brand img {
            width: 40px;
            height: 40px;
            border-radius: 40%;
            margin-left: 10px;
        }

        nav {
            display: flex;
            align-items: center;
            margin-left: auto;
            margin-right: 15px;
        }

        .nav-items {
            display: flex;
            gap: 20px;
            margin-right: 20px;
        }

        .nav-items a {
            text-decoration: none;
            color: white;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .nav-items a:hover {
            background-color: #87CEFA;
            text-decoration: none;
            color: black;
        }

        .menu-toggle {
            display: none;
            cursor: pointer;
            padding: 10px;
            background: none;
            border: none;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 1001;
        }

        .menu-toggle span {
            display: block;
            width: 25px;
            height: 3px;
            background-color: white;
            margin: 5px 0;
            transition: all 0.4s ease-in-out;
        }

        @media (max-width: 768px) {
            .menu-toggle {
                display: block;
                margin-left: auto;
                margin-right: 10px;
            }

            .nav-items {
                display: none;
                position: absolute;
                top: 60px;
                right: -100px;
                left: auto;
                width: 200px;
                background-color: #333;
                flex-direction: column;
                padding: 5px 0;
                border-radius: 4px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                gap: 0;
            }

            .nav-items.active {
                display: flex;
                right: 0;
            }

            .nav-items a {
                padding: 15px 20px;
                width: 100%;
                text-align: left;
                border-radius: 0;
            }
        }
        
        .background-blur {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('assets/img/bg.jpg'); /* Path to your background image */
            background-size: cover;
            background-position: center;
            filter: blur(5px); /* Adjust the blur amount as needed */
            z-index: -1; /* Ensure it is behind the content */
            /* Optional: Add an overlay to darken the background if needed */
            background-color: rgba(0, 0, 0, 0.5);
        }

        .background-slideshow {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .background-slideshow::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                to right,
                rgba(0, 0, 0, 0) 0%,
                rgba(0, 0, 0, 0) 30%,
                rgba(0, 0, 0, 0.5) 100%
            );
            z-index: 1;
        }

        .background-slideshow img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0;
            transition: opacity 1s ease-in-out;
        }

        .background-slideshow img.active {
            opacity: 1;
        }

        .container {
            position: relative; /* Ensure the content is positioned above the background-blur */
            width: 85%;
            margin: auto;
            overflow: hidden; /* Ensure that the container does not create extra scrollbars */
        }
        .hero {
            background: url('hero-image.jpg') no-repeat center center/cover;
            color: #fff;
            padding: 5em 0;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .hero::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: -1;
        }
        .hero h1 {
            font-size: 3em;
            margin: 0;
            animation: fadeIn 2s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        section {
            padding: 4em 2em;
        }
        section#who-we-are {
            margin-top: 60px;
            background-color: #e0f7fa;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            padding: 2em;
        }
        .text-content {
            max-width: 60%;
        }
        .image-rectangle {
            width: 330px;
            height: auto;
            position: absolute;
            right: 2em;
            top: 40%;
            transform: translateY(-50%);
            animation: slideDown 1.5s ease-out;
        }
        @keyframes slideDown {
            from {
                transform: translateY(-150%);
                opacity: 0;
            }
            to {
                transform: translateY(auto);
                opacity: 1;
            }
        }
        section#services {
            background-color: #f1f8e9;
        }
        section#team {
            background-color: #FFE4E1;
            padding: 4em 2em;
        }
        section#values {
            background-color: #F5FFFA;
        }
        section#why-choose-us {
            background-color: #e8eaf6;
        }
        section.contact {
            background-color: #333;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        section.contact p {
            text-align: center; /* Centering the paragraph text */
        }
        h2 {
            color: #333;
            font-size: 2.5em;
            border-bottom: 2px solid #333;
            padding-bottom: 0.5em;
            margin-bottom: 1em;
            transition: color 0.3s;
            animation: fadeIn 2s ease-in-out;
        }

        @keyframes slideRight {
            from { 
                transform: translateX(-100%);
                opacity: 0;
            }
            to { 
                transform: translateX(0);
                opacity: 1;
            }
        }
        p {
            margin-bottom: 1em;
            line-height: 1.8;
            animation: slideRight 1.5s ease-out;
            text-align: justify;
        }
        .services ul {
            list-style: none;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 1em;
        }
        .services li {
            background: #fff;
            border: 1px solid #ddd;
            padding: 1em;
            border-radius: 10px;
            transition: transform 0.3s, box-shadow 0.3s;
            flex: 1 1 calc(33.333% - 1em);
        }
        .services li:hover {
            transform: translateY(-10px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .team {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5em;
            justify-content: space-between;
        }
        .team-member {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 1em;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            flex: 1 1 calc(25% - 1.5em); /* Ensure 4 members fit in a row */
            box-sizing: border-box;
            text-align: center;
            /* Set height to make it rectangular */
            height: 350px; /* Adjust height as needed */
            max-width: 220px; /* Adjust max width to maintain rectangle proportion */
            margin: 0 auto; /* Center align each team member */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .team-member-image {
            width: 100%;
            height: 200px; /* Adjust height for image to fit */
            background-color: #f0f0f0;
            border-radius: 10px;
            margin-bottom: 0.5em;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .team-member-image img {
            margin-bottom: 60px;
            max-width: 100%;
            max-height: 130%;
            object-fit: cover;
            border-radius: 10px; /* Optional: Matches the container's border-radius */
        }
        .team-member-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .team-member-info h3 {
            margin: 0.2em 0;
            font-size: 1.1em; /* Adjust font size for compact look */
            color: #333;
        }
        .team-member-info p {
            text-align: center;
            font-size: 0.9em; /* Adjust font size for compact look */
            color: #555;
        }

        .team-member:hover {
            transform: translateY(-10px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .contact a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            border-bottom: 2px solid #fff;
            transition: color 0.3s, border-bottom 0.3s;
        }
        .contact a:hover {
            color: #007BFF;
            border-bottom: 2px solid #007BFF;
        }
        footer {
            background: #222;
            color: #fff;
            text-align: center;
            padding: 0.5em 0;
            font-size: 0.9em;
        }
        footer p {
            text-align: center; /* Centering the footer paragraph text */
        }
    </style>
</head>
<body>
    <header>
        <a class="navbar-brand" href="#">
            <img src="images/logo.jpg" alt="Brand Logo">
        </a>
        
        <nav>
            <button class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <div class="nav-items">
                <a href="index.php">Home</a>
                <a href="aboutus.php">About Us</a>
                <a href="signuppage.php">Signup</a>
            </div>
        </nav>
    </header>

    <div class="background-slideshow">
        <img src="images/bg1.jpg" class="active" alt="Background Image 1">
        <img src="images/bg2.jpg" alt="Background Image 2">
        <img src="images/bg3.jpg" alt="Background Image 3">
    </div>

    <div class="container">

        <section id="who-we-are" class="hero">
            <div class="text-content">
                <h2>About Us</h2>
                <p>We are a passionate team dedicated to delivering exceptional [services/products]. With years of experience and a commitment to excellence, we strive to exceed expectations in every project we undertake.</p>
            </div>
            <img src="assets/img/sample1.jpg" alt="About Us Image" class="image-rectangle">
        </section>

        <section id="services" class="services">
            <h2>What We Do</h2>
            <ul>
                <li><strong>Live Performances:</strong> From high-energy concerts to intimate acoustic sets, our performances are tailored to captivate and engage. We specialize in [mention genres or types of performances, e.g., "rock, jazz, and custom event entertainment"].</li>
                <li><strong>Event Production:</strong> We handle every detail of your event, ensuring a seamless and memorable experience. Our services range from [list key services, e.g., "event planning and coordination to sound and lighting design"].</li>
                <li><strong>Customized Entertainment:</strong> We offer bespoke entertainment solutions designed to fit your specific needs and preferences. Whether it’s a corporate gala, a private party, or a themed event, we bring your vision to life.</li>
            </ul>
        </section>

        <section id="team">
            <h2>Meet Our Team</h2>
            <div class="team">
                <div class="team-member">
                    <div class="team-member-image" style="background-color: #f9c74f;">
                        <img src="assets/img/member.png" alt="Team Member 1">
                    </div>
                    <div class="team-member-info">
                        <h3>Mike Cannon-Brookes</h3>
                        <p>Co-Founder & Co-CEO</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="team-member-image" style="background-color: #90be6d;">
                        <img src="assets/img/member.png" alt="Team Member 2">
                    </div>
                    <div class="team-member-info">
                        <h3>Scott Farquhar</h3>
                        <p>Co-Founder & Co-CEO</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="team-member-image" style="background-color: #f8961e;">
                        <img src="assets/img/member.png" alt="Team Member 3">
                    </div>
                    <div class="team-member-info">
                        <h3>Anu Bharadwaj</h3>
                        <p>President</p>
                    </div>
                </div>
                <div class="team-member">
                    <div class="team-member-image" style="background-color: #f8961e;">
                        <img src="assets/img/member.png" alt="Team Member 4">
                    </div>
                    <div class="team-member-info">
                        <h3>Anu Bharadwaj</h3>
                        <p>President</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="values">
            <h2>Our Values</h2>
            <p>Our values guide everything we do. We believe in [list key values, e.g., "integrity, creativity, and excellence"]. These principles are at the heart of our mission and shape the way we approach every project, big or small.</p>
        </section>

        <section id="why-choose-us">
            <h2>Why Choose Us?</h2>
            <p>Choosing us means selecting a partner who is as passionate about your success as you are. With a proven track record of delivering exceptional experiences, we go above and beyond to exceed your expectations. Our commitment to [mention key differentiators, e.g., "innovation, customer satisfaction, and attention to detail"] sets us apart in the industry.</p>
        </section>
    </div>

    <section class="contact">
        <div class="container">
            <p>Ready to bring your vision to life? <a href="#" data-toggle="modal" data-target="#contactModal">Get in touch</a> with us today!</p>
        </div>
    </section>

    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal" tabindex="-1" role="dialog" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">Contact Admin</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" class="form-control" id="subject" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea class="form-control" id="message" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="submitContactForm()">Send Message</button>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; [Year] [Your Name or Company Name]. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function toggleMenu() {
            const menuToggle = document.querySelector('.menu-toggle');
            const navItems = document.querySelector('.nav-items');
            
            menuToggle.classList.toggle('active');
            navItems.classList.toggle('active');
            
            if (menuToggle.classList.contains('active')) {
                menuToggle.children[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                menuToggle.children[1].style.opacity = '0';
                menuToggle.children[2].style.transform = 'rotate(-45deg) translate(7px, -7px)';
            } else {
                menuToggle.children[0].style.transform = 'none';
                menuToggle.children[1].style.opacity = '1';
                menuToggle.children[2].style.transform = 'none';
            }
        }

        function submitContactForm() {
            const formData = {
                name: $('#name').val(),
                email: $('#email').val(),
                subject: $('#subject').val(),
                message: $('#message').val()
            };

            $.ajax({
                type: 'POST',
                url: 'process_contact.php',
                data: formData,
                success: function(response) {
                    alert('Message sent successfully!');
                    $('#contactModal').modal('hide');
                    $('#contactForm')[0].reset();
                },
                error: function() {
                    alert('There was an error sending your message. Please try again.');
                }
            });
        }
    </script>
</body>
</html>
