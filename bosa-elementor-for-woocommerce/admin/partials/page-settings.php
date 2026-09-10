<?php
/**
 * BEW Settings Page
 *
 * Five tabs: Widgets | Templates | Modules | AI Integration | General
 * The Templates tab controls only the Elementor editor Template Library modal.
 * The AI Integration tab manages user-supplied AI provider API keys.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BEW_PATH . 'admin/partials/components/component-banner.php';
require_once BEW_PATH . 'admin/partials/components/component-card.php';

$bew_is_pro_active = class_exists( 'BEW_Pro' );
?>
<div class="wrap bew-admin-page bew-admin-page-settings">

	<h1><?php esc_html_e( 'BEW Settings', 'bosa-elementor-for-woocommerce' ); ?></h1>

	<?php
	if ( $bew_is_pro_active ) {
		bew_render_banner( BEW_URL . 'admin/assets/images/bew-cta-sm-pro-banner.jpg' );
	} else {
		bew_render_banner( BEW_URL . 'admin/assets/images/bew-cta-sm-banner.jpg', '', 'https://bew.bosathemes.com/pricing' );
	}
	?>

	<div class="bew-tab-nav">
		<button class="bew-tab-button active" data-tab="widgets">
			<?php esc_html_e( 'Widgets', 'bosa-elementor-for-woocommerce' ); ?>
		</button>
		<button class="bew-tab-button" data-tab="templates">
			<?php esc_html_e( 'Templates', 'bosa-elementor-for-woocommerce' ); ?>
		</button>
		<button class="bew-tab-button" data-tab="modules">
			<?php esc_html_e( 'Modules', 'bosa-elementor-for-woocommerce' ); ?>
		</button>
		<button class="bew-tab-button" data-tab="ai-integration">
			<img class="bew-ai-icon" src="<?php echo esc_url( BEW_URL . 'admin/assets/svg/bew-ai.svg' ); ?>" alt="" />
			<?php esc_html_e( 'AI Builder', 'bosa-elementor-for-woocommerce' ); ?>
			<?php if ( function_exists( 'bew_ai_is_beta' ) && bew_ai_is_beta() ) : ?>
				<span class="bew-beta-tag"><?php esc_html_e( 'Beta', 'bosa-elementor-for-woocommerce' ); ?></span>
			<?php endif; ?>
		</button>
		<button class="bew-tab-button" data-tab="general">
			<?php esc_html_e( 'General', 'bosa-elementor-for-woocommerce' ); ?>
		</button>
	</div>

	<!-- Tab: Widgets (default active) -->
	<div class="bew-tab-content active" id="bew-tab-widgets">
		<h3><?php esc_html_e( 'Widgets', 'bosa-elementor-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Enable or disable widgets. Disabled widgets will not appear in Elementor.', 'bosa-elementor-for-woocommerce' ); ?></p>

		<?php
		$is_pro_active  = class_exists( 'BEW_Pro' );
		$widget_toggles = BEW_Plugin_Settings::get( 'widget_toggles', [] );

		$general_widgets = [
			[
				'name'      => __( 'Site Logo', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_site_logo',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/site-logo-widget/',
			],
			[
				'name'      => __( 'Dropdown Nav', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_dropdown_nav',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/dropdown-nav-widget/',
			],
			[
				'name'      => __( 'Blog Grid', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_blog',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/blog-grid-widget/',
			],
			[
				'name'      => __( 'Contact Form 7', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_contact_form_7',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/contact-form-7-widget/',
			],
			[
				'name'      => __( 'Image Carousel', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_pro_image_carousel',
				'pro'       => true,
				'demo_link' => 'https://bew.bosathemes.com/image-carousel-widget/',
			],
			[
				'name'      => __( 'Testimonial Slider', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_pro_testimonial_slider',
				'pro'       => true,
				'demo_link' => 'https://bew.bosathemes.com/testimonial-slider-widget/',
			],
		];

		$woo_widgets = [
			[
				'name'      => __( 'Woo Categories', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_categories',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-categories-widget/',
			],
			[
				'name'      => __( 'Woo Archive Carousel', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_carousel_products',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-archive-carousel-widget/',
			],
			[
				'name'      => __( 'Woo Categories List', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_categories_list',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-categories-list-widget/',
			],
			[
				'name'      => __( 'Woo Archive Products', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_products',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-archive-products-widget/',
			],
			[
				'name'      => __( 'Woo Products List', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_products_list',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-products-list-widget/',
			],
			[
				'name'      => __( 'Woo Cart', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_cart',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-cart-widget/',
			],
			[
				'name'      => __( 'Woo My Account', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_my_account',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-my-account-widget/',
			],
			[
				'name'      => __( 'Woo Search', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_search',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-search-widget/',
			],
			[
				'name'      => __( 'Woo YITH Compare', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_yith_compare',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-yith-compare-widget/',
			],
			[
				'name'      => __( 'Woo YITH Wishlist', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_yith_wishlist',
				'pro'       => false,
				'demo_link' => 'https://bew.bosathemes.com/woo-yith-wishlist-widget/',
			],
			[
				'name'      => __( 'Woo Product Tabs', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_pro_product_tab',
				'pro'       => true,
				'demo_link' => 'https://bew.bosathemes.com/woo-product-tabs-widget/',
			],
			[
				'name'      => __( 'Woo Hot Deals', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_pro_hot_deals',
				'pro'       => true,
				'demo_link' => 'https://bew.bosathemes.com/woo-hot-deals-widget/',
			],
			[
				'name'      => __( 'Woo Product Slider', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_pro_product_slider',
				'pro'       => true,
				'demo_link' => 'https://bew.bosathemes.com/woo-product-slider-widget/',
			],
			[
				'name'      => __( 'Woo Grid Product', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_pro_grid_products',
				'pro'       => true,
				'demo_link' => 'https://bew.bosathemes.com/woo-grid-product-widget/',
			],
			[
				'name'      => __( 'Woo Grid Carousel', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_pro_grid_carousel',
				'pro'       => true,
				'demo_link' => 'https://bew.bosathemes.com/woo-grid-carousel-widget/',
			],
			[
				'name'      => __( 'Woo Product Accordion', 'bosa-elementor-for-woocommerce' ),
				'id'        => 'bew_product_accordion',
				'pro'       => true,
				'demo_link' => 'https://bew.bosathemes.com/woo-product-accordion-widget/',
			],
		];
		?>

		<h4 class="bew-subsection-title"><?php esc_html_e( 'General', 'bosa-elementor-for-woocommerce' ); ?></h4>
		<div class="bew-cards-grid">
			<?php
			foreach ( $general_widgets as $widget ) {
				$is_pro = $widget['pro'];
				if ( $is_pro && ! $is_pro_active ) {
					$enabled = false;
				} else {
					$enabled = isset( $widget_toggles[ $widget['id'] ] ) ? (bool) $widget_toggles[ $widget['id'] ] : true;
				}
				bew_render_card( [
					'name'         => $widget['name'],
					'toggle_id'    => $widget['id'],
					'toggle_state' => $enabled,
					'toggle_type'  => 'widget',
					'pro'          => $is_pro,
					'demo_link'    => $widget['demo_link'],
				] );
			}
			?>
		</div>

		<h4 class="bew-subsection-title"><?php esc_html_e( 'WooCommerce', 'bosa-elementor-for-woocommerce' ); ?></h4>
		<div class="bew-cards-grid">
			<?php
			foreach ( $woo_widgets as $widget ) {
				$is_pro = $widget['pro'];
				if ( $is_pro && ! $is_pro_active ) {
					$enabled = false;
				} else {
					$enabled = isset( $widget_toggles[ $widget['id'] ] ) ? (bool) $widget_toggles[ $widget['id'] ] : true;
				}
				bew_render_card( [
					'name'         => $widget['name'],
					'toggle_id'    => $widget['id'],
					'toggle_state' => $enabled,
					'toggle_type'  => 'widget',
					'pro'          => $is_pro,
					'demo_link'    => $widget['demo_link'],
				] );
			}
			?>
		</div>

		<button id="bew-save-settings-widgets" class="bew-box-button bew-button-primary">
			<?php esc_html_e( 'Save Changes', 'bosa-elementor-for-woocommerce' ); ?>
		</button>
	</div>

	<!-- Tab: Templates — controls Elementor modal only -->
	<div class="bew-tab-content" id="bew-tab-templates" style="display:none;">
		<h3><?php esc_html_e( 'Templates', 'bosa-elementor-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Enable or disable the BEW Template Library inside the Elementor editor.', 'bosa-elementor-for-woocommerce' ); ?></p>
		<div class="bew-cards-grid">
			<?php
			$template_enabled = BEW_Plugin_Settings::get( 'template_library', true );
			bew_render_card( [
				'name'         => __( 'Template Library', 'bosa-elementor-for-woocommerce' ),
				'toggle_id'    => 'template_library',
				'toggle_state' => $template_enabled,
				'demo_link'    => 'https://bew.bosathemes.com/#template-list',
				'demo_text'    => __( 'VIEW DEMOS', 'bosa-elementor-for-woocommerce' ),
			] );
			?>
		</div>

		<div class="update-message notice inline notice-warning notice-alt">
			<p>
				<?php
				printf(
					/* translators: 1: opening <strong><a> tags linking to the Store Hub demo, 2: closing </a></strong> tags, 3: opening <strong><a> tags linking to the Store Hub Pro demo, 4: closing </a></strong> tags, 5: opening <strong> tags linking to the Template Library, 6: closing </strong> tags */
					esc_html__( 'Notice: All existing Free & Pro templates previews have been moved to %1$sStore Hub%2$s. They can now be imported directly from the %3$sTemplate Library%4$s within the Elementor editor, just like any other template. For more details, please refer to the %5$sHow to Import Templates%6$s guide.', 'bosa-elementor-for-woocommerce' ),
					'<strong><a href="' . esc_url( 'https://demo.bosathemes.com/bosa/store-hub-pro/' ) . '" target="_blank" rel="noopener noreferrer">',
					'</a></strong>',
					'<strong>',
					'</strong>',
					'<strong><a href="' . esc_url( 'https://bew.bosathemes.com/docs/how-to-import-templates/' ) . '" target="_blank" rel="noopener noreferrer">',
					'</a></strong>'
				);
				?>
			</p>
		</div>
		
		<button id="bew-save-settings-templates" class="bew-box-button bew-button-primary">
			<?php esc_html_e( 'Save Changes', 'bosa-elementor-for-woocommerce' ); ?>
		</button>
	</div>

	<!-- Tab: Modules -->
	<div class="bew-tab-content" id="bew-tab-modules" style="display:none;">
		<h3><?php esc_html_e( 'Modules', 'bosa-elementor-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'Enable or disable modules.', 'bosa-elementor-for-woocommerce' ); ?></p>
		<?php
		$modules = [
			[
				'name' => __( 'Back to Top', 'bosa-elementor-for-woocommerce' ),
				'id'   => 'back_to_top',
				'pro'  => false,
			],
			[
				'name' => __( 'Site Preloader', 'bosa-elementor-for-woocommerce' ),
				'id'   => 'preloader',
				'pro'  => false,
			],
			[
				'name' => __( 'Reading Progressbar', 'bosa-elementor-for-woocommerce' ),
				'id'   => 'reading-progressbar',
				'pro'  => true,
			],
		];
		?>
		<div class="bew-cards-grid">
			<?php
			foreach ( $modules as $mod ) {
				$is_pro     = $mod['pro'];
				$is_enabled = ( $is_pro && ! $is_pro_active )
					? false
					: BEW_Plugin_Settings::is_module_enabled( $mod['id'] );
				bew_render_card( [
					'name'         => $mod['name'],
					'toggle_id'    => 'module_' . $mod['id'],
					'toggle_state' => $is_enabled,
					'toggle_type'  => 'module',
					'pro'          => $is_pro,
				] );
			}
			?>
		</div>
		<button id="bew-save-settings-modules" class="bew-box-button bew-button-primary">
			<?php esc_html_e( 'Save Changes', 'bosa-elementor-for-woocommerce' ); ?>
		</button>
	</div>

	<!-- Tab: AI Integration -->
	<div class="bew-tab-content" id="bew-tab-ai-integration" style="display:none;">
		<h3>
			<img class="bew-ai-icon" src="<?php echo esc_url( BEW_URL . 'admin/assets/svg/bew-ai.svg' ); ?>" alt="" />
			<?php esc_html_e( 'AI Builder', 'bosa-elementor-for-woocommerce' ); ?>
			<?php if ( function_exists( 'bew_ai_is_beta' ) && bew_ai_is_beta() ) : ?>
				<span class="bew-beta-tag"><?php esc_html_e( 'Beta', 'bosa-elementor-for-woocommerce' ); ?></span>
			<?php endif; ?>
		</h3>

		<h4 class="bew-subsection-title"><?php esc_html_e( 'AI Features', 'bosa-elementor-for-woocommerce' ); ?></h4>
		<p><?php esc_html_e( 'Enable or disable individual AI features. Disabled features will not appear in the editor.', 'bosa-elementor-for-woocommerce' ); ?></p>
		<div class="bew-cards-grid">
			<?php
			$ai_feature_toggles = BEW_Plugin_Settings::get( 'ai_feature_toggles', [] );
			$ai_features        = [
				[
					'name' => __( 'Text Generator', 'bosa-elementor-for-woocommerce' ),
					'id'   => 'bew_ai_text_generation',
					'pro'  => false,
				],
				[
					'name' => __( 'Image Generator', 'bosa-elementor-for-woocommerce' ),
					'id'   => 'bew_ai_image_generation',
					'pro'  => true,
				],
				[
					'name' => __( 'Bulk Text & Image Generator', 'bosa-elementor-for-woocommerce' ),
					'id'   => 'bew_ai_bulk_text_generation',
					'pro'  => true,
				],
				[
					'name' => __( 'Repeater Generator', 'bosa-elementor-for-woocommerce' ),
					'id'   => 'bew_ai_repeater_generation',
					'pro'  => true,
				],
				[
					'name' => __( 'Products Generator', 'bosa-elementor-for-woocommerce' ),
					'id'   => 'bew_ai_products_generation',
					'pro'  => true,
				],
				[
					'name' => __( 'Blog Generator', 'bosa-elementor-for-woocommerce' ),
					'id'   => 'bew_ai_blog_generation',
					'pro'  => true,
				],
			];
			foreach ( $ai_features as $feature ) {
				$is_pro = $feature['pro'];
				if ( $is_pro && ! $is_pro_active ) {
					$enabled = false;
				} else {
					$enabled = isset( $ai_feature_toggles[ $feature['id'] ] ) ? (bool) $ai_feature_toggles[ $feature['id'] ] : true;
				}
				bew_render_card( [
					'name'         => $feature['name'],
					'toggle_id'    => $feature['id'],
					'toggle_state' => $enabled,
					'toggle_type'  => 'ai_feature',
					'pro'          => $is_pro,
				] );
			}
			?>
		</div>
		<button id="bew-save-settings-ai-features" class="bew-box-button bew-button-primary">
			<?php esc_html_e( 'Save Changes', 'bosa-elementor-for-woocommerce' ); ?>
		</button>

		<?php
		$ai_provider_types = function_exists( 'bew_get_ai_provider_types' ) ? bew_get_ai_provider_types() : [];
		$ai_providers       = BEW_Plugin_Settings::get( 'ai_providers', [] );
		$ai_first_type      = $ai_provider_types ? array_key_first( $ai_provider_types ) : '';
		?>

		<h4 class="bew-subsection-title" style="margin-top:32px;"><?php esc_html_e( 'AI Provider Integration', 'bosa-elementor-for-woocommerce' ); ?></h4>
		<p><?php esc_html_e( 'Connect AI providers using your own API keys. Keys are stored encrypted and are only used by AI-powered features in this plugin.', 'bosa-elementor-for-woocommerce' ); ?></p>
		<p class="description"><?php esc_html_e( 'Bring the account you already use with your AI providers — no separate subscription, no markup. Pay the provider directly and choose the model that best fits your needs and budget.', 'bosa-elementor-for-woocommerce' ); ?></p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="bew-ai-provider-type"><?php esc_html_e( 'Provider', 'bosa-elementor-for-woocommerce' ); ?></label></th>
				<td>
					<select id="bew-ai-provider-type">
						<?php foreach ( $ai_provider_types as $type_key => $type_data ) : ?>
							<option value="<?php echo esc_attr( $type_key ); ?>"><?php echo esc_html( $type_data['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="bew-ai-provider-label"><?php esc_html_e( 'Name', 'bosa-elementor-for-woocommerce' ); ?></label></th>
				<td>
					<input
						type="text"
						id="bew-ai-provider-label"
						class="regular-text"
						placeholder="<?php esc_attr_e( 'Optional label, e.g. "Main OpenAI Key"', 'bosa-elementor-for-woocommerce' ); ?>"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="bew-ai-provider-key"><?php esc_html_e( 'API Key', 'bosa-elementor-for-woocommerce' ); ?></label></th>
				<td>
					<input type="password" id="bew-ai-provider-key" class="regular-text" autocomplete="off" />
					<p class="description" id="bew-ai-provider-help">
						<?php
						if ( $ai_first_type ) {
							echo wp_kses_post( $ai_provider_types[ $ai_first_type ]['help_html'] );
						}
						?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Model', 'bosa-elementor-for-woocommerce' ); ?></th>
				<td>
					<button type="button" id="bew-ai-load-models" class="bew-box-button bew-button-secondary">
						<?php esc_html_e( 'Load Models', 'bosa-elementor-for-woocommerce' ); ?>
					</button>
					<span id="bew-ai-load-models-status" class="description"></span>
					<div id="bew-ai-model-field" style="margin-top:8px;"></div>
					<p class="description"><?php esc_html_e( 'Enter your API key above, then load the models it can access and pick one as the default.', 'bosa-elementor-for-woocommerce' ); ?></p>
				</td>
			</tr>
		</table>
		<button type="button" id="bew-ai-provider-add" class="bew-box-button bew-button-primary">
			<?php esc_html_e( 'Add Provider', 'bosa-elementor-for-woocommerce' ); ?>
		</button>

		<h4 class="bew-subsection-title" style="margin-top:32px;"><?php esc_html_e( 'Connected Providers', 'bosa-elementor-for-woocommerce' ); ?></h4>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Name', 'bosa-elementor-for-woocommerce' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Provider', 'bosa-elementor-for-woocommerce' ); ?></th>
					<th scope="col"><?php esc_html_e( 'API Key', 'bosa-elementor-for-woocommerce' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Added', 'bosa-elementor-for-woocommerce' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Actions', 'bosa-elementor-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody id="bew-ai-providers-tbody">
				<?php if ( empty( $ai_providers ) ) : ?>
					<tr class="bew-ai-providers-empty-row">
						<td colspan="5"><?php esc_html_e( 'No AI providers connected yet.', 'bosa-elementor-for-woocommerce' ); ?></td>
					</tr>
				<?php else : ?>
					<?php foreach ( $ai_providers as $provider ) : ?>
						<?php
						$type_label = isset( $ai_provider_types[ $provider['type'] ]['label'] )
							? $ai_provider_types[ $provider['type'] ]['label']
							: $provider['type'];
						$is_default = ! empty( $provider['is_default'] );
						?>
						<tr data-provider-id="<?php echo esc_attr( $provider['id'] ); ?>" data-provider-type="<?php echo esc_attr( $provider['type'] ); ?>">
							<td>
								<?php echo esc_html( $provider['label'] ); ?>
								<?php if ( $is_default ) : ?>
									<span class="bew-default-tag bew-ai-provider-default-tag"><?php esc_html_e( 'Default', 'bosa-elementor-for-woocommerce' ); ?></span>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $type_label ); ?></td>
							<td><code>••••<?php echo esc_html( $provider['key_preview'] ); ?></code></td>
							<td><?php echo esc_html( $provider['created_at'] ); ?></td>
							<td>
								<div class="bew-provider-actions">
									<?php if ( ! $is_default ) : ?>
										<button
											type="button"
											class="bew-box-button bew-button-secondary bew-ai-provider-set-default"
											data-provider-id="<?php echo esc_attr( $provider['id'] ); ?>"
										>
											<?php esc_html_e( 'Set as Default', 'bosa-elementor-for-woocommerce' ); ?>
										</button>
									<?php endif; ?>
									<button
										type="button"
										class="bew-box-button bew-button-secondary bew-ai-provider-delete"
										data-provider-id="<?php echo esc_attr( $provider['id'] ); ?>"
									>
										<?php esc_html_e( 'Delete', 'bosa-elementor-for-woocommerce' ); ?>
									</button>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Tab: General -->
	<div class="bew-tab-content" id="bew-tab-general" style="display:none;">
		<h3><?php esc_html_e( 'General', 'bosa-elementor-for-woocommerce' ); ?></h3>

		<?php $rollback_version = BEW_Plugin_Settings::get_rollback_version(); ?>
		<?php if ( $rollback_version ) : ?>
		<div class="bew-rollback-section">
			<h3><?php esc_html_e( 'Rollback to Previous Version', 'bosa-elementor-for-woocommerce' ); ?></h3>
			<p><?php esc_html_e( 'Experiencing an issue with this version? You can rollback the previous version.', 'bosa-elementor-for-woocommerce' ); ?></p>
			<button id="bew-rollback-button" class="bew-box-button bew-button-primary bew-rollback-button" data-version="<?php echo esc_attr( $rollback_version ); ?>">
				<span class="dashicons dashicons-image-rotate"></span>
				<?php
				printf(
					/* translators: %s: version number */
					esc_html__( 'Reinstall v%s', 'bosa-elementor-for-woocommerce' ),
					esc_html( $rollback_version )
				);
				?>
			</button>
			<p class="bew-rollback-warning">
				<?php esc_html_e( 'Warning: Please backup your database before making the rollback.', 'bosa-elementor-for-woocommerce' ); ?>
			</p>
		</div>
		<?php endif; ?>
	</div>
</div>

<!-- Pro Upgrade Popup -->
<div id="bew-pro-popup" class="bew-pro-popup" style="display:none;">
	<div class="bew-pro-popup-overlay"></div>
	<div class="bew-pro-popup-content">
		<button type="button" class="bew-pro-popup-close">&times;</button>
		<h3><?php esc_html_e( 'Upgrade to Pro', 'bosa-elementor-for-woocommerce' ); ?></h3>
		<p><?php esc_html_e( 'This is available in the Pro version. Unlock all premium widgets, templates, and features for your store.', 'bosa-elementor-for-woocommerce' ); ?></p>
		<a href="https://bew.bosathemes.com/pricing" target="_blank" rel="noopener noreferrer" class="bew-box-button bew-button-primary">
			<?php esc_html_e( 'Upgrade Now', 'bosa-elementor-for-woocommerce' ); ?>
		</a>
	</div>
</div>
