var repair = function ($, url) {
    var counter = 2;
    var valueTemplate = '<span class="selected-value" style="background-image: url(\'#:data.image#\')"></span><span>#:data.name#</span>';
    var template = '<span class="k-state-default" style="background-image: url(\'#:data.image#\')"></span>' +
        '<span class="k-state-default"><h3>#: data.name #</h3></span>';

    var productValueTemplate = '<span class="selected-value" style="background-image: url(\'#:data.image#\')"></span><span>#:data.name#</span><span class="right delete-repair">X</span><span class="right">#:kendo.toString(data.price, "c") #</span>';
    var productTemplate = '<span class="k-state-default" style="background-image: url(\'#:data.image#\')"></span>' +
        '<span class="k-state-default"><h3>#: data.name #</h3></span><span class="k-state-default right"><h2>#: kendo.toString(data.price, "c") #</h2></span>';


    var categoryTransport = {
        parameterMap: function (data) {
            if (data.filter && data.filter.filters && data.filter.filters.length > 0) {
                return { id: data.filter.filters[0].value };
            }
            return {};
        },
        read: {
            url: url + "/repair/ajax/category",
            type: "get",
            dataType: "json"
        }
    };

    var productTransport = {
        parameterMap: function (data) {
            if (data.filter && data.filter.filters && data.filter.filters.length > 0) {
                return { id: data.filter.filters[0].value };
            }
            return {};
        },
        read: {
            url: url + "/repair/ajax/product",
            type: "get",
            dataType: "json",
        }
    };

    function saveRepair() {
        var failed = false;
        var data = {
            imei: $("#imei").val(),
            problem: $("#problem").val(),
            pincode: $("#pincode").val(),
            screencode: $("#screencode").val(),
            extracodes: $("#extracodes").val(),
            repairs: $.grep($("#repairs input").map(function () { return $(this).val(); }), function (n) { return (n) })
        };

        $("#imei").removeClass("validation-failed");
        $("#problem").removeClass("validation-failed");

        if (data.imei.trim() === "") {
            $("#imei").addClass("validation-failed");
            failed = true;
        }

        if (data.problem.trim() === "") {
            $("#problem").addClass("validation-failed");
            failed = true;
        }
        
        if(failed){
            return;
        }

        $.ajax({
            type: "POST",
            url: url + "/repair/ajax/save",
            dataType: "json",
            data: data
        }).then(function () { ultracart.reload(true); }, function () { });
    }

    function addRepair() {
        var repairs = $("#repairs"),
            changed = false;
        counter++;

        repairs.append("<input id='repair-" + counter + "' class='repair' disabled='disabled' style='width: 100%;' />");

        $("#repair-" + counter).kendoDropDownList({
            autoBind: false,
            optionLabel: "Välj reparation",
            cascadeFrom: "modell",
            dataTextField: "name",
            dataValueField: "id",
            valueTemplate: productValueTemplate,
            template: productTemplate,
            change: function (e) {
                if (e.sender.value() && !changed) {
                    addRepair();
                    changed = true;
                }
            },
            dataSource: {
                serverFiltering: true,
                transport: productTransport
            }
        });
    }

    $(document).ready(function () {
        kendo.culture("sv-SE");

        $("#brands").kendoDropDownList({
            optionLabel: "Välj märke",
            dataTextField: "name",
            dataValueField: "id",
            valueTemplate: valueTemplate,
            template: template,
            dataSource: {
                filter: { field: "id", operator: "eq", value: "5" },
                serverFiltering: true,
                transport: categoryTransport
            }
        });

        $("#products").kendoDropDownList({
            autoBind: false,
            optionLabel: "Välj produkt",
            cascadeFrom: "brands",
            dataTextField: "name",
            dataValueField: "id",
            valueTemplate: valueTemplate,
            template: template,
            dataSource: {
                serverFiltering: true,
                transport: categoryTransport
            }
        });

        $("#modell").kendoDropDownList({
            autoBind: false,
            cascadeFrom: "products",
            optionLabel: "Välj modell",
            dataTextField: "name",
            dataValueField: "id",
            valueTemplate: valueTemplate,
            template: template,
            dataSource: {
                serverFiltering: true,
                transport: categoryTransport
            },
            change: function () {
                $("#repairs").empty();
                addRepair();
            }
        });

        $("#repair-1").kendoDropDownList({
            autoBind: false,
            optionLabel: "Välj reparation",
            cascadeFrom: "modell",
            dataTextField: "name",
            dataValueField: "id",
            valueTemplate: productValueTemplate,
            template: productTemplate,
            dataSource: {
                serverFiltering: true,
                transport: productTransport
            }
        });

        $("#order").on("click", function () { saveRepair(); });
        $("#repairs").on("click", ".delete-repair", function () {
            var kendo = $(this).parent().parent().next();
            kendo.data("kendoDropDownList").close();
            kendo.parent().remove();
        })
    });
};