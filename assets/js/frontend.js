(function () {
	'use strict';

	window.SearchLens = window.SearchLens || {};

	function getPageType() {
		const classes = document.body.classList;
		if (classes.contains('home') || classes.contains('front-page')) {
			return 'Home';
		}
		if (classes.contains('search') || classes.contains('search-results')) {
			return 'Search Results';
		}
		if (classes.contains('error404') || classes.contains('error-404')) {
			return '404';
		}
		if (classes.contains('woocommerce-shop') || classes.contains('post-type-archive-product')) {
			return 'Shop';
		}
		if (classes.contains('single-product')) {
			return 'Product';
		}
		if (classes.contains('category')) {
			return 'Category';
		}
		if (classes.contains('tag') || classes.contains('tax-post_tag')) {
			return 'Tag';
		}
		if (classes.contains('author')) {
			return 'Author';
		}
		if (classes.contains('single-post') || (classes.contains('single') && !classes.contains('single-product'))) {
			return 'Post';
		}
		if (classes.contains('page') && !classes.contains('woocommerce-shop')) {
			return 'Page';
		}
		if (classes.contains('archive')) {
			return 'Archive';
		}
		return 'Other';
	}

	window.SearchLens.getPageType = getPageType;

	document.addEventListener('submit', function (event) {
		const form = event.target;
		const sInput = form.querySelector('input[name="s"]');
		if (sInput && form.method.toLowerCase() === 'get') {
			const pageData = {
				'searchlens_page_title': window.searchlens_data && window.searchlens_data.page_title ? window.searchlens_data.page_title : document.title,
				'searchlens_page_url': window.searchlens_data && window.searchlens_data.page_url ? window.searchlens_data.page_url : window.location.href,
				'searchlens_referrer': document.referrer,
				'searchlens_page_type': window.searchlens_data && window.searchlens_data.page_type ? window.searchlens_data.page_type : getPageType()
			};
			for (const key in pageData) {
				let input = form.querySelector('input[name="' + key + '"]');
				if (!input) {
					input = document.createElement('input');
					input.type = 'hidden';
					input.name = key;
					form.appendChild(input);
				}
				input.value = pageData[key];
			}
		}
	});
})();
