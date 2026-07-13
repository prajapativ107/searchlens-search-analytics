(function () {
	'use strict';

	const states = new WeakMap();

	function getState(wrapper) {
		if (!states.has(wrapper)) {
			states.set(
				wrapper,
				{
					timer: null,
					controller: null,
					activeIndex: -1,
					isOpen: false,
				}
			);
		}

		return states.get(wrapper);
	}

	function getStrings(wrapper) {
		const i18n = window.vplensSearchWidgetI18n || {};

		return {
			loading: wrapper.getAttribute('data-loading-text') || i18n.loading || 'Searching...',
			empty: wrapper.getAttribute('data-empty-text') || i18n.empty || 'No results found.',
			error: wrapper.getAttribute('data-error-text') || i18n.error || 'Unable to search right now.',
		};
	}

	function getSearchElements(wrapper) {
		return {
			toggle: wrapper.querySelector('.vplens-search-toggle'),
			popup: wrapper.querySelector('.vplens-search-popup'),
			closeButton: wrapper.querySelector('.vplens-search-close'),
			form: wrapper.querySelector('.vplens-form, .vplens-ajax-search-form'),
			input: wrapper.querySelector('.vplens-form .search-field, .vplens-ajax-search-input'),
			status: wrapper.querySelector('.vplens-search-status, .vplens-ajax-search-status'),
			resultsWrap: wrapper.querySelector('.vplens-search-results-wrap, .vplens-ajax-search-results-wrap'),
			results: wrapper.querySelector('.vplens-search-results, .vplens-ajax-search-results'),
		};
	}

	function setStatus(wrapper, message, isLoading) {
		const elements = getSearchElements(wrapper);

		if (!elements.status) {
			return;
		}

		elements.status.textContent = message;
		elements.status.classList.toggle('is-loading', Boolean(isLoading));
		elements.status.hidden = !message && !isLoading;
	}

	function setResultsVisibility(wrapper, isVisible) {
		const elements = getSearchElements(wrapper);

		if (elements.resultsWrap) {
			elements.resultsWrap.hidden = !isVisible;
		}
	}

	function clearResults(wrapper) {
		const elements = getSearchElements(wrapper);
		const state = getState(wrapper);

		if (elements.results) {
			elements.results.innerHTML = '';
		}

		state.activeIndex = -1;
		setResultsVisibility(wrapper, false);
		setStatus(wrapper, '', false);
	}

	function openWidget(wrapper) {
		const state = getState(wrapper);
		const elements = getSearchElements(wrapper);

		if (!elements.popup || !elements.toggle) {
			return;
		}

		state.isOpen = true;
		elements.popup.hidden = false;
		if ('inert' in elements.popup) {
			elements.popup.inert = false;
		} else {
			elements.popup.removeAttribute('inert');
		}
		elements.popup.setAttribute('aria-hidden', 'false');
		wrapper.classList.add('is-open');
		elements.toggle.setAttribute('aria-expanded', 'true');

		window.setTimeout(
			function () {
				if (elements.input) {
					elements.input.focus({ preventScroll: true });
				}
			},
			0
		);
	}

	function closeWidget(wrapper) {
		const state = getState(wrapper);
		const elements = getSearchElements(wrapper);

		state.isOpen = false;
		wrapper.classList.remove('is-open');

		if (elements.toggle) {
			elements.toggle.setAttribute('aria-expanded', 'false');
			elements.toggle.focus({ preventScroll: true });
		}

		if (elements.popup) {
			if ('inert' in elements.popup) {
				elements.popup.inert = true;
			} else {
				elements.popup.setAttribute('inert', '');
			}
			elements.popup.setAttribute('aria-hidden', 'true');
			elements.popup.hidden = true;
		}

		if (state.controller) {
			state.controller.abort();
			state.controller = null;
		}

		if (state.timer) {
			window.clearTimeout(state.timer);
			state.timer = null;
		}

		clearResults(wrapper);

		window.setTimeout(
			function () {
				if (elements.toggle) {
					elements.toggle.focus({ preventScroll: true });
				}
			},
			0
		);
	}

	function renderResults(wrapper, items) {
		const elements = getSearchElements(wrapper);
		const showFeaturedImages = '1' === wrapper.getAttribute('data-show-featured-images');
		const showPostTypeLabel = '1' === wrapper.getAttribute('data-show-post-type-label');
		const state = getState(wrapper);

		if (!elements.results) {
			return;
		}

		elements.results.innerHTML = '';
		state.activeIndex = -1;

		if (!items.length) {
			setResultsVisibility(wrapper, false);
			setStatus(wrapper, getStrings(wrapper).empty, false);
			return;
		}

		items.forEach(
			function (item, index) {
				const li = document.createElement('li');
				const link = document.createElement('a');
				const title = document.createElement('span');
				const meta = document.createElement('span');

				li.className = 'vplens-search-result';
				li.id = 'vplens-search-widget-option-' + index;
				li.setAttribute('role', 'option');

				link.className = 'vplens-search-link';
				link.href = item.url || '#';
				link.tabIndex = -1;

				if (showFeaturedImages && item.featured_image) {
					const imageWrap = document.createElement('span');
					const image = document.createElement('img');

					imageWrap.className = 'vplens-search-image-wrap';
					image.className = 'vplens-search-image';
					image.src = item.featured_image;
					image.alt = '';
					image.loading = 'lazy';
					image.decoding = 'async';
					image.setAttribute('aria-hidden', 'true');
					imageWrap.appendChild(image);
					link.appendChild(imageWrap);
					link.classList.add('has-featured-image');
				}

				title.className = 'vplens-search-title';
				title.textContent = item.title || '';

				if (showPostTypeLabel && item.post_type_label) {
					meta.className = 'vplens-search-meta';
					meta.textContent = item.post_type_label;
					link.appendChild(title);
					link.appendChild(meta);
				} else {
					link.appendChild(title);
				}
				li.appendChild(link);
				elements.results.appendChild(li);
			}
		);

		setResultsVisibility(wrapper, true);
		setStatus(wrapper, '', false);
	}

	function fetchResults(wrapper, term) {
		const state = getState(wrapper);
		const strings = getStrings(wrapper);
		const ajaxUrl = wrapper.getAttribute('data-ajax-url');
		const ajaxNonce = wrapper.getAttribute('data-ajax-nonce');
		const ajaxAction = wrapper.getAttribute('data-ajax-action');
		const limit = wrapper.getAttribute('data-limit') || '10';

		if (!ajaxUrl || !ajaxNonce || !ajaxAction) {
			return;
		}

		if (state.controller) {
			state.controller.abort();
		}

		state.controller = new AbortController();
		setStatus(wrapper, strings.loading, true);
		setResultsVisibility(wrapper, true);

		const params = new URLSearchParams();
		params.append('action', ajaxAction);
		params.append('nonce', ajaxNonce);
		params.append('term', term);
		params.append('limit', limit);

		const pageTitle = window.vplens_data && window.vplens_data.page_title ? window.vplens_data.page_title : document.title;
		const pageUrl   = window.vplens_data && window.vplens_data.page_url ? window.vplens_data.page_url : window.location.href;
		const pageType  = window.vplens_data && window.vplens_data.page_type ? window.vplens_data.page_type : (window.VPLens && window.VPLens.getPageType ? window.VPLens.getPageType() : 'Other');

		params.append('page_title', pageTitle);
		params.append('page_url', pageUrl);
		params.append('referrer', document.referrer);
		params.append('page_type', pageType);

		fetch(
			ajaxUrl,
			{
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				},
				body: params.toString(),
				signal: state.controller.signal,
			}
		)
			.then(
				function (response) {
					return response.json();
				}
			)
			.then(
				function (data) {
					if (!data || !data.success) {
						clearResults(wrapper);
						setStatus(wrapper, data && data.data && data.data.message ? data.data.message : strings.error, false);
						return;
					}

					const items = data.data && Array.isArray(data.data.items) ? data.data.items : [];
					renderResults(wrapper, items);
				}
			)
			.catch(
				function () {
					if (state.controller && state.controller.signal.aborted) {
						return;
					}

					clearResults(wrapper);
					setStatus(wrapper, strings.error, false);
				}
			)
			.finally(
				function () {
					state.controller = null;
				}
			);
	}

	function handleInput(wrapper, event) {
		const state = getState(wrapper);
		const input = event.target;
		const term = input.value.trim();
		const minChars = parseInt(wrapper.getAttribute('data-min-chars') || '2', 10);
		const debounce = parseInt(wrapper.getAttribute('data-debounce') || '300', 10);

		if (state.timer) {
			window.clearTimeout(state.timer);
		}

		if (term.length < minChars) {
			clearResults(wrapper);
			return;
		}

		state.timer = window.setTimeout(
			function () {
				fetchResults(wrapper, term);
			},
			debounce
		);
	}

	function updateActiveItem(wrapper, direction) {
		const elements = getSearchElements(wrapper);
		const state = getState(wrapper);
		const items = elements.results ? elements.results.querySelectorAll('.vplens-search-result') : [];

		if (!items.length) {
			return;
		}

		state.activeIndex += direction;

		if (state.activeIndex < 0) {
			state.activeIndex = items.length - 1;
		}

		if (state.activeIndex >= items.length) {
			state.activeIndex = 0;
		}

		items.forEach(
			function (item, index) {
				const link = item.querySelector('a');
				item.classList.toggle('is-active', index === state.activeIndex);

				if (index === state.activeIndex && link) {
					link.focus({ preventScroll: true });
					if (elements.input) {
						elements.input.setAttribute('aria-activedescendant', item.id);
					}
				}
			}
		);
	}

	function initWidget(wrapper) {
		const state = getState(wrapper);
		const elements = getSearchElements(wrapper);
		const ajaxEnabled = '1' === wrapper.getAttribute('data-ajax-enabled');
		const usesAjaxSearchModule = Boolean(wrapper.querySelector('.vplens-ajax-search'));

		if (!elements.toggle || !elements.popup) {
			return;
		}

		wrapper.classList.add('vplens-search-widget--initialized');
		setStatus(wrapper, '', false);
		clearResults(wrapper);

		elements.toggle.addEventListener(
			'click',
			function () {
				if (state.isOpen) {
					closeWidget(wrapper);
					return;
				}

				openWidget(wrapper);
			}
		);

		if (elements.input && !usesAjaxSearchModule) {
			elements.input.addEventListener(
				'input',
				function (event) {
					if (!ajaxEnabled) {
						return;
					}

					handleInput(wrapper, event);
				}
			);

			elements.input.addEventListener(
				'keydown',
				function (event) {
					if (event.key === 'Escape') {
						event.preventDefault();
						closeWidget(wrapper);
						return;
					}

					if (!ajaxEnabled) {
						return;
					}

					if (event.key === 'ArrowDown') {
						event.preventDefault();
						updateActiveItem(wrapper, 1);
						return;
					}

					if (event.key === 'ArrowUp') {
						event.preventDefault();
						updateActiveItem(wrapper, -1);
					}
				}
			);
		}

		if (elements.form && !usesAjaxSearchModule) {
			elements.form.addEventListener(
				'submit',
				function (event) {
					const state = getState(wrapper);
					const items = elements.results ? elements.results.querySelectorAll('.vplens-search-result, .vplens-ajax-search-result') : [];
					const activeResult = state.activeIndex >= 0 ? items[state.activeIndex] : null;
					const link = activeResult ? activeResult.querySelector('a') : null;

					if (link && link.href) {
						event.preventDefault();
						window.location.href = link.href;
					}
				}
			);
		}

		wrapper.addEventListener(
			'click',
			function (event) {
				if (event.target === elements.popup) {
					closeWidget(wrapper);
					return;
				}

				if (event.target.closest('.vplens-search-close')) {
					closeWidget(wrapper);
					return;
				}

				const link = event.target.closest('.vplens-search-link');

				if (link) {
					closeWidget(wrapper);
				}
			}
		);

		document.addEventListener(
			'click',
			function (event) {
				if (state.isOpen && !wrapper.contains(event.target)) {
					closeWidget(wrapper);
				}
			}
		);

		document.addEventListener(
			'keydown',
			function (event) {
				if (state.isOpen && 'Escape' === event.key) {
					closeWidget(wrapper);
				}
			}
		);
	}

	function initAllWidgets() {
		document.querySelectorAll('.vplens-search-widget').forEach(
			function (wrapper) {
				initWidget(wrapper);
			}
		);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', initAllWidgets);
	} else {
		initAllWidgets();
	}
})();