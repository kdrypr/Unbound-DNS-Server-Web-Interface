<?php
declare(strict_types=1);
require_once __DIR__ . '/core/functions.php';
require_once __DIR__ . '/config/security.php';
include 'core/header.php';
include 'core/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <!-- Toolbar -->
        <div class="card mb-4">
            <div class="card-body py-3">
                <div class="dns-toolbar">
                    <div class="input-group" style="max-width: 320px;">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="dnsSearch" placeholder="Search FQDN or IP...">
                    </div>
                    <select class="form-select" id="typeFilter" style="max-width: 150px;">
                        <option value="">All Types</option>
                        <option value="A">A</option>
                        <option value="AAAA">AAAA</option>
                        <option value="MX">MX</option>
                        <option value="CNAME">CNAME</option>
                        <option value="TXT">TXT</option>
                    </select>
                    <select class="form-select" id="perPage" style="max-width: 100px;">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <div class="ms-auto d-flex gap-2">
                        <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#queryModal">
                            <i class="bx bx-search-alt me-1"></i>DNS Query
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="bx bx-plus me-1"></i>Add Record
                        </button>
                        <button class="btn btn-danger" id="applyBtn" onclick="applyRules()" disabled>
                            <i class="bx bx-refresh me-1"></i>Apply Rules
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- DNS Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">DNS Records</h5>
                <div id="statusIndicator" class="status-indicator">
                    <span class="dot green"></span>
                    <small class="text-muted">All changes applied</small>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover" id="dnsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>FQDN</th>
                            <th>Value</th>
                            <th style="width: 100px;">Type</th>
                            <th style="width: 80px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="dnsTableBody">
                        <tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted" id="recordInfo">Loading...</small>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <!-- Add Record Modal -->
    <div class="modal fade" id="addModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-plus-circle me-2"></i>Add DNS Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">FQDN</label>
                        <input type="text" class="form-control" id="addFqdn" placeholder="e.g. server.example.com">
                    </div>
                    <div class="row">
                        <div class="col-8 mb-3">
                            <label class="form-label">Value (IP / Hostname)</label>
                            <input type="text" class="form-control" id="addValue" placeholder="e.g. 10.0.0.1">
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="addType">
                                <option value="A">A</option>
                                <option value="AAAA">AAAA</option>
                                <option value="MX">MX</option>
                                <option value="CNAME">CNAME</option>
                                <option value="TXT">TXT</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addRecord()"><i class="bx bx-save me-1"></i>Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Record Modal -->
    <div class="modal fade" id="editModal" data-bs-backdrop="static" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Edit DNS Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editOldFqdn">
                    <input type="hidden" id="editOldValue">
                    <input type="hidden" id="editOldType">
                    <div class="mb-3">
                        <label class="form-label">FQDN</label>
                        <input type="text" class="form-control" id="editFqdn">
                    </div>
                    <div class="row">
                        <div class="col-8 mb-3">
                            <label class="form-label">Value (IP / Hostname)</label>
                            <input type="text" class="form-control" id="editValue">
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="editType">
                                <option value="A">A</option>
                                <option value="AAAA">AAAA</option>
                                <option value="MX">MX</option>
                                <option value="CNAME">CNAME</option>
                                <option value="TXT">TXT</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="editRecord()"><i class="bx bx-save me-1"></i>Update</button>
                </div>
            </div>
        </div>
    </div>

    <!-- DNS Query Modal -->
    <div class="modal fade" id="queryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-search-alt me-2"></i>DNS Query</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="queryDomain" placeholder="Enter domain name...">
                        <button class="btn btn-primary" onclick="dnsQuery()">Query</button>
                    </div>
                    <div id="queryResult" class="d-none">
                        <label class="form-label">Result:</label>
                        <pre class="bg-light p-3 rounded" id="queryOutput" style="max-height: 200px; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'core/footer.php'; ?>
<script src="js/unbound.js"></script>
