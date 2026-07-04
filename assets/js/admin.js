(function () {
	'use strict';

	window.addEventListener(
		'DOMContentLoaded',
		function () {
			const wrapper = document.querySelector( '.searchlens-wrap' );

			if ( ! wrapper) {
				return;
			}

			const buttons                   = wrapper.querySelectorAll( '.searchlens-copy-shortcode' );
			const copiedLabel               = wrapper.getAttribute( 'data-copied-label' ) || 'Copied';
			const loadAllPublicPostTypes    = wrapper.querySelector( '#searchlens-search_sources-load_all_public_post_types' );
			const searchablePostTypeOptions = wrapper.querySelectorAll( '.searchlens-post-type-option input[type="checkbox"]' );

			if (loadAllPublicPostTypes && searchablePostTypeOptions.length) {
				const syncSearchablePostTypes = function () {
					const isDisabled = loadAllPublicPostTypes.checked;

					searchablePostTypeOptions.forEach(
						function (checkbox) {
							checkbox.disabled = isDisabled;
							const option      = checkbox.closest( '.searchlens-post-type-option' );

							if (option) {
								option.classList.toggle( 'is-disabled', isDisabled );
							}
						}
					);
				};

				loadAllPublicPostTypes.addEventListener( 'change', syncSearchablePostTypes );
				syncSearchablePostTypes();
			}

			// Bind details row click toggler logic using event delegation.
			wrapper.addEventListener(
				'click',
				function (e) {
					const toggleBtn = e.target.closest( '.searchlens-toggle-details' );
					if ( ! toggleBtn) {
						return;
					}

					e.preventDefault();
					const targetId = toggleBtn.getAttribute( 'data-target' );
					const targetRow = document.getElementById( targetId );

					if (targetRow) {
						if (targetRow.style.display === 'none') {
							targetRow.style.display = '';
							toggleBtn.textContent = toggleBtn.getAttribute( 'data-hide-label' ) || 'Hide';
						} else {
							targetRow.style.display = 'none';
							toggleBtn.textContent = toggleBtn.getAttribute( 'data-show-label' ) || 'Details';
						}
					}
				}
			);

			// Collapsible toggle logic for Search Filters with localStorage persistence.
			const filterToggleBtn = wrapper.querySelector( '.searchlens-filters-toggle-btn' );
			const filtersForm     = wrapper.querySelector( '.searchlens-filters-form' );
			if (filterToggleBtn && filtersForm) {
				const toggleText = filterToggleBtn.querySelector( '.searchlens-toggle-text' );
				const toggleIcon = filterToggleBtn.querySelector( '.dashicons' );

				const setFiltersState = function (isCollapsed) {
					if (isCollapsed) {
						filtersForm.classList.add( 'collapsed' );
						filterToggleBtn.classList.add( 'collapsed' );
						filterToggleBtn.setAttribute( 'aria-expanded', 'false' );
						if (toggleText) {
							toggleText.textContent = filterToggleBtn.getAttribute( 'data-show-text' ) || 'Show Filters';
						}
						if (toggleIcon) {
							toggleIcon.className = 'dashicons dashicons-arrow-down-alt2';
						}
					} else {
						filtersForm.classList.remove( 'collapsed' );
						filterToggleBtn.classList.remove( 'collapsed' );
						filterToggleBtn.setAttribute( 'aria-expanded', 'true' );
						if (toggleText) {
							toggleText.textContent = filterToggleBtn.getAttribute( 'data-hide-text' ) || 'Hide Filters';
						}
						if (toggleIcon) {
							toggleIcon.className = 'dashicons dashicons-arrow-up-alt2';
						}
					}
				};

				// Initial state from localStorage (default: expanded)
				const storedState = localStorage.getItem( 'searchlens_filters_collapsed' );
				if (storedState === 'true') {
					setFiltersState( true );
				} else {
					setFiltersState( false );
				}

				filterToggleBtn.addEventListener(
					'click',
					function (e) {
						e.preventDefault();
						const isCurrentlyCollapsed = filtersForm.classList.contains( 'collapsed' );
						setFiltersState( ! isCurrentlyCollapsed );
						localStorage.setItem( 'searchlens_filters_collapsed', ( ! isCurrentlyCollapsed ).toString() );
					}
				);
			}

			if ( ! buttons.length) {
				return;
			}

			buttons.forEach(
				function (button) {
					button.addEventListener(
						'click',
						function () {
							const shortcode = button.getAttribute( 'data-copy-shortcode' );

							if ( ! shortcode) {
								return;
							}

							const originalLabel  = button.textContent;
							const setCopiedLabel = function () {
								button.textContent = copiedLabel;
								window.setTimeout(
									function () {
										button.textContent = originalLabel;
									},
									1500
								);
							};

							if (navigator.clipboard && navigator.clipboard.writeText) {
								navigator.clipboard.writeText( shortcode ).then( setCopiedLabel );
								return;
							}

							const textarea = document.createElement( 'textarea' );
							textarea.value = shortcode;
							textarea.setAttribute( 'readonly', 'readonly' );
							textarea.style.position = 'absolute';
							textarea.style.left     = '-9999px';
							document.body.appendChild( textarea );
							textarea.select();

							try {
								document.execCommand( 'copy' );
								setCopiedLabel();
							} finally {
								document.body.removeChild( textarea );
							}
						}
					);
				}
			);
		}
	);

})();
