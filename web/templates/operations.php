<?php
// Operations template
$operations = get_operations();

// Check if a specific operation is requested
$op_id = isset($_GET['op_id']) ? intval($_GET['op_id']) : null;
$operation_details = null;

if ($op_id) {
    $operation_details = get_operation($op_id);
}
?>

<?php if ($operation_details): ?>
<!-- Single Operation View -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    Operation #<?php echo $operation_details['operation']['id']; ?> - 
                    <?php echo ucfirst($operation_details['operation']['type']); ?> Operation
                </h6>
                <a href="index.php?page=operations" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to All Operations
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Operation Details</h5>
                        <table class="table table-sm">
                            <tr>
                                <th>ID:</th>
                                <td><?php echo $operation_details['operation']['id']; ?></td>
                            </tr>
                            <tr>
                                <th>Type:</th>
                                <td>
                                    <span class="badge bg-<?php echo $operation_details['operation']['type'] == 'scrape' ? 'info' : 'warning'; ?>">
                                        <?php echo ucfirst($operation_details['operation']['type']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Target:</th>
                                <td><?php echo htmlspecialchars($operation_details['operation']['target']); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><?php echo status_badge($operation_details['operation']['status']); ?></td>
                            </tr>
                            <tr>
                                <th>Progress:</th>
                                <td><?php echo $operation_details['operation']['progress']; ?>%</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Timing Information</h5>
                        <table class="table table-sm">
                            <tr>
                                <th>Started:</th>
                                <td><?php echo format_datetime($operation_details['operation']['started_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Completed:</th>
                                <td>
                                    <?php 
                                    echo !empty($operation_details['operation']['completed_at']) 
                                        ? format_datetime($operation_details['operation']['completed_at'])
                                        : '-'; 
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Duration:</th>
                                <td>
                                    <?php 
                                    if (!empty($operation_details['operation']['completed_at'])) {
                                        $start = strtotime($operation_details['operation']['started_at']);
                                        $end = strtotime($operation_details['operation']['completed_at']);
                                        $duration = $end - $start;
                                        
                                        $hours = floor($duration / 3600);
                                        $minutes = floor(($duration % 3600) / 60);
                                        $seconds = $duration % 60;
                                        
                                        echo "$hours h $minutes m $seconds s";
                                    } else {
                                        echo 'In progress';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                        
                        <?php if ($operation_details['operation']['status'] == 'running'): ?>
                        <div class="d-grid gap-2 mt-3">
                            <button class="btn btn-danger stop-operation" data-operation-id="<?php echo $operation_details['operation']['id']; ?>">
                                <i class="fas fa-stop"></i> Stop Operation
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($operation_details['operation']['type'] == 'scrape' && in_array($operation_details['operation']['status'], ['completed', 'stopped'])): ?>
                        <div class="d-grid gap-2 mt-3">
                            <a href="api/operations/<?php echo $operation_details['operation']['id']; ?>/download" class="btn btn-success">
                                <i class="fas fa-download"></i> Download Results
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="progress mb-4">
                    <div class="progress-bar progress-bar-striped 
                        <?php echo $operation_details['operation']['status'] == 'running' ? 'progress-bar-animated' : ''; ?> 
                        bg-<?php 
                            if ($operation_details['operation']['status'] == 'completed') echo 'success';
                            elseif ($operation_details['operation']['status'] == 'failed') echo 'danger';
                            elseif ($operation_details['operation']['status'] == 'stopped') echo 'warning';
                            else echo 'primary';
                        ?>" 
                        role="progressbar" 
                        style="width: <?php echo $operation_details['operation']['progress']; ?>%" 
                        aria-valuenow="<?php echo $operation_details['operation']['progress']; ?>" 
                        aria-valuemin="0" 
                        aria-valuemax="100">
                        <?php echo $operation_details['operation']['progress']; ?>%
                    </div>
                </div>
                
                <h5 class="mt-4">Operation Logs</h5>
                
                <?php if (empty($operation_details['logs'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No logs available for this operation.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm" id="logsTable">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Account</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operation_details['logs'] as $log): ?>
                            <tr>
                                <td><?php echo format_datetime($log['created_at']); ?></td>
                                <td><?php echo $log['account_id'] ?: '-'; ?></td>
                                <td><?php echo $log['action']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        if ($log['status'] == 'success') echo 'success';
                                        elseif ($log['status'] == 'warning') echo 'warning';
                                        else echo 'danger';
                                    ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
                
                <?php if ($operation_details['operation']['status'] == 'running'): ?>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-sync"></i> This page will automatically refresh every 10 seconds to show the latest logs.
                </div>
                
                <script>
                    // Auto-refresh for running operations
                    setTimeout(function() {
                        window.location.reload();
                    }, 10000);
                </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- All Operations View -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">All Operations</h6>
                <div class="btn-group">
                    <a href="index.php?page=scrape" class="btn btn-sm btn-info">
                        <i class="fas fa-plus"></i> New Scrape
                    </a>
                    <a href="index.php?page=add" class="btn btn-sm btn-warning">
                        <i class="fas fa-plus"></i> New Add
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($operations)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-tasks fa-4x text-gray-300 mb-3"></i>
                    <p>No operations found. Start a new operation to see it here.</p>
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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($operations as $operation): ?>
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
                                    <?php 
                                    echo !empty($operation['completed_at']) 
                                        ? format_datetime($operation['completed_at'])
                                        : '-'; 
                                    ?>
                                </td>
                                <td>
                                    <a href="index.php?page=operations&op_id=<?php echo $operation['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($operation['status'] == 'running'): ?>
                                    <button class="btn btn-sm btn-danger stop-operation" data-operation-id="<?php echo $operation['id']; ?>">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($operation['type'] == 'scrape' && in_array($operation['status'], ['completed', 'stopped'])): ?>
                                    <a href="api/operations/<?php echo $operation['id']; ?>/download" class="btn btn-sm btn-success">
                                        <i class="fas fa-download"></i>
                                    </a>
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
<?php endif; ?>
