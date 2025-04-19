<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selection Page</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f2f2f2;
            font-family: Arial, sans-serif;
        }

        .container {
            display: flex;
            justify-content: space-around;
            width: 80%;
            margin-bottom: 20px;
        }

        .card-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            width: 300px;
            height: 500px;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out, border-color 0.3s ease-in-out;
            border: 2px solid transparent;
        }

        .card-box img {
            width: 250px;
            height: 350px;
            object-fit: cover;
        }

        .card-box h3 {
            color: #fff;
            margin: 10px 0;
            font-size: 18px;
        }

        .card-box button {
            width: 140px;
            height: 35px;
            background-color: #d3d3d3;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease-in-out;
        }

        .card-box button:hover {
            background-color: #bbb;
        }

        .card-box button.selected {
            background-color: #3268a8;
            color: white;
        }

        .card-box:hover {
            transform: scale(1.05);
        }

        .card-box.selected {
            border-color: #3268a8;
        }

        .proceed-container {
            margin-top: 20px;
        }

        .proceed-btn {
            width: 120px;
            height: 45px;
            background-color: #3268a8;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            opacity: 0.5;
            pointer-events: none;
        }

        .proceed-btn.active {
            opacity: 1;
            pointer-events: auto;
        }

        .proceed-btn:hover {
            background-color: #274e85;
        }

        .card-1 { background-color: #FF7F50; }
        .card-2 { background-color: #008B8B; }
        .card-3 { background-color: #7f8c8d; }
    </style>
</head>
<body>

    <div class="container">
        <!-- Card 1 -->
        <div class="card-box card-1">
            <img src="https://via.placeholder.com/140" alt="Image 1">
            <h3>Title 1</h3>
            <button class="select-btn">Select</button>
        </div>
        
        <!-- Card 2 -->
        <div class="card-box card-2">
            <img src="https://via.placeholder.com/140" alt="Image 2">
            <h3>Title 2</h3>
            <button class="select-btn">Select</button>
        </div>
        
        <!-- Card 3 -->
        <div class="card-box card-3">
            <img src="https://via.placeholder.com/140" alt="Image 3">
            <h3>Title 3</h3>
            <button class="select-btn">Select</button>
        </div>
    </div>

    <div class="proceed-container">
        <button class="proceed-btn">Proceed</button>
    </div>

    <script>
        // Get all select buttons and card boxes
        const buttons = document.querySelectorAll('.select-btn');
        const proceedBtn = document.querySelector('.proceed-btn');
        
        buttons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove the 'selected' class from all card-box elements
                document.querySelectorAll('.card-box').forEach(card => {
                    card.classList.remove('selected');
                    card.querySelector('.select-btn').classList.remove('selected');
                });
                
                // Add the 'selected' class to the clicked card-box element
                this.parentElement.classList.add('selected');
                this.classList.add('selected');
                
                // Enable proceed button
                proceedBtn.classList.add('active');
            });
        });
    </script>

</body>
</html>
