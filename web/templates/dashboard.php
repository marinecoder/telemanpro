<?php
// Dashboard template
$stats = get_stats();
?>

<div class="row mb-4">
    <!-- Account Statistics -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-primary shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Accounts</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['accounts']['total']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-success">
                        <i class="fas fa-circle"></i> Active: <?php echo $stats['accounts']['active']; ?>
                    </span>
                    <span class="text-warning ms-2">
                        <i class="fas fa-circle"></i> Restricted: <?php echo $stats['accounts']['restricted']; ?>
                    </span>
                    <span class="text-danger ms-2">
                        <i class="fas fa-circle"></i> Banned: <?php echo $stats['accounts']['banned']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Operations Statistics -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-success shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Total Operations</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['operations']['total']; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tasks fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="mt-2 small">
                    <span class="text-primary">
                        <i class="fas fa-circle"></i> Running: <?php echo $stats['operations']['running']; ?>
                    </span>
                    <span class="text-success ms-2">
                        <i class="fas fa-circle"></i> Completed: <?php echo $stats['operations']['completed']; ?>
                    </span>
                    <span class="text-danger ms-2">
                        <i class="fas fa-circle"></i> Failed: <?php echo $stats['operations']['failed']; ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Scrape Operations -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-info shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Scrape Operations
                        </div>
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                    <?php 
                                    $scrape_count = 0;
                                    foreach ($stats['recent_operations'] as $op) {
                                        if ($op['type'] == 'scrape') $scrape_count++;
                                    }
                                    echo $scrape_count;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-download fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="index.php?page=scrape" class="btn btn-info btn-sm">
                        <i class="fas fa-plus"></i> New Scrape
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Operations -->
    <div class="col-md-6 col-xl-3 mb-4">
        <div class="card border-left-warning shadow h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Add Operations</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php 
                            $add_count = 0;
                            foreach ($stats['recent_operations'] as $op) {
                                if ($op['type'] == 'add') $add_count++;
                            }
                            echo $add_count;
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <a href="index.php?page=add" class="btn btn-warning btn-sm">
                        <i class="fas fa-plus"></i> New Add
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Operations -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Operations</h6>
                <a href="index.php?page=operations" class="btn btn-sm btn-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($stats['recent_operations'])): ?>
                <div class="text-center py-4">
                    <i class="fas fa-tasks fa-4x text-gray-300 mb-3"></i>
                    <p>No recent operations found.</p>
                    <div class="mt-3">
                        <a href="index.php?page=scrape" class="btn btn-primary me-2">
                            <i class="fas fa-download"></i> Start Scraping
                        </a>
                        <a href="index.php?page=add" class="btn btn-warning">
                            <i class="fas fa-user-plus"></i> Start Adding
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Started</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats['recent_operations'] as $operation): ?>
                            <tr>
                                <td><?php echo $operation['id']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $operation['type'] == 'scrape' ? 'info' : 'warning'; ?>">
                                        <?php echo ucfirst($operation['type']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($operation['target']); ?></td>
                                <td><?php echo status_badge($operation['status']); ?></td>
                                <td>
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-striped 
                                            <?php echo $operation['status'] == 'running' ? 'progress-bar-animated' : ''; ?> 
                                            bg-<?php 
                                                if ($operation['status'] == 'completed') echo 'success';
                                                elseif ($operation['status'] == 'failed') echo 'danger';
                                                elseif ($operation['status'] == 'stopped') echo 'warning';
                                                else echo 'primary';
                                            ?>" 
                                            role="progressbar" 
                                            style="width: <?php echo $operation['progress']; ?>%" 
                                            aria-valuenow="<?php echo $operation['progress']; ?>" 
                                            aria-valuemin="0" 
                                            aria-valuemax="100">
                                            <?php echo $operation['progress']; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo format_datetime($operation['started_at']); ?></td>
                                <td>
                                    <a href="index.php?page=operations&op_id=<?php echo $operation['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($operation['status'] == 'running'): ?>
                                    <button class="btn btn-sm btn-danger stop-operation" data-operation-id="<?php echo $operation['id']; ?>">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Account Status Overview -->
<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Account Status</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="accountStatusChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="mr-2">
                        <i class="fas fa-circle text-success"></i> Active
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-warning"></i> Restricted
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-danger"></i> Banned
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Operation Status</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2">
                    <canvas id="operationStatusChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="mr-2">
                        <i class="fas fa-circle text-primary"></i> Running
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-success"></i> Completed
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-warning"></i> Stopped
                    </span>
                    <span class="mr-2">
                        <i class="fas fa-circle text-danger"></i> Failed
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Account Status Chart
document.addEventListener('DOMContentLoaded', function() {
    const accountCtx = document.getElementById('accountStatusChart').getContext('2d');
    const accountChart = new Chart(accountCtx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Restricted', 'Banned'],
            datasets: [{
                data: [
                    <?php echo $stats['accounts']['active']; ?>,
                    <?php echo $stats['accounts']['restricted']; ?>,
                    <?php echo $stats['accounts']['banned']; ?>
                ],
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#17a673', '#e9b430', '#d84334'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: false
            },
            cutoutPercentage: 80,
        },
    });

    // Operation Status Chart
    const operationCtx = document.getElementById('operationStatusChart').getContext('2d');
    const operationChart = new Chart(operationCtx, {
        type: 'doughnut',
        data: {
            labels: ['Running', 'Completed', 'Stopped', 'Failed'],
            datasets: [{
                data: [
                    <?php echo $stats['operations']['running']; ?>,
                    <?php echo $stats['operations']['completed']; ?>,
                    <?php echo $stats['operations']['stopped']; ?>,
                    <?php echo $stats['operations']['failed']; ?>
                ],
                backgroundColor: ['#4e73df', '#1cc88a', '#f6c23e', '#e74a3b'],
                hoverBackgroundColor: ['#476ae2', '#17a673', '#e9b430', '#d84334'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }],
        },
        options: {
            maintainAspectRatio: false,
            tooltips: {
                backgroundColor: "rgb(255,255,255)",
                bodyFontColor: "#858796",
                borderColor: '#dddfeb',
                borderWidth: 1,
                xPadding: 15,
                yPadding: 15,
                displayColors: false,
                caretPadding: 10,
            },
            legend: {
                display: false
            },
            cutoutPercentage: 80,
        },
    });
});
</script>
