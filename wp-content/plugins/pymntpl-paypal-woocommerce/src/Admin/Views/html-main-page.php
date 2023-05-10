<?php

/**
 * @var \PaymentPlugins\WooCommerce\PPCP\Assets\AssetsApi $assets
 */
$user      = wp_get_current_user();
//$signed_up = get_option( 'wc_ppcp_admin_signup', false );
?>
<div class="wc-ppcp-main__page">
    <div class="wc-ppcp-main__container">
		<?php include __DIR__ . '/html-main-navigation.php' ?>
		<?php /*if ( ! $signed_up ): */?><!--
            <div class="wc-ppcp-signup-container">
                <h3><?php /*esc_html_e( 'Want to get started more quickly?', 'pymntpl-paypal-woocommerce' ) */?></h3>
                <div class="wc-ppcp-signup__section">
                    <div>
                        <p>
							<?php /*esc_html_e( 'We have a ton of great documentation that you can reference by following the link below. But if you\'re like me you rarely want to read an entire website to figure out how to get started quickly. Want our quick start guide instead?',
								'pymntpl-paypal-woocommerce' ) */?>
                        <p><?php /*esc_html_e( 'Fill out the form and we\'ll send it right away.', 'pymntpl-paypal-woocommerce' ) */?></p>
                        </p>
                    </div>
                </div>
                <div class="wc-ppcp-signup__section signup-form">
                    <form>
						<?php /*echo wp_nonce_field( 'wp_rest' ) */?>
                        <div class="entry-row">
                            <input type="text" name="firstname" placeholder="<?php /*esc_html_e( 'First name', 'pymntpl-paypal-woocommerce' ) */?>" value="<?php /*echo $user->get( 'first_name' ) */?>"/>
                        </div>
                        <div class="entry-row">
                            <input type="text" name="email" placeholder="<?php /*esc_html_e( 'Email', 'pymntpl-paypal-woocommerce' ) */?>" value="<?php /*echo get_option( 'admin_email', $user->get( 'email' ) ) */?>"/>
                        </div class="entry-row">
                        <div class="entry-row">
                            <button id="wc-ppcp-signup" class="primary-button"><?php /*esc_html_e( 'Send Me Your Quick Start Guide', 'pymntpl-paypal-woocommerce' ) */?></button>
                        </div>
                    </form>
                </div>
            </div>
		--><?php /*endif; */?>
        <div class="wc-ppcp-welcome__content">
            <div class="wc-ppcp-main__row cards-container">
                <div class="wc-ppcp-main__card">
                    <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=ppcp_api' ) ?>">
                        <div class="wc-ppcp-main-card__content">
                            <h3><?php esc_html_e( 'Settings', 'pymntpl-paypal-woocommerce' ) ?></h3>
                            <div class="icon-container">
                                <!--<span class="dashicons dashicons-admin-generic"></span>-->
                                <img class="icon" src="<?php echo $assets->assets_url( 'assets/img/settings.svg' ) ?>"/>
                            </div>
                            <div class="card-header">
                                <p><?php esc_html_e( 'Connect your PayPal account, enable payment methods, and customize the plugin settings to fit your business needs.', 'pymntpl-paypal-woocommerce' ) ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="wc-ppcp-main__card">
                    <a target="_blank" href="https://docs.paymentplugins.com/wc-paypal/config">
                        <div class="wc-ppcp-main-card__content">
                            <h3><?php esc_html_e( 'Documentation', 'pymntpl-paypal-woocommerce' ) ?></h3>
                            <div class="icon-container documentation">
                                <!--<span class="dashicons dashicons-admin-users"></span>-->
                                <img class="icon" src="<?php echo $assets->assets_url( 'assets/img/documentation.svg' ) ?>"/>
                            </div>
                            <div class="card-header">
                                <p>
									<?php esc_html_e( 'Want in depth documentation?', 'pymntpl-paypal-woocommerce' ) ?>
                                    <br/>
									<?php esc_html_e( 'Our config guide and API docs are a great place to start.', 'pymntpl-paypal-woocommerce' ) ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="wc-ppcp-main__card">
                    <a href="<?php echo admin_url( 'admin.php?page=wc-ppcp-main&section=support' ) ?>">
                        <div class="wc-ppcp-main-card__content">
                            <h3><?php esc_html_e( 'Support', 'pymntpl-paypal-woocommerce' ) ?></h3>
                            <div class="icon-container support">
                                <!--<span class="dashicons dashicons-admin-users"></span>-->
                                <img class="icon" src="<?php echo $assets->assets_url( 'assets/img/support.svg' ) ?>"/>
                            </div>
                            <div class="card-header">
                                <p><?php esc_html_e( 'Have a question?', 'pymntpl-paypal-woocommerce' ) ?>
                                    <br/>
									<?php esc_html_e( 'Our support team is ready to assist you.', 'pymntpl-paypal-woocommerce' ) ?>
                                </p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
