<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - 404</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
            }
        .main-container {
            display: flex;
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            height: 90vh; /* Full viewport height */
        }
        .container {
            text-align: center;
        }
        h1 {
            font-size: 15rem;
            margin: 0;
            color: #2D4A36;
        }
        p {
            font-size: 1.2rem;
            margin: 20px 0;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            font-size: 1rem;
            color: #fff;
            background-color: #2D4A36;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        a:hover {
            background-color: green;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 10rem;
            }
            .main-container {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 80vh;
        }
        }
    </style>
</head>
<body>
    <div class="main-container">
    <div class="container">
        <h1>404</h1>
        <p>Oops! The page you are looking for does not exist.</p>
        <a href="javascript:history.back()">Go Back</a>
    </div>
    </div>
</body>
</html>
