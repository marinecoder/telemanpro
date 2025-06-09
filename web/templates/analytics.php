<?php
// Check if user is logged in
require_once '../includes/functions.php';
session_start();
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = "Analytics";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include '../templates/header_common.php'; ?>
    <style>
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../templates/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Analytics & Reports</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportCsvBtn">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportPdfBtn">
                                <i class="fas fa-file-pdf"></i> Export PDF
                            </button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="timeRangeDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-calendar"></i> Last 7 Days
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="timeRangeDropdown">
                                <li><a class="dropdown-item time-range" data-range="7" href="#">Last 7 Days</a></li>
                                <li><a class="dropdown-item time-range" data-range="30" href="#">Last 30 Days</a></li>
                                <li><a class="dropdown-item time-range" data-range="90" href="#">Last 90 Days</a></li>
                                <li><a class="dropdown-item time-range" data-range="365" href="#">Last Year</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" id="customRangeBtn">Custom Range</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Accounts</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAccounts">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Members Scraped</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalScraped">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-download fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Members Added</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAdded">0</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2 stat-card">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Success Rate</div>
                                        <div class="row no-gutters align-items-center">
                                            <div class="col-auto">
                                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800" id="successRate">0%</div>
                                            </div>
                                            <div class="col">
                                                <div class="progress progress-sm mr-2">
                                                    <div class="progress-bar bg-warning" role="progressbar" id="successRateBar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Activity Over Time Chart -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Activity Over Time</h6>
                                <div class="dropdown no-arrow">
                                    <a class="dropdown-toggle" href="#" role="button" id="activityDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="activityDropdown">
                                        <li><a class="dropdown-item chart-type" data-type="line" href="#">Line Chart</a></li>
                                        <li><a class="dropdown-item chart-type" data-type="bar" href="#">Bar Chart</a></li>
                                        <li><a class="dropdown-item chart-type" data-type="area" href="#">Area Chart</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Operation Status Chart -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Operation Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4">
                                    <canvas id="operationStatusChart"></canvas>
                                </div>
                                <div class="mt-4 text-center small" id="statusChartLegend">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Status and Performance Charts -->
                <div class="row mb-4">
                    <!-- Account Status Chart -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Account Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4">
                                    <canvas id="accountStatusChart"></canvas>
                                </div>
                                <div class="mt-4 text-center small" id="accountChartLegend">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Account Performance Chart -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Account Performance</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-bar">
                                    <canvas id="accountPerformanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Operations Table -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Operations</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="operationsTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Type</th>
                                        <th>Target</th>
                                        <th>Status</th>
                                        <th>Progress</th>
                                        <th>Started</th>
                                        <th>Completed</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody id="recentOperationsBody">
                                    <!-- Data will be loaded here via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Custom Date Range Modal -->
                <div class="modal fade" id="dateRangeModal" tabindex="-1" aria-labelledby="dateRangeModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="dateRangeModalLabel">Select Date Range</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="dateRangeForm">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="startDate" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="startDate" name="startDate" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="endDate" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="endDate" name="endDate" required>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="applyDateRangeBtn">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Initialize charts and load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Set default date range (last 7 days)
            const today = new Date();
            const startDate = new Date();
            startDate.setDate(today.getDate() - 7);
            
            loadAnalyticsData({
                startDate: formatDate(startDate),
                endDate: formatDate(today),
                range: 7
            });
            
            // Initialize charts
            initializeCharts();
            
            // Set up event listeners
            setupEventListeners();
        });
        
        // Format date as YYYY-MM-DD
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Load analytics data based on date range
        function loadAnalyticsData(params) {
            // Update the dropdown button text
            if (params.range) {
                document.getElementById('timeRangeDropdown').innerHTML = 
                    `<i class="fas fa-calendar"></i> Last ${params.range} Days`;
            } else {
                document.getElementById('timeRangeDropdown').innerHTML = 
                    `<i class="fas fa-calendar"></i> Custom Range`;
            }
            
            // Fetch data from API
            fetch(`/api/analytics?startDate=${params.startDate}&endDate=${params.endDate}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update summary stats
                        updateSummaryStats(data.stats);
                        
                        // Update charts
                        updateActivityChart(data.activity);
                        updateOperationStatusChart(data.operationStatus);
                        updateAccountStatusChart(data.accountStatus);
                        updateAccountPerformanceChart(data.accountPerformance);
                        
                        // Update recent operations table
                        updateRecentOperations(data.recentOperations);
                    } else {
                        showAlert('error', 'Failed to load analytics data: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading analytics data:', error);
                    showAlert('error', 'Failed to load analytics data');
                });
        }
        
        // Initialize chart objects
        let activityChart, operationStatusChart, accountStatusChart, accountPerformanceChart;
        
        function initializeCharts() {
            // Activity Chart
            const activityCtx = document.getElementById('activityChart').getContext('2d');
            activityChart = new Chart(activityCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Scrape Operations',
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.05)',
                            pointRadius: 3,
                            pointBackgroundColor: '#4e73df',
                            pointBorderColor: '#4e73df',
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#4e73df',
                            pointHoverBorderColor: '#4e73df',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            data: [],
                            fill: true
                        },
                        {
                            label: 'Add Operations',
                            borderColor: '#1cc88a',
                            backgroundColor: 'rgba(28, 200, 138, 0.05)',
                            pointRadius: 3,
                            pointBackgroundColor: '#1cc88a',
                            pointBorderColor: '#1cc88a',
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: '#1cc88a',
                            pointHoverBorderColor: '#1cc88a',
                            pointHitRadius: 10,
                            pointBorderWidth: 2,
                            data: [],
                            fill: true
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        x: {
                            time: {
                                unit: 'day'
                            },
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)"
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: true
                        },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            titleFontSize: 14,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            padding: 15,
                            displayColors: false
                        }
                    }
                }
            });
            
            // Operation Status Chart
            const operationStatusCtx = document.getElementById('operationStatusChart').getContext('2d');
            operationStatusChart = new Chart(operationStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Running', 'Failed', 'Pending', 'Stopped'],
                    datasets: [{
                        data: [0, 0, 0, 0, 0],
                        backgroundColor: ['#1cc88a', '#4e73df', '#e74a3b', '#f6c23e', '#858796'],
                        hoverBackgroundColor: ['#17a673', '#2e59d9', '#be2617', '#dda20a', '#6e707e'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            padding: 15,
                            displayColors: false
                        }
                    }
                }
            });
            
            // Account Status Chart
            const accountStatusCtx = document.getElementById('accountStatusChart').getContext('2d');
            accountStatusChart = new Chart(accountStatusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Restricted', 'Banned'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                        hoverBackgroundColor: ['#17a673', '#dda20a', '#be2617'],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            padding: 15,
                            displayColors: false
                        }
                    }
                }
            });
            
            // Account Performance Chart
            const accountPerformanceCtx = document.getElementById('accountPerformanceChart').getContext('2d');
            accountPerformanceChart = new Chart(accountPerformanceCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Success Rate (%)',
                        backgroundColor: '#4e73df',
                        hoverBackgroundColor: '#2e59d9',
                        borderColor: '#4e73df',
                        data: []
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    layout: {
                        padding: {
                            left: 10,
                            right: 25,
                            top: 25,
                            bottom: 0
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            max: 100,
                            grid: {
                                color: "rgba(0, 0, 0, 0.05)"
                            },
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            backgroundColor: "rgb(255,255,255)",
                            bodyColor: "#858796",
                            titleMarginBottom: 10,
                            titleColor: '#6e707e',
                            titleFontSize: 14,
                            borderColor: '#dddfeb',
                            borderWidth: 1,
                            padding: 15,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Update summary statistics
        function updateSummaryStats(stats) {
            document.getElementById('totalAccounts').textContent = stats.totalAccounts;
            document.getElementById('totalScraped').textContent = stats.totalScraped;
            document.getElementById('totalAdded').textContent = stats.totalAdded;
            document.getElementById('successRate').textContent = stats.successRate + '%';
            
            const successRateBar = document.getElementById('successRateBar');
            successRateBar.style.width = stats.successRate + '%';
            successRateBar.setAttribute('aria-valuenow', stats.successRate);
        }
        
        // Update activity chart
        function updateActivityChart(activity) {
            activityChart.data.labels = activity.dates;
            activityChart.data.datasets[0].data = activity.scrapeOps;
            activityChart.data.datasets[1].data = activity.addOps;
            activityChart.update();
        }
        
        // Update operation status chart
        function updateOperationStatusChart(statusData) {
            operationStatusChart.data.datasets[0].data = [
                statusData.completed,
                statusData.running,
                statusData.failed,
                statusData.pending,
                statusData.stopped
            ];
            operationStatusChart.update();
            
            // Update legend
            updateChartLegend('statusChartLegend', operationStatusChart);
        }
        
        // Update account status chart
        function updateAccountStatusChart(statusData) {
            accountStatusChart.data.datasets[0].data = [
                statusData.active,
                statusData.restricted,
                statusData.banned
            ];
            accountStatusChart.update();
            
            // Update legend
            updateChartLegend('accountChartLegend', accountStatusChart);
        }
        
        // Update account performance chart
        function updateAccountPerformanceChart(performanceData) {
            accountPerformanceChart.data.labels = performanceData.accounts;
            accountPerformanceChart.data.datasets[0].data = performanceData.successRates;
            accountPerformanceChart.update();
        }
        
        // Update chart legend
        function updateChartLegend(elementId, chart) {
            const legendEl = document.getElementById(elementId);
            legendEl.innerHTML = '';
            
            chart.data.labels.forEach((label, index) => {
                const bgColor = chart.data.datasets[0].backgroundColor[index];
                const span = document.createElement('span');
                span.className = 'mr-2';
                span.innerHTML = `
                    <i class="fas fa-circle" style="color: ${bgColor}"></i> ${label}
                `;
                legendEl.appendChild(span);
                
                // Add spacing between items
                if (index < chart.data.labels.length - 1) {
                    const spacer = document.createElement('span');
                    spacer.innerHTML = '&nbsp;&nbsp;&nbsp;';
                    legendEl.appendChild(spacer);
                }
            });
        }
        
        // Update recent operations table
        function updateRecentOperations(operations) {
            const tableBody = document.getElementById('recentOperationsBody');
            tableBody.innerHTML = '';
            
            operations.forEach(op => {
                const row = document.createElement('tr');
                
                // Calculate duration
                let durationText = 'N/A';
                if (op.completed_at) {
                    const startDate = new Date(op.started_at);
                    const endDate = new Date(op.completed_at);
                    const durationMs = endDate - startDate;
                    const durationMins = Math.floor(durationMs / 60000);
                    const durationSecs = Math.floor((durationMs % 60000) / 1000);
                    durationText = `${durationMins}m ${durationSecs}s`;
                }
                
                // Set status badge class
                let statusClass = 'primary';
                switch (op.status) {
                    case 'completed': statusClass = 'success'; break;
                    case 'running': statusClass = 'primary'; break;
                    case 'failed': statusClass = 'danger'; break;
                    case 'pending': statusClass = 'warning'; break;
                    case 'stopped': statusClass = 'secondary'; break;
                }
                
                row.innerHTML = `
                    <td>${op.id}</td>
                    <td>${op.type}</td>
                    <td>${op.target}</td>
                    <td><span class="badge bg-${statusClass}">${op.status}</span></td>
                    <td>
                        <div class="progress">
                            <div class="progress-bar bg-${statusClass}" role="progressbar" 
                                style="width: ${op.progress}%" aria-valuenow="${op.progress}" 
                                aria-valuemin="0" aria-valuemax="100">${op.progress}%</div>
                        </div>
                    </td>
                    <td>${new Date(op.started_at).toLocaleString()}</td>
                    <td>${op.completed_at ? new Date(op.completed_at).toLocaleString() : 'N/A'}</td>
                    <td>${durationText}</td>
                `;
                
                tableBody.appendChild(row);
            });
            
            if (operations.length === 0) {
                const row = document.createElement('tr');
                row.innerHTML = '<td colspan="8" class="text-center">No operations found</td>';
                tableBody.appendChild(row);
            }
        }
        
        // Set up event listeners
        function setupEventListeners() {
            // Time range dropdown
            document.querySelectorAll('.time-range').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const range = parseInt(this.getAttribute('data-range'));
                    
                    // Calculate date range
                    const endDate = new Date();
                    const startDate = new Date();
                    startDate.setDate(endDate.getDate() - range);
                    
                    loadAnalyticsData({
                        startDate: formatDate(startDate),
                        endDate: formatDate(endDate),
                        range: range
                    });
                });
            });
            
            // Custom date range modal
            document.getElementById('customRangeBtn').addEventListener('click', function(e) {
                e.preventDefault();
                
                // Set default dates to last 7 days
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(endDate.getDate() - 7);
                
                document.getElementById('startDate').value = formatDate(startDate);
                document.getElementById('endDate').value = formatDate(endDate);
                
                const modal = new bootstrap.Modal(document.getElementById('dateRangeModal'));
                modal.show();
            });
            
            // Apply custom date range
            document.getElementById('applyDateRangeBtn').addEventListener('click', function() {
                const startDate = document.getElementById('startDate').value;
                const endDate = document.getElementById('endDate').value;
                
                if (startDate && endDate) {
                    loadAnalyticsData({
                        startDate: startDate,
                        endDate: endDate
                    });
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('dateRangeModal'));
                    modal.hide();
                }
            });
            
            // Change chart type
            document.querySelectorAll('.chart-type').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    const chartType = this.getAttribute('data-type');
                    
                    // Save current data
                    const currentData = activityChart.data;
                    
                    // Destroy current chart
                    activityChart.destroy();
                    
                    // Create new chart with selected type
                    const ctx = document.getElementById('activityChart').getContext('2d');
                    activityChart = new Chart(ctx, {
                        type: chartType === 'area' ? 'line' : chartType,
                        data: currentData,
                        options: {
                            maintainAspectRatio: false,
                            layout: {
                                padding: {
                                    left: 10,
                                    right: 25,
                                    top: 25,
                                    bottom: 0
                                }
                            },
                            scales: {
                                x: {
                                    time: {
                                        unit: 'day'
                                    },
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: "rgba(0, 0, 0, 0.05)"
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: true
                                },
                                tooltip: {
                                    backgroundColor: "rgb(255,255,255)",
                                    bodyColor: "#858796",
                                    titleMarginBottom: 10,
                                    titleColor: '#6e707e',
                                    titleFontSize: 14,
                                    borderColor: '#dddfeb',
                                    borderWidth: 1,
                                    padding: 15,
                                    displayColors: false
                                }
                            }
                        }
                    });
                    
                    // Set fill option for area chart
                    if (chartType === 'area') {
                        activityChart.data.datasets.forEach(dataset => {
                            dataset.fill = true;
                        });
                    } else {
                        activityChart.data.datasets.forEach(dataset => {
                            dataset.fill = false;
                        });
                    }
                    
                    activityChart.update();
                });
            });
            
            // Export CSV button
            document.getElementById('exportCsvBtn').addEventListener('click', function() {
                exportData('csv');
            });
            
            // Export PDF button
            document.getElementById('exportPdfBtn').addEventListener('click', function() {
                exportData('pdf');
            });
        }
        
        // Export data function
        function exportData(format) {
            // Get current date range from the dropdown button text
            const rangeText = document.getElementById('timeRangeDropdown').textContent.trim();
            let range = 7;
            
            if (rangeText.includes('30')) {
                range = 30;
            } else if (rangeText.includes('90')) {
                range = 90;
            } else if (rangeText.includes('Year')) {
                range = 365;
            } else if (rangeText.includes('Custom')) {
                // For custom range, we need to get the actual dates
                range = null;
            }
            
            // Calculate date range
            const endDate = new Date();
            let startDate;
            
            if (range) {
                startDate = new Date();
                startDate.setDate(endDate.getDate() - range);
            } else {
                // If custom range, try to get from the modal
                startDate = document.getElementById('startDate').value;
                if (!startDate) {
                    // Default to last 7 days if no custom range
                    startDate = new Date();
                    startDate.setDate(endDate.getDate() - 7);
                } else {
                    startDate = new Date(startDate);
                }
            }
            
            // Build the export URL
            const url = `/api/analytics/export?format=${format}&startDate=${formatDate(startDate)}&endDate=${formatDate(endDate)}`;
            
            // Trigger download
            window.location.href = url;
        }
        
        // Show alert function
        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
            alertDiv.setAttribute('role', 'alert');
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            const main = document.querySelector('main');
            main.insertBefore(alertDiv, main.firstChild);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }, 5000);
        }
    </script>
</body>
</html>
