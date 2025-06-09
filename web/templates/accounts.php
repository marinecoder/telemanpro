<?php
// Accounts template
$accounts = get_accounts();
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Manage Accounts</h6>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                    <i class="fas fa-plus"></i> Add Account
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($accounts)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-user-circle fa-4x text-gray-300 mb-3"></i>
                    <p>No accounts found. Add an account to get started.</p>
                    <button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                        <i class="fas fa-plus"></i> Add Account
                    </button>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="accountsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Phone</th>
                                <th>API ID</th>
                                <th>Status</th>
                                <th>Last Used</th>
                                <th>Cooldown Until</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $account): ?>
                            <tr>
                                <td><?php echo $account['id']; ?></td>
                                <td><?php echo htmlspecialchars($account['phone']); ?></td>
                                <td><?php echo htmlspecialchars($account['api_id']); ?></td>
                                <td><?php echo status_badge($account['status']); ?></td>
                                <td><?php echo format_datetime($account['last_used']); ?></td>
                                <td>
                                    <?php 
                                    if (!empty($account['cooldown_until'])) {
                                        echo format_datetime($account['cooldown_until']);
                                        
                                        // Calculate time remaining
                                        $cooldown = strtotime($account['cooldown_until']);
                                        $now = time();
                                        
                                        if ($cooldown > $now) {
                                            $remaining = $cooldown - $now;
                                            $hours = floor($remaining / 3600);
                                            $minutes = floor(($remaining % 3600) / 60);
                                            
                                            echo " <span class='text-muted'>($hours h $minutes m)</span>";
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info view-account-btn" 
                                        data-account-id="<?php echo $account['id']; ?>"
                                        data-phone="<?php echo htmlspecialchars($account['phone']); ?>"
                                        data-api-id="<?php echo htmlspecialchars($account['api_id']); ?>"
                                        data-api-hash="<?php echo htmlspecialchars($account['api_hash']); ?>"
                                        data-status="<?php echo $account['status']; ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning authorize-account-btn" 
                                        data-account-id="<?php echo $account['id']; ?>"
                                        data-phone="<?php echo htmlspecialchars($account['phone']); ?>">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger delete-account-btn" 
                                        data-account-id="<?php echo $account['id']; ?>"
                                        data-phone="<?php echo htmlspecialchars($account['phone']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAccountModalLabel">Add New Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAccountForm">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="+1234567890" required>
                        <small class="form-text text-muted">Enter phone number with country code.</small>
                    </div>
                    <div class="mb-3">
                        <label for="api_id" class="form-label">API ID</label>
                        <input type="text" class="form-control" id="api_id" name="api_id" required>
                        <small class="form-text text-muted">Get this from <a href="https://my.telegram.org/apps" target="_blank">my.telegram.org/apps</a></small>
                    </div>
                    <div class="mb-3">
                        <label for="api_hash" class="form-label">API Hash</label>
                        <input type="text" class="form-control" id="api_hash" name="api_hash" required>
                        <small class="form-text text-muted">Get this from <a href="https://my.telegram.org/apps" target="_blank">my.telegram.org/apps</a></small>
                    </div>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> After adding the account, you'll need to authorize it.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveAccountBtn">Add Account</button>
            </div>
        </div>
    </div>
</div>

<!-- View Account Modal -->
<div class="modal fade" id="viewAccountModal" tabindex="-1" aria-labelledby="viewAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewAccountModalLabel">Account Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <p class="form-control-static" id="viewPhone"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">API ID</label>
                    <p class="form-control-static" id="viewApiId"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">API Hash</label>
                    <p class="form-control-static" id="viewApiHash"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <p class="form-control-static" id="viewStatus"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Authorize Account Modal -->
<div class="modal fade" id="authorizeAccountModal" tabindex="-1" aria-labelledby="authorizeAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="authorizeAccountModalLabel">Authorize Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>To authorize the account, you'll need to enter the code sent to your Telegram account.</p>
                <div class="mb-3">
                    <label for="authPhone" class="form-label">Phone Number</label>
                    <p class="form-control-static" id="authPhone"></p>
                </div>
                <form id="authorizeAccountForm">
                    <input type="hidden" id="authAccountId" name="account_id">
                    <div class="mb-3">
                        <label for="code" class="form-label">Verification Code</label>
                        <input type="text" class="form-control" id="code" name="code" placeholder="Enter code from Telegram" required>
                    </div>
                    <div class="mb-3 password-form" style="display: none;">
                        <label for="password" class="form-label">Two-Factor Password</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your 2FA password (if enabled)">
                    </div>
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-info-circle"></i> Check your Telegram app for the verification code.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="authorizeBtn">Authorize</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">Delete Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this account?</p>
                <p><strong>Phone:</strong> <span id="deletePhone"></span></p>
                <input type="hidden" id="deleteAccountId">
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>
