/*var with_delivery = null;
 var without_delivery = null;*/

$(document).ready(function () {
    // Cart
    $(".toCart").click(CART.toCart);
    $(".fromCart").click(CART.fromCart);
    $(".b-decinc .inc,.b-decinc .dec").click(CART.setCountCart);
    $("#amount-field_card, .amount").bind("keyup", CART.setCountCart);

    $(document).on('click', '.addDelayed', delayed.add)
    $(document).on('click', '.delDelayed', delayed.del)
    $(".del_favorte").bind("click", delayed.del_favorite)

    if ($("#check-pickup").length) {
        $("#check-pickup").bind("change", function () {
            if ($(this).prop("checked")) {
				$('input[name="rus_how"]').prop("checked", false);
				$('input[name="rus_how"]').parent().removeClass('checked');
                
				$("#with_discountshop").show();
                $("#with_discountinet").hide();
                $("#streetOrder").parent().parent().hide();
                $("#cityOrder").parent().parent().hide();
                $("#liftOrder").parent().parent().hide();
                $(".pickup_method, .ekb_method, .rf_method").hide();
                $(".pickup_method").show();
                $("#method_payment_radio").show();
                $(".js-payment-method__item").removeClass("on");
                $("input[name=radiogrouppaymentOrder]").prop("checked", false);
                $("#how_delivery_block").hide();
                delete validatorConfig['formOrder'].fields.postindexOrder;
            } else {
				$("#with_discountshop").hide();
                $("#with_discountinet").show();
                $("#streetOrder").parent().parent().show()
                $("#cityOrder").parent().parent().show()
                $("#liftOrder").parent().parent().show()
                if ($("#cityOrder").val() != "Екатеринбург") {
                    $("#how_delivery_block").show()
                    $("#liftOrder").closest(".field-row").hide()
                    $(".pickup_method, .ekb_method, .rf_method").hide();
                    $(".rf_method").show()

                    validatorConfig['formOrder'].fields.postindexOrder = {
                        noempty: true,
                        caption: "Индекс"
                    };
                }
                else {
                    $("#liftOrder").closest(".field-row").show()
                    $(".pickup_method, .ekb_method, .rf_method").hide();
                    $(".ekb_method").show()
                    $("#method_payment_radio").show()

                    delete validatorConfig['formOrder'].fields.postindexOrder;
                }
                $(".js-payment-method__item").removeClass("on")
                $("input[name=radiogrouppaymentOrder]").prop("checked", false)
            }
        })
    }

    $(".how-delivery input[type=radio]").bind("change", function () {
        //if ($(this).attr("name") == "whereDelivery") {
        //    $("#whereDelivery_" + $(this).val()).slideDown(100);
        //    var element_parent = $("#whereDelivery_" + ($(this).val() == 1 ? 2 : 1))
        //    element_parent.hide().find("input[type=radio]").prop("checked", false).parent().removeClass("checked");
        //    $("#ekb_how").parent().children().hide()
        //}
        //if ($(this).attr("name") == "ekb_how") {
        //    var element_show = $("#ekb_how").parent()
        //    element_show.children().hide()
        //    $("#ekb_how").slideDown(100);
        //}
        if ($(this).attr("name") == "rus_how") {
            //var element_show = $("#ekb_how").parent()
            $(".how-delivery__row_lvl3").children().hide()
            $("#rus_how_" + $(this).val()).slideDown(100);
            if ($(this).val() == 2) {
                $('#adress_delivery_block').slideUp();
            } else {
                $('#adress_delivery_block').slideDown();
            }
        }
    })

    $("input[name=rus_how]").bind("change", function () {
		if($(this).prop("checked")){
			$('#check-pickup-1').prop("checked", false);
			$('#check-pickup-1').parent().removeClass('checked');
			
			$('#check-pickup-2').prop("checked", false);
			$('#check-pickup-2').parent().removeClass('checked');
		}
        if ($("input[name=rus_how]").filter(":checked").val() == 2) {
            $("#rus_how_2").children(".txt-green, .txt-red").html("")
            PickPoint.open(my_function, {fromcity: 'Екатеринбург'});
            $(".pickup_method, .ekb_method, .rf_method").hide();
            $(".rf_method").show();
            $("#method_payment_radio").show();
        }
    })

    $(window).keydown(function (event) {
        if ((event.keyCode == 13) && ($(event.target).parents("#formOrder").length))
            return false
    });

    $("#formOrder").submit(function () {
		
		if (
            (($("#check-pickup").prop("checked")) && (parseInt($(".cart__price > b").html().replace(" ", "")) < 600))
            ||
            ((!$("#check-pickup").prop("checked")) && (parseInt($(".cart__price > b").html().replace(" ", "")) < 1000))
        ) {
            $(this).attr("send", "off");
            $('#modal-error-price').arcticmodal();
            $('#modal-error-price').find("input[type=button]").bind("click", function () {
                $('#modal-error-price').arcticmodal('close');
            })
            return false;
        }

        fsubmit($(this).attr('name'));
    })
});

function my_function(result) {
    var address = result["address"]
    var name = result["name"]
    var id = result["id"]
    var coeff = result["coeff"]
    var zone = result["zone"]
    $.ajax({
        type: "POST",
        url: "/cart/",
        data: {
            PT_id: id,
            address: address,
            name: name,
            coeff: coeff,
            zone: zone,
        },
        success: function (msg) {
            var msg = JSON.parse(msg)
            $("#rus_how_2").children(".txt-green").html(msg["count_days"])
            if (msg["price_delivery"])
                $("#rus_how_2").children(".txt-red").html(msg["price_delivery"])
        }
    });

}

var delayed = {
    add: function () {
        var $t = $(this),
            id = $t.attr("data-id");
        var data = {};

        if (!$(this).attr("child-id")) {
            data = {
                mode: "add_delayed",
                item_id: id,
            }
        } else {
            data = {
                mode: "add_delayed",
                item_id: id,
                child_id: $(this).attr("child-id")
            }
        }

        $.post(
            "/cabinet/",
            data,
            function (data) {
                if (data != "error") {
                    data = JSON.parse(data);
                    $t.removeClass("addDelayed").addClass("delDelayed").html("Не хочу").parents(".carousel-item__container").addClass("in_delayed")
                }
            }
        );
        return false;
    },

    del: function () {
        var $t = $(this),
            id = $t.attr("data-id");
        var data = {};

        if (!$(this).attr("child-id")) {
            data = {
                mode: "del_delayed",
                item_id: id,
            }
        } else {
            data = {
                mode: "del_delayed",
                item_id: id,
                child_id: $(this).attr("child-id")
            }
        }

        $.post(
            "/cabinet/",
            data,
            function (data) {
                if (data != "error") {
                    data = JSON.parse(data);
                    $t.removeClass("delDelayed").addClass("addDelayed").html("Хочу").parents(".carousel-item__container").removeClass("in_delayed")
                }
            }
        );
        return false;
    },
    del_favorite: function () {
        var $t = $(this),
            id = $t.attr("data-id");
        var data = {};

        if (!$(this).attr("child-id")) {
            data = {
                mode: "del_delayed",
                item_id: id,
            }
        } else {
            data = {
                mode: "del_delayed",
                item_id: id,
                child_id: $(this).attr("child-id")
            }
        }

        $.post(
            "/cabinet/",
            data,
            function (data) {
                if (data != "error") {
                    data = JSON.parse(data);
                    $t.parents(".prod-list__item").remove();
                }
            }
        );
        return false;
    }
}

function set_price_deliver(price) {
    /*if (bool_check) {
     $(".form-order__result-price > span.val").html(without_delivery + " р")
     } else {*/
    //$(".form-order__result-price > span.val").html(price + " р")
    //}
    return false;
}
function set_price_with_disc(price) {
    if ($("#with_discountinet").css("display") == "none") {
        $("#with_discountinet").show();
    }
    $("#with_discountinet span.val").html(price + " р")
    //}
}
function set_price_with_disc_shop(price) {
    if ($("#with_discountshop").length) {
        $("#with_discountshop span.val").html(price + " р")
    }
    //}
}

function check_count_cart_item(count) {
    if (!count) {
        $(".hide_block_cart").hide()
        $(".show_block_cart").show()
    }
}

/*function getPrice(check) {

 $.post(
 "/cart/",
 {
 mode: "getPrice"
 },
 function (data) {
 if (data != "error") {
 data = JSON.parse(data);
 with_delivery = data["with_delivery"];
 without_delivery = data["totalprice"];
 set_price_deliver(check);
 }
 }
 );
 }*/

var CART = {
    toCart: function () {
        var $t = $(this),
            id = $t.attr("data-id");
        var count = 1;
        if ($("#good-count").text())
            count = $("#good-count").text();
        if ($("#amount-field_card").val())
            count = $("#amount-field_card").val();
        if ($($(this).parents("table")[0]).find(".amount").length)
            count = $($(this).parents("table")[0]).find(".amount").val();
        var data = {};

        count = parseInt(count);
        if (!$(this).attr("child-id")) {
            data = {
                mode: "add",
                id: id,
                count: count
            }
        } else {
            data = {
                mode: "add",
                id: id,
                count: count,
                child_id: $(this).attr("child-id")
            }
        }

        $.post(
            "/cart/",
            data,
            function (data) {
                if (data != "error") {
                    data = JSON.parse(data);
                    $t.removeClass("toCart").addClass("cartYet")
                        .attr("href", "/cart/")
                        .unbind("click")
                        .html("<i class='icon-e icon-basket-dark'></i> Оформить заказ").parents(".carousel-item__container").addClass("in_cart");
                    $(".cart__summ > b").html(data["count"])
                    $(".cart__price > b").html(data["totalprice"])
                    /*with_delivery = data["with_delivery"]
                     without_delivery = data["totalprice"];*/
                    if ($("#check-pickup").length) {
                        set_price_deliver(data["totalprice"])
                    }
                }
            }
        );
        return false;
    },

    setCountCart: function () {
        if (($(this).attr("id") == "amount-field_card") || ($(this).hasClass("amount"))) {
            var $t = $(this),
                id = $t.attr("data-id"),
                count = $(this).val();
        }
        else {
            var $t = $(this),
                id = $t.attr("data-id"),
                element_input = ($("#amount-field_card").length ? $("#amount-field_card") : $t.parent().children(".amount")),
                count = element_input.val();
        }
        var data = {};


        if (!$(this).attr("child-id")) {
            data = {
                mode: "setCount",
                id: id,
                count: count
            }
        } else {
            data = {
                mode: "setCount",
                id: id,
                count: count,
                child_id: $(this).attr("child-id")
            }
        }

        if ($("input[name=addresstoOrder]").length && $("#check-pickup").prop("checked")) {
            if ($("input[name=addresstoOrder]").filter(":checked").val()) {
                data["pickup"] = 1;
                data["shop"] = $("input[name=addresstoOrder]").filter(":checked").val();
            }
        }

        $.post(
            "/cart/",
            data,
            function (data) {
                data = JSON.parse(data);
                $(".cart__summ > b").html(data["count"])
                $(".cart__price > b").html(data["totalprice"])
                var total_price_int = parseInt(data["totalprice"].replace(" ", ""))
                if ((total_price_int >= 600) && (total_price_int < 1000)) {
                    $("#ekb_how_2").prop("disabled", false).parent().removeClass("disabled")
                }
                if (total_price_int >= 1000) {
                    $("#ekb_how_1").prop("disabled", false).parent().removeClass("disabled")
                }
                if (total_price_int < 600) {
                    $("#ekb_how_2").prop("disabled", true).parent().addClass("disabled")
                    if ($("#ekb_how_2").prop("checked")) {
                        $("#ekb_how_2").prop("checked", false).parent().removeClass("checked")
                        $("#ekb_how").hide()
                    }
                }
                if ((total_price_int < 1000) && (total_price_int >= 600)) {
                    $("#ekb_how_1").prop("disabled", true).parent().addClass("disabled")
                    if ($("#ekb_how_1").prop("checked")) {
                        $("#ekb_how_1").prop("checked", false).parent().removeClass("checked")
                        $("#ekb_how").hide()
                    }
                }
                if ($(".b-cart-table__result-summ .val.val-upd").length) {
                    $(".b-cart-table__result-summ .val.val-upd").html(data["totalprice"])
                }
                if ($t.parents("tr").find(".b-cart-table__price-summ.upd").length) {
                    $t.parents("tr").find(".b-cart-table__price-summ.upd").html(data["total_price_item"])
                }
                if ($t.parents("tr").find(".b-cart-table__price_oldprice.old-upd").length) {
                    $t.parents("tr").find(".b-cart-table__price_oldprice.old-upd").html(data["total_oldprice_item"])
                }
                
                $t = $('#total-price-dif');
                if (data["totaldifprice"]) {
                    $t.html(data["totaldifprice"]);
                    $t.closest('tr').show();
                } else {
                    $t.closest('tr').hide();
                }
                
                
                if ($t.parents("tr").find(".b-cart-table__date").length) {
                    $t.parents("tr").find(".b-cart-table__date").html(data["date"]+"*")
                    var d1 = new Date(data["date"]);
                    var d2 = new Date($(".result-date .val").html());
                    if (d1 >= d2) {
                        $t.parents(".b-cart-table").find(".result-date .val").html(data["date"])
                        //if ($("input[name=addresstoOrder]").length && $("#check-pickup").prop("checked")) {
                        //    if ($("input[name=addresstoOrder]").filter(":checked").val() == 1) {
                        //        $("#check-pickup-1-styler").parent().find(".result-date .val").html(data["date"])
                        //    }
                        //    if ($("input[name=addresstoOrder]").filter(":checked").val() == 2) {
                        //        $("#check-pickup-2-styler").parent().find(".result-date .val").html(data["date"])
                        //    }
                        //}
                    }
                }
                if ($("#check-pickup").length) {
                    set_price_deliver(data["totalprice"])
                }
                if (data["price_with_disc"]) {
                    set_price_with_disc(data["price_with_disc"])
                }
                if (data["price_with_disc_shop"]) {
                    set_price_with_disc_shop(data["price_with_disc_shop"])
                }
            }
        );
        return false;
    },

    fromCart: function () {
        var $t = $(this),
            id = $t.attr("data-id");
        var data = {};

        if (!$(this).attr("child-id")) {
            data = {
                mode: "remove",
                id: id
            }
        } else {
            data = {
                mode: "remove",
                id: id,
                child_id: $(this).attr("child-id")
            }
        }
        $.post(
            "/cart/",
            data,
            function (data) {
                if (data != "error") {
                    data = JSON.parse(data);
                    $(".cart__summ > b").html(data["count"])
                    $(".cart__price > b").html(data["totalprice"])
                    if ($(".b-cart-table__result-summ .val.val-upd").length) {
                        $(".b-cart-table__result-summ .val.val-upd").html(data["totalprice"])
                    }
                    /*with_delivery = data["with_delivery"]
                     without_delivery = data["totalprice"];
                     if ($("#check-pickup").length) {
                     set_price_deliver($("#check-pickup").prop("checked"))
                     }*/
                    if ($("#check-pickup").length) {
                        set_price_deliver(data["totalprice"])
                    }
                    if (data["price_with_disc"]) {
                        set_price_with_disc(data["price_with_disc"])
                    }
                    if (data["price_with_disc_shop"]) {
                        set_price_with_disc_shop(data["price_with_disc_shop"])
                    }
                    
                    $t = $('#total-price-dif');
                    if (data["totaldifprice"]) {
                        $t.html(data["totaldifprice"]);
                        $t.closest('tr').show();
                    } else {
                        $t.closest('tr').hide();
                    }
                

                    check_count_cart_item(data["count"]);
                }
            }
        );
        return false;
    }

};


var successSendOrder = function (param) {
    $('#modal-send-order').arcticmodal();
    $('#modal-send-order').find("input[type=button]").bind("click", function () {
        window.location.href = "/";
    })
};
/*var successSendOrder = function (param) {
 var id = "#successSendRobokassa",
 $w,
 effectIn = "bounceIn";

 if (param != "") {
 $("#robokassa-content").html(param);
 }
 else {
 id = "#successSendOrder";
 }


 $("#sendOrder").hide();


 $.fancybox.close();

 if (id === "#successSendRobokassa") {
 $.fancybox(id,
 {
 autoWidth: true,
 autoHeight: true,
 fitToView: true,
 autoSize: true,
 closeClick: false,
 openEffect: 'none',
 closeEffect: 'none',
 closeBtn: false,
 wrapCSS: 'form',
 padding: 0,
 openSpeed: 0,
 closeSpeed: 0,
 modal: true
 }
 );
 }
 else {
 $.fancybox(id,
 {
 autoWidth: true,
 autoHeight: true,
 fitToView: true,
 autoSize: true,
 closeClick: false,
 openEffect: 'none',
 closeEffect: 'none',
 closeBtn: false,
 wrapCSS: 'form',
 padding: 0,
 openSpeed: 0,
 closeSpeed: 0
 }
 );
 }

 $w = $(".fancybox-wrap");

 $w.addClass("animated " + effectIn);
 $w.one('webkitAnimationEnd mozAnimationEnd MSAnimationEnd oanimationend animationend', function () {
 $w.removeClass("animated " + effectIn);
 });

 window.location.hash = "Order?form_send";
 };*/
