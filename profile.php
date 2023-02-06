<?php
include_once("core/functions.php");
require_once("config/security.php");
?>

<?php
include('core/header.php');
include('core/sidebar.php');
?>

<div class="content-wrapper">
    <!-- DNS records -->

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="card">
                <h5 class="card-header">Şifre Değiştir</h5>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="control-label">Eski Şifre</label>

                            <input type="password" class="form-control" id="oldPassword"
                                   required="required">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="control-label">Yeni Şifre</label>

                            <input type="password" class="form-control" id="newPassword"
                                   required="required">
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-1">
                        <button class="btn btn-success" onclick="changePassword()">Kaydet</button>
                    </div>
                </div>
                <br>
            </div>
        </div>
    </div>
</div>

<?php
include("core/footer.php");
?>
