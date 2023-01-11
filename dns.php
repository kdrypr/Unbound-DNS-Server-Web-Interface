<?php
include_once("core/functions.php");
require_once("config/security.php");
?>

<?php
include('core/header.php');
include('core/sidebar.php');
?>
    <!-- Content wrapper -->
    <div class="content-wrapper">
        <!-- DNS records -->

        <div class="container-xxl flex-grow-1 container-p-y">
            <div class="row">
                <div class="card">
                    <h5 class="card-header">DNS Records</h5>
                    <div class="table-responsive text-nowrap">
                        <table class="table" id="dnsTable">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>FQDN</th>
                                <th>IP Address</th>
                                <th>Record Type</th>
                                <th>Option</th>
                            </tr>
                            </thead>
                            <tbody class="table-border-bottom-0">
                            <?php
                            getDNSList();
                            ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- DNS Modal -->
            <div class="modal fade" id="newDNSRecord" data-bs-backdrop="static" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="newDNSRecordTitle">Add New DNS Record</h5>
                            <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                            ></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-3">
                                    <label for="fqdn" class="form-label">FQDN</label>
                                    <input
                                            type="text"
                                            id="fqdn"
                                            class="form-control"
                                            placeholder="Enter FQDN"
                                    />
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col mb-0">
                                    <label for="ipAddr" class="form-label">IP Address</label>
                                    <input
                                            type="text"
                                            id="ipAddr"
                                            class="form-control"
                                            placeholder="Enter IP Address"
                                    />
                                </div>
                                <div class="col mb-0">
                                    <label for="recordType" class="form-label">Record Type</label>
                                    <select id="recordType" class="form-select">
                                        <option value="A">A Record</option>
                                        <option value="MX">MX Record</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button type="button" class="btn btn-primary" onclick="addNewDNSRecord()">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal fade" id="editDNSRecord" data-bs-backdrop="static" tabindex="-1">
                <div class="modal-dialog">
                    <form class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editDNSRecordTitle">Edit DNS Record</h5>
                            <button
                                    type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                            ></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col mb-3">
                                    <label for="editFQDN" class="form-label">FQDN</label>
                                    <input
                                            type="text"
                                            id="editFQDN"
                                            class="form-control"
                                            placeholder="Enter FQDN"
                                    />
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col mb-0">
                                    <label for="editIPAddr" class="form-label">IP Address</label>
                                    <input
                                            type="text"
                                            id="editIPAddr"
                                            class="form-control"
                                            placeholder="Enter IP Address"
                                    />
                                </div>
                                <div class="col mb-0">
                                    <label for="editRecordType" class="form-label">Record Type</label>
                                    <select id="editRecordType" class="form-select">
                                        <option value="A">A Record</option>
                                        <option value="MX">MX Record</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3">

                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button type="button" class="btn btn-primary" onclick="editDNSRecord()">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- / DNS records -->
<?php
include("core/footer.php");
?>