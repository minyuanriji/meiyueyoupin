define(["biz"], function(biz) {
	model = {};
	model.multi = false;
	model.callback = '';
	model.ele = {};
	model.listenPool = [1];
	model.selectedPool = {};
	model.merchid = 0;
	model.no_merchid = 0;
	model.check = true;
	model.myArray = new Array();
	model.content = new Array();
	model.url = function(routes, merch) {
		if (merch) {
			var url = './merchant.php?c=site&a=entry&m=vcshop&do=web&r=' + routes.replace(/\//ig, '.')
		} else {
			var url = './index.php?c=site&a=entry&m=vcshop&do=web&r=' + routes.replace(/\//ig, '.')
		}
		return url
	};
	model.post_url = model.url('commission.apply.get_list', model.merchid);
	model.init = function(params) {
		model.id = params.id;
		// model.listen();
		if (model.check) {
			$('input[name=ischeck]').attr('value', 1)
		}
	};
	$('#btn-check').click(function() {
		model.check = true;
		model.myArray.splice(0, model.myArray.length);
		$('input[name=ischeck]').attr('value', 1);
		$('.append-content').empty()
	});
	$('#btn-uncheck').click(function() {
		$('input[name=ischeck]').attr('value', 0);
		model.check = false;
		model.myArray.splice(0, model.myArray.length);
		$('.append-content').empty()
	});
	model.listen = function() {
		model.jumpnow(1);
		$(document).keypress(function(e) {
			if (e.which == 13 && model.ele != undefined) {
				model.jumpnow(1);
				return false
			}
		});
		$(document).on("click", ".pager-nav", function() {
			var num = Number($(this).attr("page"));
			model.jumpnow(num)
		});
		$(document).on("change", ".page-raduis", function() {
			var num = Number($(this).val());
			$(this).parent().next("li").find("a").attr("page", num)
		})
	};
	model.jumpnow = function(page) {
		model.getpage(page, model.goodsgroup)
	};
	model.getpage = function(page, keywords, goodsgroup) {
		if (!page > 0) {
			page = 1
		}
		$.ajax({
			url: model.post_url,
			type: "post",
			data: {
				data: {},
				page: page,
				id: model.id
			},
			success: function(htm) {
				model.ele = $("#detail_content");
				model.ele.empty().html(htm);
				checkall(model.check);
				$.each(model.myArray, function(index, item) {
					if (model.check) {
						$('input[data-name=' + item + '][value=-1]').prop("checked", "checked")
					} else {
						$('input[data-name=' + item + '][value=2]').prop("checked", "checked")
					}
				});
				if (model.content) {
					for (var key in model.content) {
						$('input[data-name=' + key + ']').val(model.content[key])
					}
				}
				$('input[type=radio]').click(function() {
					var input_name = $(this).attr('data-name');
					var input_val = $(this).val();
					var html = "<input type=hidden  value=" + input_val + " name=" + input_name + " data-post-name=" + input_name + ">";
					if (model.check) {
						if (input_val == -1) {
							$('input[data-post-name=' + input_name + ']').remove();
							$('.append-content').append(html)
						} else {
							$('input[data-post-name=' + input_name + ']').remove()
						}
					} else if (model.check == false) {
						if (input_val == 2) {
							$('input[data-post-name=' + input_name + ']').remove();
							$('.append-content').append(html)
						} else {
							$('input[data-post-name=' + input_name + ']').remove()
						}
					}
					if (model.check) {
						if (input_val == -1) {
							if ($.inArray(input_name, model.myArray) == -1) {
								model.myArray.push(input_name)
							}
						} else {
							model.myArray.splice($.inArray(input_name, model.myArray), 1)
						}
					}
					if (model.check == false) {
						if (input_val == 2) {
							if ($.inArray(input_name, model.myArray) == -1) {
								model.myArray.push(input_name)
							}
						} else {
							model.myArray.splice($.inArray(input_name, model.myArray), 1)
						}
					}
				});
				$("input[name^='content']").blur(function() {
					var content_text = $(this).val();
					var content_name = $(this).attr('data-name');
					var html = "<input type=hidden  value=" + content_text + " name=" + content_name + " data-post-name=" + content_name + ">";
					if (content_text.replace(/(^s*)|(s*$)/g, "").length > 0) {
						model.content[content_name] = content_text;
						$('.append-remark-content').append(html)
					} else {
						delete model.content[content_name];
						$('input[data-post-name=' + content_name + ']').remove()
					}
				});
				$('[data-toggle="tooltip"]').tooltip("destroy").tooltip({
					container: $(document.body)
				});
				$('[data-toggle="popover"]').popover("destroy").popover({
					container: $(document.body)
				})
			},
		})
	};
	return model
});