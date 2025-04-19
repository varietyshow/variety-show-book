<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landing Page</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <style>
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
            }

            .nav-items a {
                color: white;
                padding: 12px 20px;
                width: 100%;
                text-align: left;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                white-space: nowrap;
                font-size: 16px;
            }
        }

        .menu-toggle.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }

        /* Main content styles */
        .main-content {
            margin-top: 60px;
            min-height: 100vh;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
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
                rgba(0, 0, 0, 2.0) 100%
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

        body, html {
            height: 100%;
            margin: 0;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.9);
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 139, 0.5);
            width: 100%;
            max-width: 350px;
            text-align: center;
            box-sizing: border-box;
            margin: 20px;
        }

        h1 {
            margin-bottom: 20px;
            color: #222;
            text-shadow:
                1px 1px 0 #fff,
                -1px -1px 0 #fff,
                1px -1px 0 #fff,
                -1px 1px 0 #fff;
        }

        .account-type {
            margin-bottom: 20px;
        }

        .account-type a {
            display: block;
            width: 100%;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            text-align: center;
            background-color: #6c757d;
            transition: background-color 0.3s, transform 0.3s;
            box-sizing: border-box;
        }

        .account-type a:hover {
            background-color: #004494;
            transform: scale(1.05);
        }

        .navbar-brand img {
            width: 40px;
            height: 40px;
            border-radius: 40%;
            margin-left: 10px;
        }

        /* Add media queries for desktop view */
        @media (min-width: 769px) {
            .login-container {
                width: 380px;
                padding: 30px;
                margin-right: 50px;
            }

            .account-type a {
                font-size: 16px;
                padding: 15px;
                margin: 12px 0;
            }

            h1 {
                font-size: 2.2rem;
            }
        }

        /* Add media queries for mobile view */
        @media (max-width: 768px) {
            .main-content {
                justify-content: center;
                padding-right: 0;
            }
            
            .login-container {
                width: 85%;
                max-width: 300px;
                padding: 20px;
                margin: 15px;
            }

            .account-type a {
                padding: 12px;
                margin: 10px 0;
                font-size: 16px;
            }

            h1 {
                font-size: 2rem;
            }
        }

        /* Add media queries for very small devices */
        @media (max-width: 320px) {
            .login-container {
                width: 95%;
                padding: 20px;
                margin: 10px;
            }

            .account-type a {
                padding: 10px;
                font-size: 14px;
            }

            h1 {
                font-size: 1.8rem;
            }
        }

        .welcome-message {
            color: white;
            position: relative;
            z-index: 2;
            margin-left: 8%;
            max-width: 500px;
            text-align: center;
        }

        .welcome-message h2 {
            font-size: 3.5rem;
            font-weight: bold;
            text-shadow: 
                -2px -2px 0 #000,  
                 2px -2px 0 #000,
                -2px  2px 0 #000,
                 2px  2px 0 #000,
                -2px  0   0 #000,
                 2px  0   0 #000,
                 0   -2px 0 #000,
                 0    2px 0 #000;
            margin-bottom: 20px;
            line-height: 1.2;
            animation: textColorChange 20s infinite;
        }

        .welcome-message p {
            font-size: 12px;
            line-height: 1.6;
            text-shadow: 
                -1px -1px 0 #000,  
                 1px -1px 0 #000,
                -1px  1px 0 #000,
                 1px  1px 0 #000;
            margin-bottom: 30px;
        }

        /* Add this to your mobile media queries (@media (max-width: 768px)) */
        @media (max-width: 768px) {
            .welcome-message {
                display: none; /* Hide on mobile to prevent cluttering */
            }
        }

        /* Add this to your existing CSS styles section */
        @keyframes textColorChange {
            0% { color: #FF6B6B; }
            25% { color: #4ECDC4; }
            50% { color: #FFD93D; }
            75% { color: #95E1D3; }
            100% { color: #FF6B6B; }
        }

        .welcome-message h2 {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        #random-emoji {
            display: inline-block;
            transition: opacity 0.5s ease-in-out;
            font-size: 3rem;
            position: absolute;
            opacity: 0;
        }

        /* Cardinal directions */
        .emoji-n { top: -50px; left: 50%; transform: translateX(-50%); }
        .emoji-ne { top: -40px; right: -40px; }
        .emoji-e { right: -60px; top: 50%; transform: translateY(-50%); }
        .emoji-se { bottom: -90px; right: -40px; }
        .emoji-s { bottom: -90px; left: 50%; transform: translateX(-50%); }
        .emoji-sw { bottom: -90px; left: -40px; }
        .emoji-w { left: -60px; top: 50%; transform: translateY(-50%); }
        .emoji-nw { top: -40px; left: -40px; }

        /* Intercardinal directions */
        .emoji-nne { top: -45px; right: -20px; }
        .emoji-ene { top: -20px; right: -45px; }
        .emoji-ese { bottom: -90px; right: -45px; }
        .emoji-sse { bottom: -90px; right: -20px; }
        .emoji-ssw { bottom: -90px; left: -20px; }
        .emoji-wsw { bottom: -90px; left: -45px; }
        .emoji-wnw { top: -20px; left: -45px; }
        .emoji-nnw { top: -45px; left: -20px; }

        .emoji-visible {
            opacity: 1 !important;
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

    <div class="container-fluid main-content">
        <div class="background-slideshow">
            <img src="images/bg1.jpg" alt="Background 1" class="active">
            <img src="images/bg2.jpg" alt="Background 2">
            <img src="images/bg3.jpg" alt="Background 3">
            <img src="images/bg4.jpg" alt="Background 4">
        </div>
        
        <div class="welcome-message">
            <h2>
                <span id="random-emoji"></span>
                <span class="heading-text">"Entertainment that Sparks Joy!"</span>
            </h2>
            <p>Looking to ignite your event with fun and excitement! Our variety entertainers offer an exhilarating blend of talent, creativity, and charm that guarantees enjoyment for everyone!</p>
        </div>
        
        <div class="login-container">
            <h1>Login as:</h1>
            <div class="account-type">
                <a href="customer/customer-loginpage.php">Customer</a>
                <a href="entertainer/entertainer-loginpage.php">Entertainer</a>
                <a href="admin/admin-loginpage.php">Manager</a>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.7/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function toggleMenu() {
            const menuToggle = document.querySelector('.menu-toggle');
            const navItems = document.querySelector('.nav-items');
            
            menuToggle.classList.toggle('active');
            navItems.classList.toggle('active');

            const isExpanded = menuToggle.classList.contains('active');
            menuToggle.setAttribute('aria-expanded', isExpanded);
        }

        document.addEventListener('click', function(event) {
            const menuToggle = document.querySelector('.menu-toggle');
            const navItems = document.querySelector('.nav-items');
            
            if (!event.target.closest('.menu-toggle') && 
                !event.target.closest('.nav-items') && 
                navItems.classList.contains('active')) {
                menuToggle.classList.remove('active');
                navItems.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const menuToggle = document.querySelector('.menu-toggle');
                const navItems = document.querySelector('.nav-items');
                
                menuToggle.classList.remove('active');
                navItems.classList.remove('active');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
        });

        function changeBackground() {
            const images = document.querySelectorAll('.background-slideshow img');
            const heading = document.querySelector('.welcome-message h2');
            const colors = ['#FF6B6B', '#4ECDC4', '#FFD93D', '#95E1D3'];
            let currentIndex = 0;

            setInterval(() => {
                // Remove active class from all images
                images.forEach(img => img.classList.remove('active'));
                
                // Add active class to next image
                currentIndex = (currentIndex + 1) % images.length;
                images[currentIndex].classList.add('active');
                
                // Change heading color with text shadow for better visibility
                heading.style.color = colors[currentIndex];
                heading.style.textShadow = `2px 2px 4px rgba(0, 0, 0, 0.5)`;
            }, 5000); // Change image and color every 5 seconds
        }

        function updateEmoji() {
            const emojis = ['🎭', '🎪', '🎩', '🎨', '🎬', '🎵', '🎸', '🎺', '🎷', '🎹', '🎮', '🎯', '🎪', '🎭', '🎨'];
            const positions = [
                'emoji-n', 'emoji-ne', 'emoji-e', 'emoji-se', 
                'emoji-s', 'emoji-sw', 'emoji-w', 'emoji-nw',
                'emoji-nne', 'emoji-ene', 'emoji-ese', 'emoji-sse',
                'emoji-ssw', 'emoji-wsw', 'emoji-wnw', 'emoji-nnw'
            ];
            const emojiElement = document.getElementById('random-emoji');
            
            setInterval(() => {
                // Fade out the emoji
                emojiElement.classList.remove('emoji-visible');
                
                setTimeout(() => {
                    // Remove previous position class
                    positions.forEach(pos => emojiElement.classList.remove(pos));
                    
                    // Add new position and emoji
                    const randomEmoji = emojis[Math.floor(Math.random() * emojis.length)];
                    const randomPosition = positions[Math.floor(Math.random() * positions.length)];
                    
                    emojiElement.textContent = randomEmoji;
                    emojiElement.classList.add(randomPosition);
                    
                    // Make visible in new position
                    requestAnimationFrame(() => {
                        emojiElement.classList.add('emoji-visible');
                    });
                }, 500);
            }, 3000);
        }

        // Make sure both functions are called when the page loads
        document.addEventListener('DOMContentLoaded', () => {
            changeBackground();
            updateEmoji();
        });
    </script>
</body>

</html>