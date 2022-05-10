$(function () {

	$(".qtyButtons input").after('<div class="qtyInc"></div>');
	$(".qtyButtons input").before('<div class="qtyDec"></div>');
	$(".qtyDec, .qtyInc").on("click", function () {

		var $button = $(this);
		var oldValue = $button.parent().find("input").val();

		if ($button.hasClass('qtyInc')) {
			var newVal = parseFloat(oldValue) + 1;
		} else {
			// don't allow decrementing below zero
			if (oldValue > 0) {
				var newVal = parseFloat(oldValue) - 1;
			} else {
				newVal = 0;
			}
		}

		$button.parent().find("input").val(newVal);
		qtySum();
		$(".qtyTotal").addClass("rotate-x");

	});

	function removeAnimation() { $(".qtyTotal").removeClass("rotate-x"); }
	const counter = document.querySelector(".qtyTotal");
	counter.addEventListener("animationend", removeAnimation);

});