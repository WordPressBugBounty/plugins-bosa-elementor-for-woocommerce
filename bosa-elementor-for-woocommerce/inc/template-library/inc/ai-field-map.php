<?php
/**
 * Widget-type -> AI-field-role mapping for the AI text-regeneration
 * feature (Phase 2).
 *
 * Keyed by Elementor `widgetType` (the exact string stored in `_elementor_data`,
 * NOT a CSS class or anything rendering-time-only), so this works against
 * every existing template with zero backfill -- widgetType is always present
 * on every widget instance, unlike a per-item tag that would need writing
 * onto thousands of individual widgets.
 *
 * Built from a real usage scan across the live template library (58 distinct
 * widget types found), so only widgets actually in use are mapped -- not
 * every widget every dependent plugin theoretically offers.
 *
 * Shape per widget type:
 *   'widgetType' => array(
 *       'settings_key' => 'role_label',                 // simple field
 *       'repeater_key' => array( 'sub_key' => 'role' ),  // repeater: role map applies to each item
 *   ),
 *
 * A widget type intentionally absent from this map means it was checked and
 * found to have no regeneratable text (e.g. bew-elements-cart's content is
 * live WooCommerce data, icon/spacer/rating are purely visual, google_maps'
 * only text field is a real address, not prose) -- see the notes below each
 * source's block for exactly what was excluded and why.
 *
 * @package Bosa_Elementor_WooCommerce
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the full widget-type -> AI-field-role map.
 *
 * @return array<string, array<string, mixed>>
 */
function bosa_ewc_get_ai_field_map() {
	$map = array();

	// ── BEW's own widgets ───────────────────────────────────────────────
	// Not mapped (confirmed no regeneratable text): bew-elements-cart,
	// bew-elements-contact-form-7 (selects an existing CF7 form; its field
	// labels live in the separate wpcf7_contact_form post), bew-elements-
	// site-logo (pure media/style, no text controls at all).
	$map['bew-elements-search'] = array(
		'placeholder'            => 'search_placeholder_text',
		'categories_placeholder' => 'category_placeholder_text',
	);
	$map['bew-elements-dropdown-nav'] = array(
		'label' => 'button_label',
	);
	$map['bew-elements-my-account'] = array(
		'label' => 'button_label',
	);
	$map['bew-elements-yith-compare'] = array(
		'label' => 'button_label',
	);
	$map['bew-elements-yith-wishlist'] = array(
		'label' => 'button_label',
	);
	// Opt-in Compare & Wishlist buttons shared across 7 independent
	// product-listing widgets (register_bew_yith_buttons_control() in
	// inc/bew-common.php) -- this is the free-plugin one; the 6 Pro
	// widgets that share the same two control keys are registered by Pro
	// itself via the bosa_ewc_ai_field_map filter (no wildcard matching
	// here, each widgetType needs its own entry).
	// '_anchor' places the BEW AI button next to the "Label" master
	// switcher's own row instead of either text field specifically --
	// the button generates both fields at once, so anchoring it to one
	// of them reads as "for that field only" (see findIdealAnchor() in
	// bew-ai-editor.js). Not itself a generatable field.
	$map['bew-elements-product-list'] = array(
		'_anchor'                          => 'bew_yith_buttons_show_label',
		'bew_yith_buttons_compare_label'  => 'button_label',
		'bew_yith_buttons_wishlist_label' => 'button_label',
	);
	// Only the button's own label -- title/excerpt/content are per-post
	// WP_Post data, not this widget's own settings, so BEW Pro's Blog
	// Generator (a separate, content-adapter-based card; see
	// mandatory-feature-checklist.md section 7) handles those instead of
	// this map.
	$map['bew-elements-blog'] = array(
		'read_more_text' => 'button_label',
	);

	// ── Elementor core ──────────────────────────────────────────────────
	// Not mapped (confirmed no regeneratable text): icon, spacer, rating,
	// social-icons (all icon/spacing/numeric/link-only, no text controls).
	// image-carousel's only TEXT control (carousel_name) is an accessibility
	// label, not visible content -- deliberately excluded. google_maps'
	// only TEXT control (address) is the real map query address, not prose
	// an AI should creatively rewrite -- deliberately excluded.
	$map['heading'] = array(
		'title' => 'headline',
	);
	$map['text-editor'] = array(
		'editor' => 'body_text',
	);
	$map['button'] = array(
		'text' => 'button_label',
	);
	$map['image'] = array(
		'caption' => 'caption_text',
	);
	$map['icon-list'] = array(
		'icon_list' => array(
			'text' => 'item_label',
		),
	);
	$map['icon-box'] = array(
		'title_text'       => 'headline',
		'description_text' => 'body_text',
	);
	$map['image-box'] = array(
		'title_text'       => 'headline',
		'description_text' => 'body_text',
	);
	$map['divider'] = array(
		'text' => 'label_text',
	);
	$map['counter'] = array(
		'title'  => 'label_text',
		'prefix' => 'symbol_text',
		'suffix' => 'symbol_text',
	);
	$map['progress'] = array(
		'title'      => 'label_text',
		'inner_text' => 'label_text',
	);
	$map['nested-tabs'] = array(
		'tabs' => array(
			'tab_title' => 'item_title',
		),
	);

	// ── ElementsKit Lite ────────────────────────────────────────────────
	// Not mapped (confirmed no regeneratable text): ekit-nav-menu (selects
	// an existing WP nav menu; item labels live in wp_posts, not this
	// widget's settings), elementskit-contact-form7 (same pattern as BEW's
	// CF7 widget -- selects an existing form by ID).
	$map['elementskit-heading'] = array(
		'ekit_heading_title'       => 'headline',
		'ekit_heading_sub_title'   => 'subheadline',
		'ekit_heading_extra_title' => 'body_text',
		'shadow_text_content'      => 'decorative_text',
	);
	$map['elementskit-video'] = array(
		'ekit_video_popup_button_title' => 'button_label',
	);
	$map['elementskit-progressbar'] = array(
		'ekit_progressbar_title' => 'item_title',
	);
	$map['elementskit-image-accordion'] = array(
		'ekit_img_accordion_items' => array(
			'ekit_img_accordion_title'        => 'item_title',
			'ekit_img_accordion_button_label' => 'item_button_label',
		),
	);
	$map['elementskit-team'] = array(
		'ekit_team_name'              => 'person_name',
		'ekit_team_position'          => 'job_title',
		'ekit_team_short_description' => 'body_text',
		'ekit_team_description'       => 'body_text',
		'ekit_team_social_icons'      => array(
			'ekit_team_label' => 'item_label',
		),
	);
	$map['elementskit-testimonial'] = array(
		'ekit_testimonial_data' => array(
			'client_name' => 'person_name',
			'designation' => 'job_title',
			'review'      => 'body_text',
		),
	);
	$map['elementskit-accordion'] = array(
		'ekit_accordion_items' => array(
			'acc_title'   => 'item_title',
			'acc_content' => 'item_body_text',
		),
	);
	$map['elementskit-piechart'] = array(
		'ekit_piechart_title'            => 'item_title',
		'ekit_piechart_item_description' => 'item_description',
	);
	$map['elementskit-social-media'] = array(
		'ekit_socialmedia_add_icons' => array(
			'ekit_socialmedia_label' => 'item_label',
		),
	);
	$map['elementskit-header-search'] = array(
		'ekit_search_placeholder_text' => 'placeholder_text',
	);
	$map['elementskit-button'] = array(
		'ekit_btn_text' => 'button_label',
	);
	$map['elementskit-mail-chimp'] = array(
		'ekit_mail_chimp_opt_in_success_message' => 'success_message',
		'ekit_mail_chimp_submit'                 => 'button_label',
		'ekit_mail_chimp_success_message'        => 'success_message',
		'ekit_mail_chimp_fields_repeater'        => array(
			'ekit_mail_chimp_field_label'       => 'field_label',
			'ekit_mail_chimp_field_placeholder' => 'field_placeholder',
		),
	);
	$map['elementskit-header-offcanvas'] = array(
		'ekit_offcanvas_menu_text'       => 'toggle_label',
		'ekit_offcanvas_menu_close_text' => 'toggle_label',
	);
	$map['elementskit-countdown-timer'] = array(
		'ekit_countdown_timer_weeks_label'         => 'unit_label',
		'ekit_countdown_timer_days_label'          => 'unit_label',
		'ekit_countdown_timer_hours_label'         => 'unit_label',
		'ekit_countdown_timer_minutes_hours_label' => 'unit_label',
		'ekit_countdown_timer_seconds_hours_label' => 'unit_label',
		'ekit_countdown_timer_title'               => 'headline',
		'ekit_countdown_timer_expiry_content'      => 'body_text',
	);
	$map['elementskit-post-list'] = array(
		'icon_list' => array(
			'text' => 'item_label',
		),
	);

	// ── Sina Extension for Elementor ────────────────────────────────────
	// Several widgets share a `button_content()` helper (inc/sina-ext-
	// controls.php, class Sina_Common_Data) that produces a `{prefix}_text`
	// / `{prefix}_tooltip_text` pair -- source of the repeated btn_text /
	// read_more_text / tooltip_text pattern below.
	//
	// Not mapped (confirmed no regeneratable text): none entirely excluded
	// at the widget level, but several individual fields below are excluded
	// with an inline reason -- see comments.
	$map['sina_title'] = array(
		'title'      => 'headline',
		'title_span' => 'highlight_text',
		'subtitle'   => 'subheadline',
		'desc'       => 'body_text',
	);
	$map['sina_counter'] = array(
		'title'  => 'label_text',
		'prefix' => 'symbol_text',
		'suffix' => 'symbol_text',
	);
	$map['sina_team'] = array(
		'name'     => 'person_name',
		'position' => 'job_title',
		'desc'     => 'body_text',
		// social_icons[].social_name intentionally excluded: confirmed via
		// render() that it's editor-only and never output on the live page.
	);
	$map['sina_video'] = array(
		'title' => 'caption_text',
	);
	$map['sina_portfolio'] = array(
		'reset_text' => 'button_label',
		'portfolio'  => array(
			'item_name' => 'item_title',
			'item_desc' => 'item_description',
			// item.category intentionally excluded: it's the literal CSS
			// filter key (data-filter) that builds the filter menu -- must
			// stay identical across items sharing a category, not prose.
		),
	);
	$map['sina_accordion'] = array(
		// desc only renders when accordion[].save_templates is off; when a
		// saved Elementor template is used instead this field is unused for
		// that item. Left mapped -- the extraction step should check the
		// condition per item before writing.
		'accordion' => array(
			'title' => 'item_title',
			'desc'  => 'item_description',
		),
	);
	$map['sina_dynamic_button'] = array(
		// Only used when btn_type='static'; in 'page'/'taxonomy' mode the
		// label comes dynamically from the chosen page/term instead.
		'btn_text' => 'button_label',
	);
	$map['sina_mc_subscribe'] = array(
		'email_placeholder' => 'placeholder_text',
		'fname_placeholder' => 'placeholder_text',
		'lname_placeholder' => 'placeholder_text',
		'phone_placeholder' => 'placeholder_text',
		'successs_message'  => 'success_message', // sic -- verbatim key from source, do not "fix" the typo.
		'process_text'       => 'status_text',
		'btn_text'           => 'button_label',
	);
	$map['sina_banner_slider'] = array(
		'slides' => array(
			'title'              => 'item_headline',
			'title_span'         => 'item_highlight_text',
			'subtitle'           => 'item_subheadline',
			'desc'               => 'item_body_text',
			'primary_btn_text'   => 'item_button_label',
			'secondary_btn_text' => 'item_button_label',
		),
		'pbtn_text'         => 'button_label',
		'pbtn_tooltip_text' => 'tooltip_text',
		'sbtn_text'         => 'button_label',
		'sbtn_tooltip_text' => 'tooltip_text',
	);
	$map['sina_blogpost'] = array(
		'btn_text'       => 'button_label',
		'read_more_text' => 'button_label',
	);
	$map['sina_piechart'] = array(
		'title'  => 'label_text',
		'prefix' => 'symbol_text',
		'suffix' => 'symbol_text',
	);
	$map['sina_pricing'] = array(
		'title'            => 'plan_name',
		'ribbon_title'     => 'badge_text',
		'price_save_value' => 'promo_text',
		'price_prefix'     => 'symbol_text',
		'price_suffix'     => 'symbol_text',
		'item'             => array(
			'title' => 'feature_text',
		),
		'btn_text'         => 'button_label',
		// price intentionally excluded: a literal cost figure (e.g. "$20"),
		// factual business data, not prose to creatively rewrite.
	);
	$map['sina_brand_carousel'] = array(
		// title is confirmed via render() to be used only as the logo
		// image's alt attribute -- real, rendered, but never visible prose.
		'brand' => array(
			'title' => 'image_alt_text',
		),
	);
	$map['sina_countdown'] = array(
		// Only rendered when action='text'.
		'message' => 'expired_message',
	);
	$map['sina_posts_carousel'] = array(
		'read_more_text' => 'button_label',
	);

	/**
	 * Filter the AI field-role map, e.g. to add mappings for a plugin not
	 * covered here, or override a role label.
	 *
	 * @param array<string, array<string, mixed>> $map
	 */
	return apply_filters( 'bosa_ewc_ai_field_map', $map );
}
