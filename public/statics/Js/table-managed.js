var TableManaged = function () {

    var initTable1 = function () {

        var table = $('#sample_1');

        // begin first table
        table.dataTable({
            "columns": [{
                "orderable": false
            }, {
                "orderable": true
            }, {
                "orderable": false
            }, {
                "orderable": false
            }, {
                "orderable": true
            }, {
                "orderable": false
            }],
            "lengthMenu": [
                [5, 15, 20, -1],
                [5, 15, 20, "全部"] // change per page values here
            ],
            // set the initial value
            "pageLength": 3,
            "pagingType": "bootstrap_full_number",
            "language": {
                "lengthMenu": "  _MENU_ 条",
                "paginate": {
                    "previous":"上一页",
                    "next": "下一页",
                    "last": "最后一页",
                    "first": "第一页"
                }
            },
            "columnDefs": [{  // set default column settings
                'orderable': false,
                'targets': [0]
            }, {
                "searchable": false,
                "targets": [0]
            }],
            "order": [
                [1, "asc"]
            ] // set first column as a default sort by asc
        });

        var tableWrapper = jQuery('#sample_1_wrapper');

        table.find('.group-checkable').change(function () {
            var set = jQuery(this).attr("data-set");
            var checked = jQuery(this).is(":checked");
            jQuery(set).each(function () {
                if (checked) {
                    $(this).attr("checked", true);
                    $(this).parents('tr').addClass("active");
                    $(this).parents('span').addClass("checked");
                } else {
                    $(this).attr("checked", false);
                    $(this).parents('tr').removeClass("active");
                    $(this).parents('span').removeClass("checked");
                }
            });

            $(this).parents('span').toggleClass("checked");
            jQuery.uniform.update(set);
        });

        table.on('change', 'tbody tr .checkboxes', function () {
            $(this).parents('tr').toggleClass("active");
            $(this).parents('span').toggleClass("checked");
        });

        tableWrapper.find('.dataTables_length select').addClass("form-control input-xsmall input-inline"); // modify table per page dropdown
    }


    return {

        //main function to initiate the module
        init: function () {
            if (!jQuery().dataTable) {
                return;
            }

            initTable1();
        }

    };

}();