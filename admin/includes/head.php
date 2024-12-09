<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="../vendor/twbs/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        /* Ensure the body takes full height */
        html,
        body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* Main content should take up remaining space */
        .container {
            flex: 1;
            padding: 20px;
        }

        /* Chart container styles */
        .chart-container {
            min-height: 300px;
            width: 100%;
            position: relative;
        }

        /* Card hover effect */
        .card {
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        /* Custom colors for charts */
        .apexcharts-tooltip {
            background: rgba(0, 0, 0, 0.8) !important;
            color: #fff !important;
        }

        /* Image Container to control size */
        .image-container {
            width: 100%;
            height: 200px;
            /* Fixed height */
            overflow: hidden;
            /* Ensures image doesn't overflow */
            position: relative;
        }

        /* Fixed size for project images */
        .project-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Ensures the image fills the container */
            transition: transform 0.3s ease;
            /* Adds a smooth transition */
        }

        /* Hover effect to zoom image */
        .project-image:hover {
            transform: scale(1.1);
            /* Slight zoom on hover */
        }

        /* Optional: Add lightbox effect to enlarge images */
        .project-image:active {
            transform: scale(1.2);
            /* Zooms image more when clicked */
        }

        /* Sticky footer style */
        .footer {
            background-color: #f8f9fa;
            text-align: center;
            padding: 10px;
            position: relative;
            bottom: 0;
            width: 100%;
            margin-top: auto;
        }

        .footer a {
            text-decoration: none;
            color: #007bff;
        }
    </style>
</head>

<body class="d-flex flex-column bg-light"></body>