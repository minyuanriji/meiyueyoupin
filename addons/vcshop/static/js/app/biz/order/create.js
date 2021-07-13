define(["core", "tpl", "biz/plugin/diyform", "biz/order/invoice"], function(f, e, c, t) {
	var _ = {
		params: {
			orderid: 0,
			goods: [],
			coupon_goods: [],
			merchs: [],
			iscarry: 0,
			isverify: 0,
			isvirtual: 0,
			isonlyverifygoods: 0,
			dispatch_price: 0,
			deductenough_enough: 0,
			merch_deductenough_enough: 0,
			deductenough_money: 0,
			merch_deductenough_money: 0,
			addressid: 0,
			contype: 0,
			couponid: 0,
			card_id: 0,
			card_price: 0,
			wxid: 0,
			wxcardid: 0,
			wxcode: "",
			isnodispatch: 0,
			nodispatch: "",
			packageid: 0,
			card_packageid: 0,
			new_area: "",
			address_street: "",
			discountprice: 0,
			isdiscountprice: 0,
			lotterydiscountprice: 0,
			gift_price: 0,
			giftid: 0,
			show_card: !1,
			city_express_state: !1,
		},
		invoice_info: {},
		init: function(e, t) {
			t && t.title && (_.invoice_info = t), _.params = $.extend(_.params, e || {}), _.params.couponid = 0, $("#coupondiv").find(".fui-cell-label").html("优惠券"), $("#coupondiv").find(".fui-cell-info").html("");
			var i = f.getNumber($(".discountprice").val()),
				r = f.getNumber($(".isdiscountprice").val());
			0 < i && $(".discount").show(), 0 < r && $(".isdiscount").show(), _.params.city_express_state ? ($(".fui-cell-group.city_express.external").show(), $("#showdispatchprice div:first-child").text("同城运费")) : ($(".fui-cell-group.city_express.external").hide(), $("#showdispatchprice div:first-child").text(_.params.freight));
			var a = !1;
			if (void 0 !== window.selectedAddressData) a = window.selectedAddressData;
			else if (void 0 !== window.editAddressData)(a = window.editAddressData).address = a.areas.replace(/ /gi, "") + " " + a.address;
			else {
				var d = _.getCookie("id"),
					c = _.getCookie("mobile"),
					o = decodeURIComponent(_.getCookie("realname")),
					s = decodeURIComponent(_.getCookie("addressd"));
				0 < d && (a = {
					id: d,
					mobile: c,
					address: s,
					realname: o
				})
			}
			a && (_.params.addressid = a.id, $("#addressInfo .has-address").show(), $("#addressInfo .no-address").hide(), $("#addressInfo .icon-dingwei").show(), $("#addressInfo .realname").html(a.realname), $("#addressInfo .mobile").html(a.mobile), $("#addressInfo .address").html(a.address), $("#addressInfo a").attr("href", f.getUrl("member/address/selector")), $("#addressInfo a").click(function() {
				window.orderSelectedAddressID = a.id
			}));
			var n = !(document.cookie = "id=0");
			if (void 0 !== window.selectedStoreData) {
				n = window.selectedStoreData, _.params.storeid = n.id;
				var u = "#carrierInfo";
				1 == _.params.isforceverifystore && 1 == _.params.isverify && (u = "#forceStoreInfo"), $(u + " .storename").html(n.storename), $(u + " .realname").html(n.realname), $(u + "_mobile").html(n.mobile), $(u + " .address").html(n.address), $(u).find(".no-address").css("display", "none"), $(u).find(".has-address").css("display", "block"), $(u).find(".fui-list-media").css("display", "block"), $(u).find(".text").css("display", "block"), $(u).find(".title").css("display", "block"), $(u).find(".subtitle").css("display", "block")
			}
			FoxUI.tab({
				container: $("#carrierTab"),
				handlers: {
					tab1: function() {
						$(".exchange-withoutpostage").hide(), $(".exchange-withpostage").show(), _.params.iscarry = 0, $("#addressInfo").show(), $("#carrierInfo").hide(), $("#memberInfo").hide(), $("#showdispatchprice").show(), _.caculate()
					},
					tab2: function() {
						_.params.iscarry = 1, $(".exchange-withpostage").hide(), $(".exchange-withoutpostage").show(), $("#addressInfo").hide(), $("#carrierInfo").show(), $("#memberInfo").show(), $("#showdispatchprice").hide(), _.caculate()
					}
				}
			});
			var m = $(".fui-number");
			if (0 < m.length) {
				var l = m.data("maxbuy") || 0,
					p = m.data("goodsid"),
					h = m.data("minbuy") || 0,
					g = m.data("unit") || "件";
				m.numbers({
					max: l,
					min: h,
					minToast: "{min}" + g + "起售",
					maxToast: "最多购买{max}" + g,
					callback: function(e) {
						$.each(_.params.goods, function() {
							if (this.goodsid == p) return this.total = e, !1
						}), $.each(_.params.coupon_goods, function() {
							if (this.goodsid == p) return this.total = e, !1
						}), _.params.contype = 0, _.params.couponid = 0, _.params.wxid = 0, _.params.wxcardid = "", _.params.wxcode = "", _.params.couponmerchid = 0, $("#coupondiv").find(".fui-cell-label").html("优惠券"), $("#coupondiv").find(".fui-cell-info").html(""), $("#goodscount").html(e);
						var t = f.getNumber(m.closest(".goods-item").find(".marketprice").html()) * e;
						$(".goodsprice").html(f.number_format(t, 2)), _.caculate()
					}
				})
			}
			$("#deductcredit").click(function() {
				_.calcCouponPrice(), _.calcCardPrice()
			}), $("#deductcredit2").click(function() {
				_.calcCouponPrice(), _.calcCardPrice()
			}), _.bindCoupon(), _.bindCard(), $(document).click(function() {
				$("input,select,textarea").each(function() {
					$(this).attr("data-value", $(this).val())
				}), $(":checkbox,:radio").each(function() {
					$(this).attr("data-checked", $(this).prop("checked"))
				})
			}), $("input,select,textarea").each(function() {
				var e = $(this).attr("data-value") || "";
				"" != e && $(this).val(e)
			}), $(":checkbox,:radio").each(function() {
				var e = "true" === $(this).attr("data-checked");
				$(this).prop("checked", e)
			}), $(".buybtn").click(function() {
				_.submit(this, e.token)
			}), _.caculate(), $(".fui-cell-giftclick").click(function() {
				_.giftPicker = new FoxUIModal({
					content: $("#gift-picker-modal").html(),
					extraClass: "picker-modal",
					maskClick: function() {
						_.giftPicker.close()
					}
				}), _.giftPicker.container.find(".btn-danger").click(function() {
					_.giftPicker.close()
				}), _.giftPicker.show();
				var e = $("#giftid").val();
				$(".gift-item").each(function() {
					$(this).val() == e && $(this).prop("checked", "true")
				}), $(".gift-item").on("click", function() {
					$.ajax({
						url: f.getUrl("goods/detail/querygift", {
							id: $(this).val()
						}),
						cache: !0,
						success: function(e) {
							0 < (e = window.JSON.parse(e)).status && ($("#giftid").val(e.result.id), $("#gifttitle").text(e.result.title))
						}
					})
				})
			}), $(".show-allshop-btn").click(function() {
				$(this).closest(".store-container").addClass("open")
			}), _.initaddress(), $(".card-list-modal").click(function() {
				$(".card-list-modal").removeClass("in"), $(".card-list-group").removeClass("in")
			})
		},
		getCookie: function(e) {
			for (var t = e + "=", i = document.cookie.split(";"), r = 0; r < i.length; r++) {
				for (var a = i[r];
				" " == a.charAt(0);) a = a.substring(1);
				if (-1 != a.indexOf(t)) return a.substring(t.length, a.length)
			}
			return ""
		},
		giftPicker: function() {
			_.giftPicker = new FoxUIModal({
				content: $("#option-picker-modal").html(),
				extraClass: "picker-modal",
				maskClick: function() {
					_.packagePicker.close()
				}
			})
		},
		bindCard: function() {
			$("#selectCard").unbind("click").click(function() {
				$("#cardloading").show(), $("#showdispatchprice div:first-child").text("同城运费"), f.json("membercard/query", {
					money: 0,
					type: 0,
					goods: _.params.goods,
					discountprice: _.params.discountprice,
					isdiscountprice: _.params.isdiscountprice
				}, function(t) {
					$("#cardloading").hide(), 0 < t.result.cards.length || 0 < t.result.wxcards.length ? ($("#selectCard").show().find(".badge").html(t.result.cards.length).show(), $("#selectCard").find(".text-danger").hide(), require(["../addons/vcshop/plugin/membercard/static/js/picker.js"], function(e) {
						e.show({
							card_id: _.params.card_id,
							cards: t.result.cards,
							onCancel: function() {
								_.params.card_id = 0, $("#selectCard").find(".fui-cell-label").html("不使用会员卡"), $("#selectCard").find(".fui-cell-info").html(""), _.calcCardPrice()
							},
							onCaculate: function() {
								_.params.card_id = 0, _.caculate()
							},
							onSelected: function(e) {
								_.params.card_id = e.card_id, _.params.card_price = e.card_price, $("#selectCard").find(".fui-cell-label").html("已选择"), $("#selectCard").find(".fui-cell-info").html(e.card_name), $("#selectCard").data(e), _.calcCardPrice()
							}
						})
					})) : (FoxUI.toast.show("未找到会员卡!"), _.hideCard())
				}, !1, !0)
			})
		},
		hideCard: function() {
			$("#selectCard").hide(), $("#selectCard").find(".badge").html("0").hide(), $("#selectCard").find(".text").show()
		},
		hideCoupon: function() {
			$("#coupondiv").hide(), $("#coupondiv").find(".badge").html("0").hide(), $("#coupondiv").find(".text").show()
		},
		caculate: function() {
			var e = f.getNumber($(".goodsprice").html()) - f.getNumber($(".taskdiscountprice").val()) - f.getNumber($(".lotterydiscountprice").val()) - f.getNumber($(".discountprice").val()) - f.getNumber($(".isdiscountprice").val()) - f.getNumber($("#taskcut").val());
			0 < $(".shownum").length && (e = f.getNumber($(".marketprice").html()) * parseInt($(".shownum").val())), 0 == _.params.fromcart && 1 == _.params.goods.length && (_.params.goods[0].total = parseInt($(".shownum").val())), void 0 !== window.selectedAddressData && (_.params.addressid = window.selectedAddressData.id), f.json("order/create/caculate", {
				totalprice: e,
				addressid: _.params.addressid,
				dispatchid: _.params.dispatchid,
				dflag: _.params.iscarry,
				goods: _.params.goods,
				packageid: _.params.card_packageid,
				liveid: _.params.liveid,
				card_id: _.params.card_id,
				giftid: _.params.giftid,
				goods_dispatch: _.params.goods_dispatch
			}, function(e) {
				if (1 == e.status) {
					$.each(e.result.goods, function(e, i) {
						$.each(_.params.coupon_goods, function(e, t) {
							i.goodsid == t.goodsid && (_.params.coupon_goods[e].discounttype = i.discounttype, _.params.coupon_goods[e].discountunitprice = i.discountunitprice, _.params.coupon_goods[e].isdiscountunitprice = i.isdiscountunitprice)
						})
					}), console.log(_.params.coupon_goods);
					var t = parseInt($(".shownum").val()),
						i = _.params.goods[0].goodsid + "_" + t + "_isgift";
					if (0 == _.params.fromcart && 1 == e.result.isgift && 0 == _.params.giftid && 1 != _.getCookie(i)) {
						_.params.goods[0].goodsid, _.params.goods[0].optionid, _.params.giftid, _.params.liveid;
						document.cookie = i + "=1"
					} else document.cookie = i + "=-1";
					_.params.iscarry ? $(".dispatchprice").html("0.00") : $(".dispatchprice").html(f.number_format(e.result.price, 2)), e.result.taskdiscountprice && $("#taskdiscountprice").val(f.number_format(e.result.taskdiscountprice, 2)), e.result.lotterydiscountprice && $("#lotterydiscountprice").val(f.number_format(e.result.lotterydiscountprice, 2)), e.result.discountprice && $("#discountprice").val(f.number_format(e.result.discountprice, 2)), e.result.buyagain && ($("#buyagain").val(f.number_format(e.result.buyagain, 2)), $("#showbuyagainprice").html(f.number_format(e.result.buyagain, 2)).parents(".fui-cell").show()), e.result.isdiscountprice && $("#isdiscountprice").val(f.number_format(e.result.isdiscountprice, 2)), e.result.deductcredit && ($("#deductcredit_money").html(f.number_format(e.result.deductmoney, 2)), $("#deductcredit_info").html(e.result.deductcredit), $("#deductcredit").data("credit", e.result.deductcredit), $("#deductcredit").data("money", f.number_format(e.result.deductmoney, 2))), e.result.deductcredit2 && ($("#deductcredit2_money").html(e.result.deductcredit2), $("#deductcredit2").data("credit2", e.result.deductcredit2)), 0 < e.result.include_dispath ? $("#include_dispath").show() : $("#include_dispath").hide(), 0 < e.result.seckillprice ? ($("#seckillprice").show(), $("#seckillprice_money").html(f.number_format(e.result.seckillprice, 2))) : ($("#seckillprice").hide(), $("#seckillprice_money").html(0)), 0 < e.result.couponcount ? ($("#coupondiv").show().find(".badge").html(e.result.couponcount).show(), $("#coupondiv").find(".text").hide()) : (_.params.couponid = 0, $("#coupondiv").hide().find(".badge").html(0).hide()), 0 < e.result.merch_deductenough_money ? ($("#merch_deductenough").show(), $("#merch_deductenough_money").html(f.number_format(e.result.merch_deductenough_money, 2)), $("#merch_deductenough_enough").html(f.number_format(e.result.merch_deductenough_enough, 2))) : ($("#merch_deductenough").hide(), $("#merch_deductenough_money").html("0.00"), $("#merch_deductenough_enough").html("0.00")), 0 < e.result.deductenough_money ? ($("#deductenough").show(), $("#deductenough_money").html(f.number_format(e.result.deductenough_money, 2)), $("#deductenough_enoughdeduct").html(f.number_format(e.result.deductenough_money, 2)), $("#deductenough_enough").html(f.number_format(e.result.deductenough_enough, 2))) : ($("#deductenough").hide(), $("#deductenough_money").html("0.00"), $("#deductenough_enoughdeduct").html("0.00"), $("#deductenough_enough").html("0.00")), e.result.merchs && (_.params.merchs = e.result.merchs), 1 == e.result.isnodispatch ? (_.isnodispatch = 1, _.nodispatch = e.result.nodispatch, FoxUI.toast.show(_.nodispatch)) : (_.isnodispatch = 0, _.nodispatch = ""), _.params.city_express_state = e.result.city_express_state, _.params.city_express_state ? ($(".fui-cell-group.city_express.external").show(), $("#showdispatchprice div:first-child").text("同城运费")) : ($(".fui-cell-group.city_express.external").hide(), $("#showdispatchprice div:first-child").text(_.params.freight)), 0 < _.params.liveid && 0 == _.params.card_id && ($(".goodsprice").html(f.number_format(e.result.realprice - e.result.price, 2)), $(".marketprice").html(f.number_format(e.result.realprice - e.result.price, 2))), _.calcCouponPrice(), _.calcCardPrice()
				}
			}, !0, !0)
		},
		totalPrice: function(e) {
			var t = f.getNumber($(".goodsprice").html());
			0 < _.params.packageid || 0 < _.params.card_packageid ? (t = f.getNumber($(".bigprice-packageprice").html()), $("#showpackageprice").show()) : $("#showpackageprice").hide();
			var i = t - f.getNumber($(".showtaskdiscountprice").html()) - f.getNumber($(".showlotterydiscountprice").html()) - f.getNumber($(".showdiscountprice").html()) - f.getNumber($(".showisdiscountprice").html()) - e - f.getNumber($("#buyagain").val()),
				r = f.getNumber($("#taskcut").val()),
				a = f.getNumber($(".dispatchprice").html()),
				d = (f.getNumber($("#deductenough_enough").html()), f.getNumber($("#merch_deductenough_enough").html()), f.getNumber($("#merch_deductenough_money").html())),
				c = f.getNumber($("#deductenough_money").html());
			d = 0;
			0 < $("#merch_deductenough_money").length && "" != $("#merch_deductenough_money").html() && (d = f.getNumber($("#merch_deductenough_money").html()));
			c = 0;
			0 < $("#deductenough_money").length && "" != $("#deductenough_money").html() && (c = f.getNumber($("#deductenough_money").html()));
			0 < _.params.card_packageid && 0 == _.params.card_id && (i -= f.getNumber($(".packageprice").html())), i = i - d - c + a - r;
			var o = 0;
			if (0 < $("#deductcredit").length) {
				var s = f.getNumber($("#deductcredit").data("credit"));
				if ($("#deductcredit").prop("checked")) {
					if (o = f.getNumber($("#deductcredit").data("money")), 0 < $("#deductcredit2").length) {
						var n = f.getNumber($("#deductcredit2").data("credit2"));
						if (0 <= i - o) n < (s = i - o) && (s = n), $("#deductcredit2_money").html(f.number_format(s, 2))
					}
				} else if (0 < $("#deductcredit2").length) {
					s = f.getNumber($("#deductcredit2").data("credit2"));
					$("#deductcredit2_money").html(f.number_format(s, 2))
				}
			}
			var u = 0;
			0 < $("#deductcredit2").length && $("#deductcredit2").prop("checked") && (u = f.getNumber($("#deductcredit2_money").html()));
			var m = 0;
			if (0 < $("#seckillprice_money").length && "" != $("#seckillprice_money").html() && (m = f.getNumber($("#seckillprice_money").html())), (i = i - o - u - m) <= 0 && (i = 0), 0 < _.params.gift_price && t > parseInt(_.params.gift_price) ? ($(".giftdiv").show(), $("#giftid").val(_.params.giftid)) : ($(".giftdiv").hide(), $("#giftid").val(0)), $(".totalprice").html(f.number_format(i)), (0 < _.params.packageid || 0 < _.params.card_packageid) && $(".total-packageid").html(f.number_format(i)), 0 < $("#deductcredit2").length) n = f.getNumber($("#deductcredit2").data("credit2")), f.getNumber($("#coupondeduct_money").html());
			if (0 < $("#deductcredit").length) n = f.getNumber($("#deductcredit").data("credit")), f.getNumber($("#coupondeduct_money").html());
			return _.bindCoupon(), _.bindCard(), i
		},
		calcCouponPrice: function() {
			var e = f.getNumber($(".goodsprice").html()),
				r = 0,
				t = f.getNumber($(".taskdiscountprice").val()),
				i = f.getNumber($("#taskcut").val()),
				a = f.getNumber($(".lotterydiscountprice").val()),
				d = f.getNumber($(".discountprice").val()),
				c = f.getNumber($(".isdiscountprice").val()),
				o = f.getNumber($("#carddeduct_money").html());
			if (0 == _.params.couponid && 0 == _.params.wxid) return $("#coupondeduct_div").hide(), $("#coupondeduct_text").html(""), $("#coupondeduct_money").html("0"), 0 < t ? ($(".showtaskdiscountprice").html($(".taskdiscountprice").val()), $(".istaskdiscount").show()) : $(".istaskdiscount").hide(), 0 < i ? ($(".showtaskcut").html($("#taskcut").val()), $(".taskcut").show()) : $(".istaskdiscount").hide(), 0 < a ? ($(".showlotterydiscountprice").html($(".lotterydiscountprice").val()), $(".islotterydiscount").show()) : $(".islotterydiscount").hide(), 0 < d ? ($(".showdiscountprice").html($(".discountprice").val()), $(".discount").show()) : $(".discount").hide(), 0 < c ? ($(".showisdiscountprice").html($(".isdiscountprice").val()), $(".isdiscount").show()) : $(".isdiscount").hide(), 0 < o ? $("#carddeduct_div").show() : $("#carddeduct_div").hide(), 0 < o ? _.totalPrice(o) : _.totalPrice(0);
			f.json("order/create/getcouponprice", {
				goods: _.params.coupon_goods,
				goodsprice: e,
				real_price: _.realPrice(),
				couponid: _.params.couponid,
				contype: _.params.contype,
				wxid: _.params.wxid,
				wxcardid: _.params.wxcardid,
				wxcode: _.params.wxcode,
				discountprice: d,
				isdiscountprice: c
			}, function(e) {
				if (1 == e.status) {
					$("#coupondeduct_text").html(e.result.coupondeduct_text), r = e.result.deductprice;
					var t = e.result.discountprice,
						i = e.result.isdiscountprice;
					0 < t ? ($(".showdiscountprice").html(t), $(".discount").show()) : ($(".showdiscountprice").html(0), $(".discount").hide()), 0 < i ? ($(".showisdiscountprice").html(i), $(".isdiscount").show()) : ($(".showisdiscountprice").html(0), $(".isdiscount").hide()), 0 < r ? ($("#coupondeduct_div").show(), $("#coupondeduct_money").html(f.number_format(r, 2))) : ($("#coupondeduct_div").hide(), $("#coupondeduct_text").html(""), $("#coupondeduct_money").html("0"))
				} else 0 < d ? ($(".showdiscountprice").html($(".discountprice").val()), $(".discount").show()) : $(".discount").hide(), 0 < c ? ($(".showisdiscountprice").html($(".isdiscountprice").val()), $(".isdiscount").show()) : $(".isdiscount").hide(), r = 0;
				return 0 < o ? _.totalPrice(o + r) : _.totalPrice(r)
			}, !0, !0)
		},
		calcCardPrice: function() {
			if (_.params.show_card) {
				var e = f.getNumber($(".goodsprice").html());
				0 < _.params.packageid && (e = f.getNumber($(".bigprice-packageprice").html())), $("#carddeduct_div").hide(), $("#carddeduct_text").html(""), $("#carddeduct_money").html("0");
				var d = 0,
					c = (f.getNumber($(".taskdiscountprice").val()), f.getNumber($("#taskcut").val()), f.getNumber($(".lotterydiscountprice").val()), f.getNumber($(".discountprice").val())),
					o = f.getNumber($(".isdiscountprice").val()),
					s = f.getNumber($("#coupondeduct_money").html()),
					n = (f.getNumber($("#deductenough_enough").html()), f.getNumber($("#merch_deductenough_enough").html())),
					u = "";
				if (0 == _.params.card_id) return 0 < _.params.card_packageid ? $("#showpackageprice").show() : $("#showpackageprice").hide(), $("#taskcut").val(_.params.taskcut), $(".showtaskcut").html(_.params.taskcut), 0 < _.params.taskcut && (_.params.discountprice = 0, _.params.isdiscountprice = 0, _.params.deductenough_enough = 0, _.params.merch_deductenough_enough = 0, $("#deductenough_enough").html("0.00"), $("#merch_deductenough_enough").html("0.00"), $("#deductenough_money").html("0.00"), $("#merch_deductenough_money").html("0.00"), $("#deductenough").hide(), $("#merch_deductenough").hide()), 0 < _.params.lotterydiscountprice && (_.params.discountprice = 0, _.params.isdiscountprice = 0), 0 < _.params.card_packageid && (_.params.discountprice = 0, _.params.isdiscountprice = 0, $("#showdiscountprice").html("0"), $("#discountprice").attr("value", "0"), $(".discount").hide(), $("#showisdiscountprice").html("0"), $("#isdiscountprice").attr("value", "0"), $(".isdiscount").hide(), $("#deductenough").hide(), $("#deductenough_money").html("0.00"), $("#deductenough_enoughdeduct").html("0.00"), $("#deductenough_enough").html("0.00"), $("#merch_deductenough").hide(), $("#merch_deductenough_money").html("0.00"), $("#merch_deductenough_enough").html("0.00")), 0 < _.params.liveid && (_.params.discountprice = 0, _.params.isdiscountprice = 0), 0 < _.params.lotterydiscountprice && $("#lotterydiscountprice").val(_.params.lotterydiscountprice), 0 < _.params.lotterydiscountprice && $(".showlotterydiscountprice").html(_.params.lotterydiscountprice), f.getNumber($(".dispatchprice").html()) <= 0 && (_.params.iscarry ? $(".dispatchprice").html("0.00") : $(".dispatchprice").html(f.number_format(_.params.dispatch_price, 2))), 0 < _.params.discountprice && ($("#discountprice").attr("value", _.params.discountprice), $(".showdiscountprice").html(_.params.discountprice)), 0 < _.params.isdiscountprice && ($("#isdiscountprice").attr("value", _.params.isdiscountprice), $(".showisdiscountprice").html(_.params.isdiscountprice)), 0 < c || 0 < _.params.discountprice ? $(".discount").show() : $(".discount").hide(), 0 < o || 0 < _.params.isdiscountprice ? $(".isdiscount").show() : $(".isdiscount").hide(), _.params.city_express_state ? ($(".fui-cell-group.city_express.external").show(), $("#showdispatchprice div:first-child").text("同城运费")) : ($(".fui-cell-group.city_express.external").hide(), $("#showdispatchprice div:first-child").text(_.params.freight)), s ? _.totalPrice(s) : _.totalPrice(0);
				f.json("order/create/getcardprice", {
					goods: _.params.goods,
					goodsprice: e,
					card_id: _.params.card_id,
					liveid: _.params.liveid,
					card_price: _.params.card_price,
					deductenough_enough: _.params.deductenough_enough,
					merch_deductenough_enough: _.params.merch_deductenough_enough,
					dispatch_price: _.params.dispatch_price,
					lotterydiscountprice: _.params.lotterydiscountprice,
					taskcut: _.params.taskcut,
					discountprice: _.params.discountprice,
					isdiscountprice: _.params.isdiscountprice,
					goods_dispatch: _.params.goods_dispatch
				}, function(e) {
					if (1 == e.status) {
						$("#discountprice").attr("value", e.result.discountprice), $("#isdiscountprice").attr("value", e.result.isdiscountprice);
						var t = e.result.discountprice,
							i = e.result.isdiscountprice;
						0 < t ? ($(".showdiscountprice").html(t), $(".discount").show()) : ($(".showdiscountprice").html(0), $(".discount").hide()), 0 < i ? ($(".showisdiscountprice").html(i), $(".isdiscount").show()) : ($(".showisdiscountprice").html(0), $(".isdiscount").hide()), $("#carddeduct_text").html(e.result.carddeduct_text), d = e.result.carddeductprice, u = e.result.cardname, $("#selectCard").find(".fui-cell-label").html("已选择"), $("#selectCard").find(".fui-cell-info").html(u), $(".islotterydiscount").hide();
						var r = e.result.dispatch_price;
						1 == _.params.iscarry && 0 < r && (r = 0), $(".dispatchprice").html(f.number_format(r, 2)), 0 < d && ($("#carddeduct_div").show(), $("#carddeduct_money").html(f.number_format(d, 2))), $("#taskcut").val(f.number_format(e.result.taskcut, 2)), $(".showtaskcut").html(f.number_format(e.result.taskcut, 2)), 0 < e.result.taskcut ? $(".taskcut").show() : $(".taskcut").hide(), $("#lotterydiscountprice").val(f.number_format(e.result.lotterydiscountprice, 2)), $(".showlotterydiscountprice").html(f.number_format(e.result.lotterydiscountprice, 2)), e.result.deductcredit && ($("#deductcredit_money").html(f.number_format(e.result.deductmoney, 2)), $("#deductcredit_info").html(e.result.deductcredit), $("#deductcredit").data("credit", e.result.deductcredit), $("#deductcredit").data("money", f.number_format(e.result.deductmoney, 2))), e.result.deductcredit2 && ($("#deductcredit2_money").html(e.result.deductcredit2), $("#deductcredit2").data("credit2", e.result.deductcredit2)), 0 == e.result.dispatch_price && 1 == e.result.shipping ? $("#showdispatchprice div:first-child").html("运费(<span style='color: #ff0000'>会员卡包邮</span>)") : _.params.city_express_state ? ($(".fui-cell-group.city_express.external").show(), $("#showdispatchprice div:first-child").text("同城运费")) : ($(".fui-cell-group.city_express.external").hide(), $("#showdispatchprice div:first-child").text(_.params.freight));
						var a = f.getNumber($(".packageprice").html());
						0 < _.params.card_packageid && 0 < a || a <= 0 ? $("#showpackageprice").hide() : $("#showpackageprice").show(), 0 < e.result.deductenough_money ? ($("#deductenough").show(), $("#deductenough_money").html(f.number_format(e.result.deductenough_money, 2)), $("#deductenough_enoughdeduct").html(f.number_format(e.result.deductenough_money, 2)), $("#deductenough_enough").html(f.number_format(e.result.deductenough_enough, 2))) : ($("#deductenough").hide(), $("#deductenough_money").html("0.00"), $("#deductenough_enoughdeduct").html("0.00"), $("#deductenough_enough").html("0.00")), e.result.totalprice < n && ($("#merch_deductenough").hide(), $("#merch_deductenough_money").html("0.00"), $("#merch_deductenough_enough").html("0.00")), 0 < _.params.liveid && ($(".goodsprice").html(f.number_format(e.result.goodsprice, 2)), $(".marketprice").html(f.number_format(e.result.goodsprice, 2)))
					} else 0 < c ? ($(".showdiscountprice").html($(".discountprice").val()), $(".discount").show()) : $(".discount").hide(), 0 < o ? ($(".showisdiscountprice").html($(".isdiscountprice").val()), $(".isdiscount").show()) : $(".isdiscount").hide(), d = 0;
					return 0 < _.params.couponid && _.calcCouponPrice(), 0 < s ? _.totalPrice(d + s) : _.totalPrice(d)
				}, !0, !0)
			}
		},
		submit: function(e, t) {
			var i = $(e),
				r = parseInt($("#giftid").val());
			if (_.params.mustbind, !i.attr("stop")) {
				if (_.params.iscarry || _.params.isverify || _.params.isvirtual || _.params.isonlyverifygoods) {
					if ($(":input[name=carrier_realname]").isEmpty() && 0 == $(":input[name=carrier_realname]").attr("data-set")) return void FoxUI.toast.show("请填写联系人");
					if ($(":input[name=carrier_mobile]").isEmpty() && 0 == $(":input[name=carrier_mobile]").attr("data-set")) return void FoxUI.toast.show("请填写联系电话");
					if (!$(":input[name=carrier_mobile]").isMobile() && 0 == $(":input[name=carrier_mobile]").attr("data-set")) return void FoxUI.toast.show("联系电话需请填写11位手机号")
				}
				if (_.params.isonlyverifygoods) {
					if (1 == _.params.isforceverifystore && 0 == _.params.storeid) return void FoxUI.toast.show("请选择核销门店")
				} else if (_.params.iscarry || _.params.isverify || _.params.isvirtual) {
					if (_.params.iscarry && 0 == _.params.storeid) return void FoxUI.toast.show("请选择自提点");
					if (1 == _.params.isforceverifystore && 0 == _.params.storeid) return void FoxUI.toast.show("请选择核销门店")
				} else {
					if (0 == _.params.addressid) return void FoxUI.toast.show("请选择收货地址");
					if (1 == _.isnodispatch) return void FoxUI.toast.show(_.nodispatch)
				}
				var a = "";
				if (_.params.has_fields) if (!(a = c.getData(".diyform-container"))) return;
				0 == _.params.fromcart && 1 == _.params.goods.length && (_.params.goods[0].total = parseInt($(".shownum").val())), i.attr("stop", 1);
				var d = {
					orderid: _.params.orderid,
					id: _.params.id,
					goods: _.params.goods,
					card_id: _.params.card_id,
					giftid: r,
					gdid: _.params.gdid,
					liveid: _.params.liveid,
					diydata: a,
					dispatchtype: _.params.iscarry ? 1 : 0,
					fromcart: _.params.fromcart,
					carrierid: _.params.iscarry ? _.params.storeid : 0,
					addressid: _.params.iscarry ? 0 : _.params.addressid,
					carriers: _.params.iscarry || _.params.isvirtual || _.params.isverify ? {
						carrier_realname: $(":input[name=carrier_realname]").val(),
						carrier_mobile: $(":input[name=carrier_mobile]").val(),
						realname: $("#carrierInfo .realname").html(),
						mobile: $("#carrierInfo_mobile").html(),
						storename: $("#carrierInfo .storename").html(),
						address: $("#carrierInfo .address").html()
					} : "",
					remark: $("#remark").val(),
					officcode: $("#officcode").val(),
					deduct: 0 < $("#deductcredit").length && $("#deductcredit").prop("checked") ? 1 : 0,
					deduct2: 0 < $("#deductcredit2").length && $("#deductcredit2").prop("checked") ? 1 : 0,
					contype: _.params.contype,
					couponid: _.params.couponid,
					wxid: _.params.wxid,
					wxcardid: _.params.wxcardid,
					wxcode: _.params.wxcode,
					invoicename: $("#invoicename").val(),
					submit: !0,
					real_price: _.realPrice(),
					token: t,
					packageid: _.params.card_packageid,
					fromquick: _.params.fromquick,
					goods_dispatch: _.params.goods_dispatch
				};
				_.params.isverify && _.params.isforceverifystore && (d.carrierid = _.params.storeid), FoxUI.loader.show("mini"), f.json("order/create/submit", d, function(e) {
					if (i.removeAttr("stop", 1), 0 == e.status) return FoxUI.loader.hide(), void FoxUI.toast.show(e.result.message);
					if (-1 == e.status) return FoxUI.loader.hide(), void FoxUI.alert(e.result.message);
					if (3 == e.status) return FoxUI.loader.hide(), _.endtime = e.result.endtime || 0, _.imgcode = e.result.imgcode || 0, void require(["biz/member/account"], function(e) {
						e.initQuick({
							action: "bind",
							backurl: btoa(location.href),
							endtime: _.endtime,
							imgcode: _.imgcode,
							success: function() {
								var e = _.params;
								e.refresh = !0, _.open(e)
							}
						})
					});
					var t = f.getUrl("order/pay", {
						id: e.result.orderid
					});
					f.options && f.options.siteUrl && (t = f.options.siteUrl + "app" + t.substr(1)), location.href = t
				}, !1, !0)
			}
		},
		initaddress: function(e) {
			var t = ["foxui.picker"];
			_.params.new_area && (t = ["foxui.picker", "foxui.citydatanew"]), require(t, function() {
				if ($("#areas").cityPicker({
					title: "请选择所在城市",
					new_area: _.params.new_area,
					address_street: _.params.address_street,
					onClose: function(e) {
						var t = $("#areas").attr("data-value"),
							i = t.split(" ");
						if (_.params.new_area && $("#areas").attr("data-datavalue", t), _.params.new_area && _.params.address_street) {
							var r = i[1],
								a = i[2];
							r += "", a += "";
							var d = _.loadStreetData(r, a),
								c = $('<input type="text" id="street"  name="street" data-value="" value="" placeholder="所在街道"  class="fui-input" readonly=""/>'),
								o = $("#street").closest(".fui-cell-info");
							$("#street").remove(), o.append(c), c.cityPicker({
								title: "请选择所在街道",
								street: 1,
								data: d,
								onClose: function(e) {
									var t = $("#street").attr("data-value");
									$("#street").attr("data-datavalue", t)
								}
							})
						}
					}
				}), _.params.new_area && _.params.address_street) {
					var e = $("#areas").attr("data-value");
					if (e) {
						var t = e.split(" "),
							i = t[1],
							r = t[2],
							a = _.loadStreetData(i, r);
						$("#street").cityPicker({
							title: "请选择所在街道",
							street: 1,
							data: a
						})
					}
				}
			}), $(document).on("click", "#btn-submit", function() {
				if (!$(this).attr("submit")) if ($("#realname").isEmpty()) FoxUI.toast.show("请填写收件人");
				else {
					var e = /(境外地区)+/.test($("#areas").val()),
						t = /(台湾)+/.test($("#areas").val()),
						i = /(澳门)+/.test($("#areas").val()),
						r = /(香港)+/.test($("#areas").val());
					if (e || t || i || r) {
						if ($("#mobile").isEmpty()) return void FoxUI.toast.show("请填写手机号码")
					} else if (!$("#mobile").isMobile()) return void FoxUI.toast.show("请填写正确手机号码");
					$("#areas").isEmpty() ? FoxUI.toast.show("请填写所在地区") : $("#address").isEmpty() ? FoxUI.toast.show("请填写详细地址") : ($("#btn-submit").html("正在处理...").attr("submit", 1), window.editAddressData = {
						realname: $("#realname").val(),
						mobile: $("#mobile").val(),
						address: $("#address").val(),
						areas: $("#areas").val(),
						street: $("#street").val(),
						streetdatavalue: $("#street").attr("data-datavalue"),
						datavalue: $("#areas").attr("data-datavalue"),
						isdefault: $("#isdefault").is(":checked") ? 1 : 0
					}, f.json("member/address/submit", {
						id: $("#addressid").val(),
						addressdata: window.editAddressData
					}, function(e) {
						$("#btn-submit").html("保存地址").removeAttr("submit"), window.editAddressData.id = e.result.addressid, 1 == e.status ? (FoxUI.toast.show("保存成功!"), $("#addaddress").css("display", "none"), window.location.reload()) : FoxUI.toast.show(e.result.message)
					}, !0, !0))
				}
			})
		},
		loadStreetData: function(e, t) {
			var i = "../addons/vcshop/static/js/dist/area/list/" + e.substring(0, 2) + "/" + e + ".xml",
				r = _.loadXmlFile(i).childNodes[0].getElementsByTagName("county"),
				a = [];
			if (0 < r.length) for (var d = 0; d < r.length; d++) {
				var c = r[d];
				if (c.getAttribute("code") == t) for (var o = c.getElementsByTagName("street"), s = 0; s < o.length; s++) {
					var n = o[s];
					a.push({
						text: n.getAttribute("name"),
						value: n.getAttribute("code"),
						children: []
					})
				}
			}
			return a
		},
		bindCoupon: function() {
			$("#coupondiv").unbind("click").click(function() {
				$("#coupondiv").attr("is_open") || ($("#coupondiv").attr("is_open", !0), $("#couponloading").show(), f.json("sale/coupon/util/query", {
					money: 0,
					type: 0,
					merchs: _.params.merchs,
					goods: _.params.goods
				}, function(t) {
					$("#couponloading").hide(), 0 < t.result.coupons.length || 0 < t.result.wxcards.length ? ($("#coupondiv").show().find(".badge").html(t.result.coupons.length + t.result.wxcards.length).show(), $("#coupondiv").find(".text").hide(), require(["biz/sale/coupon/picker"], function(e) {
						e.show({
							couponid: _.params.couponid,
							coupons: t.result.coupons,
							wxcards: t.result.wxcards,
							onClose: function() {
								$("#coupondiv").removeAttr("is_open")
							},
							onCancel: function() {
								_.params.contype = 0, _.params.couponid = 0, _.params.wxid = 0, _.params.wxcardid = "", _.params.wxcode = "", _.params.couponmerchid = 0, $("#coupondiv").find(".fui-cell-label").html("优惠券"), $("#coupondiv").find(".fui-cell-info").html(""), _.calcCouponPrice()
							},
							onSelected: function(e) {
								_.params.contype = e.contype, 1 == _.params.contype ? (_.params.couponid = 0, _.params.wxid = e.wxid, _.params.wxcardid = e.wxcardid, _.params.wxcode = e.wxcode, _.params.couponmerchid = e.merchid, $("#coupondiv").find(".fui-cell-label").html("已选择"), $("#coupondiv").find(".fui-cell-info").html(e.couponname), $("#coupondiv").data(e)) : 2 == _.params.contype ? (_.params.couponid = e.couponid, _.params.wxid = 0, _.params.wxcardid = "", _.params.wxcode = "", _.params.couponmerchid = e.merchid, $("#coupondiv").find(".fui-cell-label").html("已选择"), $("#coupondiv").find(".fui-cell-info").html(e.couponname), $("#coupondiv").data(e)) : (_.params.contype = 0, _.params.couponid = 0, _.params.wxid = 0, _.params.wxcardid = "", _.params.wxcode = "", _.params.couponmerchid = 0, $("#coupondiv").find(".fui-cell-label").html("优惠券"), $("#coupondiv").find(".fui-cell-info").html("")), _.calcCouponPrice()
							}
						})
					})) : (FoxUI.toast.show("未找到优惠券!"), _.hideCoupon())
				}, !1, !0))
			})
		},
		hideCoupon: function() {
			$("#coupondiv").hide(), $("#coupondiv").find(".badge").html("0").hide(), $("#coupondiv").find(".text").show(), $("#coupondiv").removeAttr("is_open")
		},
		loadXmlFile: function(e) {
			var t = null;
			if (window.ActiveXObject)(t = new ActiveXObject("Microsoft.XMLDOM")).async = !1, t.load(e) || t.loadXML(e);
			else if (document.implementation && document.implementation.createDocument) {
				var i = new window.XMLHttpRequest;
				i.open("GET", e, !1), i.send(null), t = i.responseXML
			} else t = null;
			return t
		}
	};
	return $(document).on("click", "#invoicename", function() {
		var e = $(this).data("type");
		t.open(_.invoice_info, function(e) {
			_.invoice_info = e;
			var t = "[" + (1 == _.invoice_info.entity ? "纸质" : "电子") + "] ";
			t += _.invoice_info.title, t += " （" + (1 == _.invoice_info.company ? "单位" : "个人") + (_.invoice_info.number ? ": " + _.invoice_info.number : "") + "）", $("#invoicename").val(t)
		}, e)
	}), _.realPrice = function() {
		var e = f.getNumber($(".goodsprice").html()) - f.getNumber($(".showtaskdiscountprice").html()) - f.getNumber($(".showlotterydiscountprice").html()) - f.getNumber($(".showdiscountprice").html()) - f.getNumber($(".showisdiscountprice").html()) - f.getNumber($("#buyagain").val()),
			t = f.getNumber($("#taskcut").val()),
			i = (f.getNumber($("#deductenough_enough").html()), f.getNumber($("#merch_deductenough_enough").html()), f.getNumber($("#merch_deductenough_money").html())),
			r = f.getNumber($("#deductenough_money").html());
		i = 0;
		0 < $("#merch_deductenough_money").length && "" != $("#merch_deductenough_money").html() && (i = f.getNumber($("#merch_deductenough_money").html()));
		r = 0;
		0 < $("#deductenough_money").length && "" != $("#deductenough_money").html() && (r = f.getNumber($("#deductenough_money").html()));
		0 < _.params.card_packageid && 0 == _.params.card_id && (e -= f.getNumber($(".packageprice").html()));
		_.params.show_card && (e -= f.getNumber($("#carddeduct_money").html())), e = e - i - r - t;
		var a = 0;
		return 0 < $("#seckillprice_money").length && "" != $("#seckillprice_money").html() && (a = f.getNumber($("#seckillprice_money").html())), (e -= a) <= 0 && (e = 0), e
	}, _
});