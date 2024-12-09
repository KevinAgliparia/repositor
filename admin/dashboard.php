<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Fetch analytics data
$totalProjects = $conn->query("SELECT COUNT(*) AS total FROM projects")->fetch_assoc()['total'];

$projectsByDate = $conn->query("
    SELECT DATE(upload_date) AS upload_date, COUNT(*) AS total 
    FROM projects 
    GROUP BY DATE(upload_date) 
    ORDER BY upload_date DESC
    LIMIT 7
")->fetch_all(MYSQLI_ASSOC);

$topMembers = $conn->query("
    SELECT pm.member_name, COUNT(pm.project_id) AS total_projects 
    FROM project_members pm
    GROUP BY pm.member_name 
    ORDER BY total_projects DESC 
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

$projectsByUsers = $conn->query("
    SELECT u.username, COUNT(p.project_id) AS total_projects 
    FROM users u
    LEFT JOIN projects p ON u.user_id = p.user_id
    GROUP BY u.username
    HAVING total_projects > 0
    ORDER BY total_projects DESC
")->fetch_all(MYSQLI_ASSOC);

// Debug data
$debug = [
    'projectsByDate' => $projectsByDate,
    'topMembers' => $topMembers,
    'projectsByUsers' => $projectsByUsers
];
?>


<?php include 'includes/head.php'; ?>
<?php include 'includes/navbar.php'; ?>

<div class="container mt-5">
    <h1 class="mb-4 text-center">Dashboard</h1>

    <!-- Debug Info -->
    <div class="debug-info" style="display: none;">
        <pre><?php echo json_encode($debug, JSON_PRETTY_PRINT); ?></pre>
    </div>

    <!-- Overview Section -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h5 class="text-muted mb-3">
                        <i class="bi bi-diagram-3-fill text-primary me-2"></i>Total Projects
                    </h5>
                    <h2 class="display-5 text-primary fw-bold"><?php echo $totalProjects; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row">
        <!-- Projects by Upload Date -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Projects by Upload Date (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <div id="projectsByDateChart" class="chart-container"></div>
                </div>
            </div>
        </div>

        <!-- Top Members -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Top Members by Participation</h5>
                </div>
                <div class="card-body">
                    <div id="topMembersChart" class="chart-container"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Projects by Users -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Projects Distribution by Users</h5>
                </div>
                <div class="card-body">
                    <div id="projectsByUsersChart" class="chart-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show debug info in console
    console.log('Debug Data:', <?php echo json_encode($debug); ?>);

    // Format dates for better display
    const formatDates = dates => dates.map(date => {
        const d = new Date(date);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });

    try {
        // Data for Projects by Upload Date
        const projectsByDate = {
            categories: formatDates(<?php echo json_encode(array_column($projectsByDate, 'upload_date')); ?>),
            series: <?php echo json_encode(array_column($projectsByDate, 'total')); ?>
        };
        console.log('Projects by Date:', projectsByDate);

        // Line Chart for Projects by Date
        new ApexCharts(document.querySelector("#projectsByDateChart"), {
            chart: {
                type: 'area',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            series: [{
                name: 'Projects',
                data: projectsByDate.series
            }],
            xaxis: {
                categories: projectsByDate.categories,
                labels: {
                    style: {
                        colors: '#6c757d'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#6c757d'
                    }
                }
            },
            dataLabels: {
                enabled: true
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            colors: ['#0d6efd'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: value => `${value} Project${value !== 1 ? 's' : ''}`
                }
            }
        }).render();

        // Data for Top Members
        const topMembers = {
            categories: <?php echo json_encode(array_column($topMembers, 'member_name')); ?>,
            series: <?php echo json_encode(array_column($topMembers, 'total_projects')); ?>
        };
        console.log('Top Members:', topMembers);

        // Bar Chart for Top Members
        new ApexCharts(document.querySelector("#topMembersChart"), {
            chart: {
                type: 'bar',
                height: 300,
                toolbar: {
                    show: false
                }
            },
            series: [{
                name: 'Projects',
                data: topMembers.series
            }],
            plotOptions: {
                bar: {
                    borderRadius: 4,
                    horizontal: true,
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            xaxis: {
                categories: topMembers.categories,
                labels: {
                    style: {
                        colors: '#6c757d'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#6c757d'
                    }
                }
            },
            colors: ['#198754'],
            dataLabels: {
                enabled: true,
                formatter: value => `${value} Project${value !== 1 ? 's' : ''}`
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: value => `${value} Project${value !== 1 ? 's' : ''}`
                }
            }
        }).render();

        // Data for Projects by Users
        const projectsByUsers = {
            categories: <?php echo json_encode(array_column($projectsByUsers, 'username')); ?>,
            series: <?php echo json_encode(array_column($projectsByUsers, 'total_projects')); ?>
        };
        console.log('Projects by Users:', projectsByUsers);

        // Pie Chart for Projects by Users
        new ApexCharts(document.querySelector("#projectsByUsersChart"), {
            chart: {
                type: 'donut',
                height: 300
            },
            series: projectsByUsers.series,
            labels: projectsByUsers.categories,
            colors: ['#0dcaf0', '#ffc107', '#dc3545', '#198754', '#0d6efd', '#6610f2', '#fd7e14'],
            legend: {
                position: 'bottom',
                labels: {
                    colors: '#6c757d'
                }
            },
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => {
                    const value = opts.w.globals.series[opts.seriesIndex];
                    return `${value} Project${value !== 1 ? 's' : ''}`;
                }
            },
            tooltip: {
                theme: 'dark',
                y: {
                    formatter: value => `${value} Project${value !== 1 ? 's' : ''}`
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '50%'
                    }
                }
            }
        }).render();
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
});
</script>

<?php include 'includes/footer.php'; ?>