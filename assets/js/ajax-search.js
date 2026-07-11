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
				}
			);
		}

		return states.get(wrapper);
	}

	function getStrings(wrapper) {
		return {
			loading: wrapper.getAttribute('data-loading-text') || 'Searching...',
			empty: wrapper.getAttribute('data-empty-text') || 'No results found.',
			minimum: wrapper.getAttribute('data-min-chars-text') || 'Type at least 2 characters to search.',
			error: wrapper.getAttribute('data-error-text') || 'Unable to search right now.',
		};
	}

	function setStatus(wrapper, message, isLoading) {
		const status = wrapper.querySelector('.vplens-ajax-search-status');

		if (!status) {
			return;
		}

		status.textContent = message;
		status.classList.toggle('is-loading', Boolean(isLoading));
		status.style.display = isLoading || message ? 'block' : 'none';
	}

	function setResultsVisibility(wrapper, isVisible) {
		const resultsWrap = wrapper.querySelector('.vplens-ajax-search-results-wrap');

		if (resultsWrap) {
			resultsWrap.style.display = isVisible ? 'block' : 'none';
		}
	}

	function hideResultsUI(wrapper) {
		setStatus(wrapper, '', false);
		setResultsVisibility(wrapper, false);
	}

	function clearResults(wrapper) {
		const results = wrapper.querySelector('.vplens-ajax-search-results');
		const input = wrapper.querySelector('.vplens-ajax-search-input');

		if (results) {
			results.innerHTML = '';
		}

		if (input) {
			input.setAttribute('aria-expanded', 'false');
			input.removeAttribute('aria-activedescendant');
		}

		getState(wrapper).activeIndex = -1;
	}

	function renderResults(wrapper, items) {
		const results = wrapper.querySelector('.vplens-ajax-search-results');
		const input = wrapper.querySelector('.vplens-ajax-search-input');
		const showFeaturedImages = '1' === wrapper.getAttribute('data-show-featured-images');
		const showPostTypeLabel = '1' === wrapper.getAttribute('data-show-post-type-label');
		const state = getState(wrapper);

		if (!results || !input) {
			return;
		}

		results.innerHTML = '';
		state.activeIndex = -1;

		if (!items.length) {
			input.setAttribute('aria-expanded', 'false');
			clearResults(wrapper);
			setResultsVisibility(wrapper, false);
			return;
		}

		items.forEach(
			function (item, index) {
				const li = document.createElement('li');
				const link = document.createElement('a');
				const imageWrap = document.createElement('span');
				const title = document.createElement('span');
				const meta = document.createElement('span');

				li.className = 'vplens-ajax-search-result';
				li.setAttribute('role', 'option');
				li.id = 'vplens-ajax-search-option-' + index;

				link.className = 'vplens-ajax-search-link';
				link.href = item.url || '#';
				link.tabIndex = -1;

				imageWrap.className = 'vplens-ajax-search-image-wrap';

				if (showFeaturedImages && item.featured_image) {
					const image = document.createElement('img');
					image.className = 'vplens-ajax-search-image';
					image.src = item.featured_image;
					image.alt = '';
					image.loading = 'lazy';
					image.decoding = 'async';
					image.setAttribute('aria-hidden', 'true');
					imageWrap.appendChild(image);
					link.classList.add('has-featured-image');
				}

				title.className = 'vplens-ajax-search-title';
				title.textContent = item.title || '';

				if (imageWrap.childNodes.length) {
					link.appendChild(imageWrap);
				}
				link.appendChild(title);

				if (showPostTypeLabel && item.post_type_label) {
					meta.className = 'vplens-ajax-search-meta';
					meta.textContent = item.post_type_label;
					link.appendChild(meta);
				}

				li.appendChild(link);
				results.appendChild(li);
			}
		);

		input.setAttribute('aria-expanded', 'true');
		setResultsVisibility(wrapper, true);
	}

	function updateActiveItem(wrapper, direction) {
		const state = getState(wrapper);
		const items = wrapper.querySelectorAll('.vplens-ajax-search-result');

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
					const input = wrapper.querySelector('.vplens-ajax-search-input');

					if (input) {
						input.setAttribute('aria-activedescendant', item.id);
					}
				}
			}
		);
	}

	function fetchResults(wrapper, term) {
		const state = getState(wrapper);
		const ajaxUrl = wrapper.getAttribute('data-ajax-url');
		const nonce = wrapper.getAttribute('data-nonce');
		const action = wrapper.getAttribute('data-action');
		const limit = wrapper.getAttribute('data-limit') || '10';
		const strings = getStrings(wrapper);

		if (!ajaxUrl || !nonce || !action) {
			return;
		}

		if (state.controller) {
			state.controller.abort();
		}

		state.controller = new AbortController();
		setStatus(wrapper, strings.loading, true);

		const params = new URLSearchParams();
		params.append('action', action);
		params.append('nonce', nonce);
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
						setResultsVisibility(wrapper, false);
						return;
					}

					const items = data.data && Array.isArray(data.data.items) ? data.data.items : [];
					if (!items.length) {
						clearResults(wrapper);
						setResultsVisibility(wrapper, false);
						setStatus(wrapper, strings.empty, false);
						return;
					}

					renderResults(wrapper, items);
					setStatus(wrapper, '', false);
				}
			)
			.catch(
				function () {
					if (state.controller.signal.aborted) {
						return;
					}

					clearResults(wrapper);
					setStatus(wrapper, strings.error, false);
					setResultsVisibility(wrapper, false);
				}
			)
			.finally(
				function () {
					state.controller = null;
					const input = wrapper.querySelector('.vplens-ajax-search-input');

					if (input) {
						input.removeAttribute('aria-busy');
					}
				}
			);
	}

	function handleInput(wrapper, event) {
		const input = event.target;
		const term = input.value.trim();
		const state = getState(wrapper);
		const minChars = parseInt(wrapper.getAttribute('data-min-chars') || '2', 10);
		const debounce = parseInt(wrapper.getAttribute('data-debounce') || '300', 10);
		const strings = getStrings(wrapper);

		if (state.timer) {
			window.clearTimeout(state.timer);
		}

		if (term.length < minChars) {
			clearResults(wrapper);
			hideResultsUI(wrapper);
			return;
		}

		input.setAttribute('aria-busy', 'true');
		state.timer = window.setTimeout(
			function () {
				fetchResults(wrapper, term);
			},
			debounce
		);
	}

	function initWrapper(wrapper) {
		const input = wrapper.querySelector('.vplens-ajax-search-input');
		const form = wrapper.querySelector('.vplens-ajax-search-form');

		if (!input || !form) {
			return;
		}

		hideResultsUI(wrapper);

		input.addEventListener('input', handleInput.bind(null, wrapper));
		input.addEventListener(
			'keydown',
			function (event) {
				if ('ArrowDown' === event.key) {
					event.preventDefault();
					updateActiveItem(wrapper, 1);
					return;
				}

				if ('ArrowUp' === event.key) {
					event.preventDefault();
					updateActiveItem(wrapper, -1);
					return;
				}

				if ('Escape' === event.key) {
					clearResults(wrapper);
					hideResultsUI(wrapper);
				}
			}
		);

		form.addEventListener(
			'submit',
			function (event) {
				event.preventDefault();

				const state = getState(wrapper);
				const items = wrapper.querySelectorAll('.vplens-ajax-search-result');
				const activeResult = state.activeIndex >= 0 ? items[state.activeIndex] : null;
				const link = activeResult ? activeResult.querySelector('a') : null;

				if (link && link.href) {
					window.location.href = link.href;
					return;
				}

				input.dispatchEvent(new Event('input', { bubbles: true }));
			}
		);

		wrapper.addEventListener(
			'click',
			function (event) {
				const link = event.target.closest('.vplens-ajax-search-link');

				if (link) {
					clearResults(wrapper);
					hideResultsUI(wrapper);
				}
			}
		);

		document.addEventListener(
			'click',
			function (event) {
				if (!wrapper.contains(event.target)) {
					clearResults(wrapper);
					hideResultsUI(wrapper);
				}
			}
		);
	}

	function init() {
		document.querySelectorAll('.vplens-ajax-search').forEach(initWrapper);
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', init);
		return;
	}

	init();
})();