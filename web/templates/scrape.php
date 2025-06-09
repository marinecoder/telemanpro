<?php
// Scrape template
$accounts = get_accounts();
$active_accounts = array_filter($accounts, function($account) {
    return $account['status'] == 'active';
});
?>

<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Scrape Members</h6>
            </div>
            <div class="card-body">
                <?php if (empty($active_accounts)): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> You need at least one active account to scrape members.
                    <a href="index.php?page=accounts" class="alert-link">Add an account</a> first.
                </div>
                <?php else: ?>
                <form id="scrapeForm">
                    <div class="mb-3">
                        <label for="channel" class="form-label">Source Channel</label>
                        <input type="text" class="form-control" id="channel" name="channel" placeholder="@channelname or https://t.me/channelname" required>
                        <div class="form-text">Enter a public channel username or URL.</div>
                    </div>
                    <div class="mb-3">
                        <label for="limit" class="form-label">Member Limit (optional)</label>
                        <input type="number" class="form-control" id="limit" name="limit" placeholder="Leave empty for no limit">
                        <div class="form-text">Maximum number of members to scrape. Leave empty to scrape all members.</div>
                    </div>
                    
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> You have <strong><?php echo count($active_accounts); ?></strong> active accounts that can be used for this operation.
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="startScrapeBtn">
                            <i class="fas fa-play"></i> Start Scraping
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Active Operations Card -->
<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Active Scrape Operations</h6>
            </div>
            <div class="card-body">
                <div id="activeOperationsContainer">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary mb-3"></i>
                        <p>Loading active operations...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Operation Progress -->
<div class="modal fade" id="operationProgressModal" tabindex="-1" aria-labelledby="operationProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="operationProgressModalLabel">Operation Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="operation-details mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Operation ID:</strong> <span id="opId"></span></p>
                            <p><strong>Target:</strong> <span id="opTarget"></span></p>
                            <p><strong>Status:</strong> <span id="opStatus"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Started:</strong> <span id="opStarted"></span></p>
                            <p><strong>Completed:</strong> <span id="opCompleted"></span></p>
                            <p><strong>Progress:</strong> <span id="opProgress"></span>%</p>
                        </div>
                    </div>
                    <div class="progress mb-3">
                        <div id="opProgressBar" class="progress-bar progress-bar-striped" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                
                <h6>Operation Logs</h6>
                <div class="table-responsive">
                    <table class="table table-sm" id="opLogsTable">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody id="opLogs">
                            <tr>
                                <td colspan="4" class="text-center">Loading logs...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger d-none" id="stopOperationBtn">Stop Operation</button>
                <a href="#" class="btn btn-success d-none" id="downloadResultBtn" target="_blank">Download Results</a>
            </div>
        </div>
    </div>
</div>
