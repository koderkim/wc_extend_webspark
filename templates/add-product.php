<style>
.wc_extend_webspark-notice{
	background: #fff;
	border: 1px solid #c3c4c7;
	border-left-width: 4px;
	box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
	margin: 5px 0 20px;
	padding: 4px 12px;
}
.wc_extend_webspark-notice.notice-success{
	border-left-color: #00a32a;
}
.wc_extend_webspark-notice p{
	margin: 0;
}
</style>

<?php do_action('wc_extend_webspark_notice'); ?>

<?php $_product = isset($_GET['id']) ? wc_get_product($_GET['id']) : null; ?>

<form action="" method="POST" enctype="multipart/form-data">
	<input type="hidden" name="_action" value="wc_extend_webspark_save_product">
	<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wc_extend_webspark') ?>">
	<input type="hidden" name="_product_id" value="<?php echo isset($_product) ? $_product->get_ID() : ''; ?>">
	<?php
		$args = array(
			'type' => 'text',
			'required' => true,
			'label' => __('Product name', 'wc_extend_webspark'),
			'default' => isset($_product) ? $_product->get_name() : ''
		);
		woocommerce_form_field('product_name', $args);

		$args = array(
			'type' => 'text',
			'required' => true,
			'label' => __('Product price', 'wc_extend_webspark'),
			'class' => 'form-row-first',
			'default' => isset($_product) ? $_product->get_price() : ''
		);
		woocommerce_form_field('product_price', $args);

		$args = array(
			'type' => 'number',
			'default' => 1,
			'label' => __('Product quantity', 'wc_extend_webspark'),
			'class' => 'form-row-last',
			'default' => isset($_product) ? $_product->get_stock_quantity() : 1
		);
		woocommerce_form_field('product_quantity', $args);

		echo '<div class="clear"></div>';

		wp_editor(isset($_product) ? $_product->get_description() : '', 'product_description', ['textarea_rows' => 12, 'media_buttons' => false]);
	?>
	<p class="form-row">
		<label for="product_image">Product image</label>
		<span class="woocommerce-input-wrapper">
			<input type="file" name="product_image" id="product_image">
		</span>
	</p>
	<button type="submit" class="woocommerce-Button button"><?php _e('Create product', 'wc_extend_webspark'); ?></button>
	<?php /* ?><button type="button" onclick="wcewSubmit(this.parentElement)" class="woocommerce-Button button"><?php _e('Create product', 'wc_extend_webspark'); ?></button><?php */ ?>
</form>
