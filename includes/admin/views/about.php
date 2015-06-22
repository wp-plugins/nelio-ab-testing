<?php
// ------------------------------------------------
// FILL THIS VARS FOR AN UPDATE
// ------------------------------------------------

$welcome_message = __( '%s Nelio A/B Testing %s adds support for WooCommerce and looks better than ever before. We hope you enjoy using it.', 'nelioab' );


/** @var text|boolean $minor_update */
$minor_update = false;


$main_update_title = __( 'WooCommerce Support', 'nelioab' );
$main_update_summary = __( 'We\'ve finally added support for WooCommerce! Now, you can easily test the featured image, name, and short description of products, and track the completion of orders as conversions.', 'nelioab' );
$main_update_details = array(
	array(
		'title' => __( 'The First Step', 'nelioab' ),
		'text'  => __( 'Nelio A/B Testing aims to become the perfect tool for split testing your WooCommerce site. This is just the first step in our roadmap, and we plan to increase the number of tests you\'ll be able to run with us.', 'nelioab' )
	),
	array(
		'title' => __( 'Track Product Purchases', 'nelioab' ),
		'text'  => __( 'Regardless of the type of experiment you\'re running, Nelio now offers the opportunity to count as a conversion the fact that a certain product has been purchased. It\'s time to track what really matters!', 'nelioab' )
	),
	array(
		'title' => __( 'Test Product Summaries', 'nelioab' ),
		'text'  => __( 'Nelio includes a new type of experiment called <em>WooCommerce Product Summaries</em>. With it, you can easily change the name, featured image, and/or short description of your products, and detect which one gets you more sales.', 'nelioab' )
	)
);


$secondary_update_details = array(
	array(
		'title' => __( 'New Results Page', 'nelioab' ),
		'text'  => __( 'We\'ve completely redesigned the results page. Now, the results are organized in three different sections: (a) general information about the experiment and its status, (b) details of the alternatives, and (c) a more visual list of conversion actions.', 'nelioab' )
	),
	array(
		'title' => __( 'New UI for Conversion Actions', 'nelioab' ),
		'text'  => __( 'The set of conversion actions are no longer presented using texts. Now, when you define the goals of your experiments, conversion actions use descriptive icons.', 'nelioab' )
	),
	array(
		'title' => __( 'Drag and Drop Conversion Actions', 'nelioab' ),
		'text'  => __( 'Conversion Actions are one of the key pieces of an A/B Testing platform. You can now sort the conversion actions within a goal just by dragging and dropping them.', 'nelioab' )
	)
);


$tweets = array(
	__( 'Are you a WordPress publisher? #Nelio A/B Testing makes it super easy to split test your headlines!', 'nelioab' ),
	__( '#Nelio A/B Testing is the best #abtest service for #WordPress. Check it out (it\'s free to test).', 'nelioab' ),
	__( 'Collecting #heatmaps and #clickmaps in #WordPress, and then running split tests, is easy with Nelio.', 'nelioab' ),
	__( 'Want more income? More subscribers? #Nelio A/B Testing is the best plugin for doing so in #WordPress.', 'nelioab' )
);


?>
<div class="wrap about-wrap">
	<h1><?php printf( __( 'Welcome to Nelio A/B Testing %s', 'nelioab' ), NELIOAB_PLUGIN_VERSION ); ?></h1>

	<div class="about-text nelioab-about-text">
		<?php
			if ( ! empty( $_GET['nelioab-installed'] ) ) {
				$message = __( 'Thanks, all done!', 'nelioab' );
			} elseif ( ! empty( $_GET['nelioab-updated'] ) ) {
				$message = __( 'Thank you for updating to the latest version!', 'nelioab' );
			} else {
				$message = __( 'Thanks for installing!', 'nelioab' );
			}

			printf( $welcome_message, $message, NELIOAB_PLUGIN_VERSION );
		?>
	</div>

	<div class="nelioab-badge"><div class="logo"></div><?php printf( __( 'Version %s', 'nelioab' ), NELIOAB_PLUGIN_VERSION ); ?></div>

	<?php
		// Random tweet - must be kept to 102 chars to "fit"
		shuffle( $tweets );
	?>

	<p class="nelioab-first-actions">
		<a href="https://twitter.com/share" class="twitter-share-button" data-url="https://nelioabtesting.com" data-text="<?php echo esc_attr( $tweets[0] ); ?>" data-via="NelioSoft" data-size="large"><?php _e( 'Tweet', 'nelioab' ); ?></a>
		<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
	</p>

	<h2><?php _e( 'Minor Update Notice', 'nelioab' ); ?></h2>

	<h2><?php _e( 'What\'s New', 'nelioab' ); ?></h2>

	<div class="changelog">
		<h4><?php echo $main_update_title; ?></h4>
		<p><?php echo $main_update_summary; ?></p>

		<div class="changelog about-integrations">
			<div class="nelioab-feature feature-section col three-col">
				<div>
					<h4><?php echo $main_update_details[0]['title']; ?></h4>
					<p><?php echo $main_update_details[0]['text']; ?></p>
				</div>
				<div>
					<h4><?php echo $main_update_details[1]['title']; ?></h4>
					<p><?php echo $main_update_details[1]['text']; ?></p>
				</div>
				<div class="last-feature">
					<h4><?php echo $main_update_details[2]['title']; ?></h4>
					<p><?php echo $main_update_details[2]['text']; ?></p>
				</div>
			</div>
		</div>
	</div>

	<?php
		$size = count( $secondary_update_details );
		if ( $size > 0 ) {
			echo "\n";
			echo '<div class="changelog">' . "\n";

			for ( $i = 0; $i < count( $secondary_update_details ); ++$i ) {
				$is_first_in_block = $i % 3 == 0;
				$is_last_in_block = $i % 3 == 2 || $i == $size - 1;

				if ( $is_first_in_block ) {
					echo '  <div class="feature-section col three-col">' . "\n";
				}

				if ( $is_last_in_block ) {
					echo '    <div class="last-feature">' . "\n";
				} else {
					echo '    <div>' . "\n";
				}

				$details = $secondary_update_details[$i];
				echo '      <h4>' . $details['title'] . '</h4>' . "\n";
				echo '      <p>' . $details['text'] . '</p>' . "\n";

				echo '    </div>' . "\n";

				if ( $is_last_in_block ) {
					echo '  </div>' . "\n";
				}
			}
		}

		echo '</div>' . "\n";
	?>

	<p class="nelioab-last-actions">
		<a href="<?php echo admin_url('admin.php?page=nelioab-dashboard'); ?>" class="button button-primary"><?php _e( 'Dashboard', 'nelioab' ); ?></a>
		<a href="<?php echo esc_url( apply_filters( 'nelioab', 'http://support.nelioabtesting.com/support/home', 'nelioab' ) ); ?>" class="button"><?php _e( 'Docs', 'nelioab' ); ?></a>
	</p>

</div>
