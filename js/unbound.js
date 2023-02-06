function restartDNSService() {
    $.ajax({
        type: "POST",
        url: 'controller.php',
        data: {
            'restart_dns_service': 'yes',
        },
        complete: function (xhr, statusText) {

            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            Toast.fire({
                icon: 'success',
                title: 'DNS Service Restarted!'
            })

            function reload() {
                location.reload();
            }

            setTimeout(reload, 2000);
        }
    });
}

function addNewDNSRecord() {
    var fqdn = document.getElementById('fqdn').value;
    var ipAddr = document.getElementById('ipAddr').value;
    var recordType = document.getElementById('recordType').value;
    $.ajax({
        type: "POST",
        url: 'controller.php',
        data: {
            'fqdn': fqdn,
            'ipAddr': ipAddr,
            'recordType': recordType,
            'newRecord': 'yes'
        },
        complete: function (xhr, statusText) {

            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            Toast.fire({
                icon: 'success',
                title: 'New DNS Record successfuly added!'
            })

            function reload() {
                location.reload();
            }

            setTimeout(reload, 2000);
        }
    });
}

var kayitID = 0;

function fillEditDNSRecord(id) {
    kayitID = id;
    $('#editDNSRecord').modal('show');
    var table = document.getElementById('dnsTable');
    var data = table.rows[id].cells;
    var fqdn = data[1].innerHTML;
    var ipAddr = data[2].innerHTML;
    var recordType = data[3].innerHTML;

    $('#editFQDN').val(fqdn);
    $('#editIPAddr').val(ipAddr);
    $('#editRecordType').val(recordType);
}

function editDNSRecord() {
    var table = document.getElementById('dnsTable');
    var data = table.rows[kayitID].cells;
    var oldFQDN = data[1].innerHTML;
    var oldIPAddr = data[2].innerHTML;
    var oldRecordType = data[3].innerHTML;

    var fqdn = document.getElementById('editFQDN').value;
    var ipAddr = document.getElementById('editIPAddr').value;
    var recordType = document.getElementById('editRecordType').value;
    $.ajax({
        type: "POST",
        url: 'controller.php',
        data: {
            'editFQDN': fqdn,
            'editIPAddr': ipAddr,
            'editRecordType': recordType,
            'oldFQDN': oldFQDN,
            'oldIPAddr': oldIPAddr,
            'oldRecordType': oldRecordType,
            'editRecord': 'yes'
        },
        complete: function (xhr, statusText) {

            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            })

            Toast.fire({
                icon: 'success',
                title: 'DNS Record successfuly edited!'
            })

            function reload() {
                location.reload();
            }

            setTimeout(reload, 2000);
        }
    });
}

function deleteRecord(id) {
    var table = document.getElementById('dnsTable');
    var data = table.rows[id].cells;
    var delFQDN = data[1].innerHTML;
    var delIPAddr = data[2].innerHTML;
    var delRecordType = data[3].innerHTML;

    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                type: "POST",
                url: 'controller.php',
                data: {
                    'delFQDN': delFQDN,
                    'delIPAddr': delIPAddr,
                    'delRecordType': delRecordType,
                    'deleteRecord': 'yes'
                },
                complete: function (xhr, statusText) {

                    Swal.fire(
                        'Deleted!',
                        'Your dns record has been deleted.',
                        'success'
                    )

                    function reload() {
                        location.reload();
                    }

                    setTimeout(reload, 2000);
                }
            });
        }
    })
}

function changePassword() {
    var oldPassword = document.getElementById('oldPassword').value;
    var newPassword = document.getElementById('newPassword').value;
    $.ajax({
        type: "POST",
        url: 'controller.php',
        data: {
            'oldPassword': oldPassword,
            'newPassword': newPassword,
            'changePassword': 'yes'
        },
        success: function (data) {
            if (data === 'success') {
                Swal.fire(
                    'Success!',
                    'Your password has changed.',
                    'success'
                )

                function reload() {
                    location.reload();
                }

                setTimeout(reload, 2000);
            } else {
                Swal.fire(
                    'Oops!',
                    'Old password is wrong, please check again!',
                    'error'
                )
            }
        }
    });
}

function logout() {
    $.ajax({
        url: "logout.php",
        type: 'POST',
        data: {
            'action': 'loggedout'
        },
        complete: function (xhr, statusText) {
            Swal.fire("Successfully logged out!", "redirecting..", "success");

            function redirect() {
                window.location = 'index.php';
            }

            setTimeout(redirect, 2000);
        }
    });
}
