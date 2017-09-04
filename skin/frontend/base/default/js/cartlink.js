jQuery(function () {
	var clicked = false;
	jQuery("div.header-regular").on("click", "div.ultracart.has-items", function () {
		//to solve the problem that curent page just to checkout when we click button to change qty.
		return ;
		if (!clicked) {
			clicked = true;
			jQuery("div.ultracart.has-items button").click();
		}
	});
});