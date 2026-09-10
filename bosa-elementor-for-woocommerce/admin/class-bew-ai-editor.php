<?php
/**
 * BEW AI — in-editor integration.
 *
 * Loads the "BEW AI" button into the Elementor editor itself, entirely
 * separate from Elementor's own native AI feature (no shared code, no
 * dependency on it). Enqueued via Elementor's own editor-specific hooks,
 * distinct from BEW_Admin's admin_enqueue_scripts (the Elementor editor is a
 * different page/context than the BEW Settings screen).
 *
 * @package BosaMiller\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BEW_AI_Editor {

	public function __construct() {
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_styles' ] );
	}

	public function enqueue_scripts() {
		if ( ! BEW_Plugin_Settings::is_ai_feature_enabled( 'bew_ai_text_generation' ) ) {
			return;
		}

		wp_enqueue_script(
			'bew-ai-editor',
			BEW_URL . 'admin/assets/js/bew-ai-editor.js',
			[ 'jquery', 'elementor-common', 'elementor-editor-modules', 'elementor-editor-document' ],
			BEW_VERSION,
			true
		);

		$providers  = BEW_Plugin_Settings::get( 'ai_providers', [] );
		$types      = function_exists( 'bew_get_ai_provider_types' ) ? bew_get_ai_provider_types() : [];
		$connected  = [];

		foreach ( $providers as $p ) {
			if ( empty( $p['id'] ) ) {
				continue;
			}
			$connected[] = [
				'id'         => $p['id'],
				'label'      => isset( $p['label'] ) ? $p['label'] : '',
				'type_label' => isset( $types[ $p['type'] ]['label'] ) ? $types[ $p['type'] ]['label'] : $p['type'],
				'model'      => isset( $p['model'] ) ? $p['model'] : '',
				'model_label' => isset( $p['model_label'] ) ? $p['model_label'] : '',
				'is_default' => ! empty( $p['is_default'] ),
				// Whatever's already cached from Settings' own Load/Refresh
				// Models action -- never fetched live from here, so opening
				// this card never hits the provider's API. Empty until the
				// user has fetched at least once in Settings (or the 24h
				// cache expires), same as image_capable_providers() in Pro.
				'models'     => function_exists( 'bew_get_cached_ai_provider_models_readonly' ) && ! empty( $p['api_key_enc'] ) && class_exists( 'BEW_Crypto' )
					? bew_get_cached_ai_provider_models_readonly( $p['type'], BEW_Crypto::decrypt( $p['api_key_enc'] ), 'text' )
					: [],
			];
		}

		wp_localize_script( 'bew-ai-editor', 'bewAiEditor', [
			'nonce'              => wp_create_nonce( 'bew-settings-nonce' ),
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'settingsUrl'        => admin_url( 'admin.php?page=bew-settings' ),
			'fieldMap'           => function_exists( 'bosa_ewc_get_ai_field_map' ) ? bosa_ewc_get_ai_field_map() : [],
			'hasConnectedProvider' => bew_has_connected_ai_provider(),
			'connectedProviders' => $connected,
			'isBeta'             => bew_ai_is_beta(),
			'iconUrl'            => BEW_URL . 'admin/assets/svg/bew-ai.svg',
			// Shown as a small upgrade note at the bottom of the card --
			// pointless once Pro is already active, so gated the same way
			// the "Upgrade to BEW Pro" admin menu link is.
			'showUpgradeNote'    => class_exists( 'BEW_Admin' ) && ! class_exists( 'BEW_Pro' ),
			'pricingUrl'         => class_exists( 'BEW_Admin' ) ? BEW_Admin::PRICING_URL : 'https://bew.bosathemes.com/pricing',
			'i18n'               => [
				'buttonLabel'        => __( 'BEW AI', 'bosa-elementor-for-woocommerce' ),
				'betaTag'            => __( 'Beta', 'bosa-elementor-for-woocommerce' ),
				'generatorSubtitle'  => __( 'Text Generator', 'bosa-elementor-for-woocommerce' ),
				'connectMessage'     => __( 'Connect an AI provider in BEW Settings to use this feature.', 'bosa-elementor-for-woocommerce' ),
				'openSettings'       => __( 'Open AI Integration Settings', 'bosa-elementor-for-woocommerce' ),
				'checkAgain'         => __( "I've Connected — Check Again", 'bosa-elementor-for-woocommerce' ),
				'checking'           => __( 'Checking…', 'bosa-elementor-for-woocommerce' ),
				'providerLabel'      => __( 'Provider', 'bosa-elementor-for-woocommerce' ),
				'modelLabel'         => __( 'Model', 'bosa-elementor-for-woocommerce' ),
				'instructionLabel'   => __( 'Instruction (optional)', 'bosa-elementor-for-woocommerce' ),
				'instructionNote'    => __( 'Providing clear guidance helps AI match your intended tone and direction.', 'bosa-elementor-for-woocommerce' ),
				'instructionPlaceholder' => __( 'e.g. make it shorter, more playful…', 'bosa-elementor-for-woocommerce' ),
				'usingLabel'         => __( 'Using', 'bosa-elementor-for-woocommerce' ),
				'generate'           => __( 'Generate', 'bosa-elementor-for-woocommerce' ),
				'generating'         => __( 'Generating…', 'bosa-elementor-for-woocommerce' ),
				'apply'              => __( 'Apply', 'bosa-elementor-for-woocommerce' ),
				'regenerate'         => __( 'Regenerate', 'bosa-elementor-for-woocommerce' ),
				'cancel'             => __( 'Cancel', 'bosa-elementor-for-woocommerce' ),
				'errorMessage'       => __( 'Something went wrong. Please try again.', 'bosa-elementor-for-woocommerce' ),
				'showMore'           => __( 'Show more', 'bosa-elementor-for-woocommerce' ),
				'showLess'           => __( 'Show less', 'bosa-elementor-for-woocommerce' ),
				'noFieldsSelected'   => __( 'Select at least one field', 'bosa-elementor-for-woocommerce' ),
				'noEligibleFields'   => __( 'No AI-eligible fields on this widget yet', 'bosa-elementor-for-woocommerce' ),
				'itemLabel'          => __( 'Item', 'bosa-elementor-for-woocommerce' ),
				'upgradeNoteText'    => __( 'Get BEW Pro to access advanced AI features.', 'bosa-elementor-for-woocommerce' ),
				'upgradeLinkLabel'   => __( 'Upgrade Now', 'bosa-elementor-for-woocommerce' ),
				/* translators: %s: field name, e.g. "Headline". */
				'historyTitle'       => __( 'History — %s', 'bosa-elementor-for-woocommerce' ),
				'historyIconLabel'   => __( 'View history for this field', 'bosa-elementor-for-woocommerce' ),
				'historyBack'        => __( 'Back', 'bosa-elementor-for-woocommerce' ),
				'historyEmpty'       => __( 'No history yet for this field.', 'bosa-elementor-for-woocommerce' ),
				'historyRestore'     => __( 'Restore', 'bosa-elementor-for-woocommerce' ),
				'historyDeleteLabel' => __( 'Delete this entry', 'bosa-elementor-for-woocommerce' ),
				'historyClearAll'    => __( 'Clear all history for this page', 'bosa-elementor-for-woocommerce' ),
				'historyClearConfirm' => __( 'Delete all saved history for this page? This can\'t be undone.', 'bosa-elementor-for-woocommerce' ),
				'historyClearYes'    => __( 'Yes, clear it', 'bosa-elementor-for-woocommerce' ),
				'historyClearNo'     => __( 'Cancel', 'bosa-elementor-for-woocommerce' ),
				'historyJustNow'     => __( 'Just now', 'bosa-elementor-for-woocommerce' ),
				/* translators: %d: number of minutes. */
				'historyMinutesAgo'  => __( '%dm ago', 'bosa-elementor-for-woocommerce' ),
				/* translators: %d: number of hours. */
				'historyHoursAgo'    => __( '%dh ago', 'bosa-elementor-for-woocommerce' ),
				/* translators: %d: number of days. */
				'historyDaysAgo'     => __( '%dd ago', 'bosa-elementor-for-woocommerce' ),
				'historyLoading'     => __( 'Loading…', 'bosa-elementor-for-woocommerce' ),
				'historyRestored'    => __( 'Restored.', 'bosa-elementor-for-woocommerce' ),
				/* translators: 1: current item number, 2: total number of items. */
				'historyItemTitle'   => __( 'History — %1$d of %2$d', 'bosa-elementor-for-woocommerce' ),
				'historyPrevious'    => __( 'Previous', 'bosa-elementor-for-woocommerce' ),
				'historyNext'        => __( 'Next', 'bosa-elementor-for-woocommerce' ),
				'historyNoItems'     => __( 'Nothing to show history for yet.', 'bosa-elementor-for-woocommerce' ),
				'historyClearAllItem' => __( 'Clear all history for this item', 'bosa-elementor-for-woocommerce' ),
				// Shown in the card header for every Pro-only generator, never
				// on the free Text Generator's own card.
				'proTag'             => __( 'Pro', 'bosa-elementor-for-woocommerce' ),
				// Saved instruction presets -- mandatory in every card, same
				// as History; reuses i18n.historyBack for the manage view's
				// own back link rather than duplicating it.
				'presetManage'         => __( 'Manage presets', 'bosa-elementor-for-woocommerce' ),
				'presetLoadPlaceholder' => __( 'Load a saved preset', 'bosa-elementor-for-woocommerce' ),
				'presetSaveAs'         => __( 'Save as preset', 'bosa-elementor-for-woocommerce' ),
				'presetNameLabel'      => __( 'Preset name', 'bosa-elementor-for-woocommerce' ),
				'presetNamePlaceholder' => __( 'e.g. Casual bakery voice', 'bosa-elementor-for-woocommerce' ),
				'presetSaveConfirm'    => __( 'Save', 'bosa-elementor-for-woocommerce' ),
				'presetSaveCancel'     => __( 'Cancel', 'bosa-elementor-for-woocommerce' ),
				'presetSaveNeedsText'  => __( 'Write an instruction first.', 'bosa-elementor-for-woocommerce' ),
				'presetsListTitle'     => __( 'Saved presets', 'bosa-elementor-for-woocommerce' ),
				'presetsEmpty'         => __( 'No presets saved yet.', 'bosa-elementor-for-woocommerce' ),
				'presetRenameLabel'    => __( 'Rename this preset', 'bosa-elementor-for-woocommerce' ),
				'presetDeleteLabel'    => __( 'Delete this preset', 'bosa-elementor-for-woocommerce' ),
			],
		] );

		wp_set_script_translations( 'bew-ai-editor', 'bosa-elementor-for-woocommerce' );
	}

	public function enqueue_styles() {
		if ( ! BEW_Plugin_Settings::is_ai_feature_enabled( 'bew_ai_text_generation' ) ) {
			return;
		}

		wp_enqueue_style(
			'bew-ai-editor',
			BEW_URL . 'admin/assets/css/bew-ai-editor.css',
			[],
			BEW_VERSION
		);
	}
}
