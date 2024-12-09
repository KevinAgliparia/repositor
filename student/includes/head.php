<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="../vendor/twbs/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="../vendor/twbs/bootstrap-icons/font/bootstrap-icons.css">

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
        .main-content {
            flex-grow: 1;
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

<body class="bg-light">