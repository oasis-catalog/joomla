jQuery(function ($) {
	function sendHere(postfix) {
        var token = $("#token").attr("name");
        var $button = $("#btn-" + postfix).button('loading');

        $.ajax({
            data: {
                [token]: "1",
                task: "oasis.sendOrder",
                format: "json",
                orderId: $("#order-" + postfix).attr("value")
            },
            success: function () {
                setTimeout(function () {
                    $button.button('complete');
                    location.reload();
                }, 2 * 1000);
            },
            error: function () {
            },
        });
    }

	let tree = new OaHelper.Tree('#tree', {
		onBtnRelation (cat_id, cat_rel_id){
			ModalRelation(cat_rel_id).then(item => tree.setRelationItem(cat_id, item));
		}
	});
	function ModalRelation(cat_rel_id){
		return new Promise((resolve, reject) => {
			$.get('index.php?option=com_oasis&task=oasis.get_all_categories', {}, tree_content => {
				let content = $('#oasis-relation').clone();
				content.find('.modal-body').html(tree_content);

				let btn_ok = content.find('.js-ok'),
					btn_clear = content.find('.js-clear'),
					modal = null,
					tree = new OaHelper.RadioTree(content.find('.oa-tree'), {
							onChange: item => {
								btn_ok.toggleClass('disabled', !item);
							}
						});

				tree.value = cat_rel_id;

				btn_ok.toggleClass('disabled', !tree.value);
				btn_clear.toggle(!!cat_rel_id);

				btn_ok.on('click', () => {
					let item = tree.item;
					if(item){
						modal.hide();
						resolve(item);
					}
				});
				btn_clear.on('click', () => {
					modal.hide();
					resolve(null);
				});

				modal = new bootstrap.Modal(content);
				modal.show();
			});
		});
	}
});