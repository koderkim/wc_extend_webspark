<?php
/*
 * Plugin Name: WooCommerce Extend Webspark
 * Requires Plugins: woocommerce
 * Description: WooCommerce Extend Webspark
 * Author: koder-kim
 * Author URI: http://koder.pp.ua
 * Version: 1.0.0
 */

class WC_EXTEND_WEBSPARK{
	protected static $_instance = null;

	public static function instance(){
		if(is_null(self::$_instance)){
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct(){
		add_action('init', array($this, 'wc_extend_webspark_load'), 9);
	}

	public function wc_extend_webspark_load(){
		if(!class_exists('WooCommerce')){
			add_action('admin_notices', array($this, 'need_woocommerce'));
			return;
		}

		add_action('init', 'wc_extend_webspark_endpoints');
		function wc_extend_webspark_endpoints() {
			add_rewrite_endpoint('add-product', EP_ROOT | EP_PAGES);
			add_rewrite_endpoint('product-list', EP_ROOT | EP_PAGES);
		}

		add_filter('query_vars', 'wc_extend_webspark_query_vars', 0);
		function wc_extend_webspark_query_vars($vars){
			$vars[] = 'add-product';
			$vars[] = 'product-list';

			return $vars;
		}

		function wc_extend_webspark_flush_rewrite_rules(){
			flush_rewrite_rules();
		}
		add_action('after_switch_theme', 'wc_extend_webspark_flush_rewrite_rules');

		add_filter('woocommerce_account_menu_items', 'wc_extend_webspark_account_menu_items');
		function wc_extend_webspark_account_menu_items($items){
			$items['add-product'] = __('Add product', 'wc_extend_webspark');
			$items['product-list'] = __('My products', 'wc_extend_webspark');
			return $items;
		}

		add_action('woocommerce_account_add-product_endpoint', 'wc_extend_webspark_account_add_product_endpoint');
		function wc_extend_webspark_account_add_product_endpoint(){
			include plugin_dir_path(__FILE__) . 'templates/add-product.php'; 
		}

		add_action('woocommerce_account_product-list_endpoint', 'wc_extend_webspark_account_product_list_endpoint');
		function wc_extend_webspark_account_product_list_endpoint(){
			include plugin_dir_path(__FILE__) . 'templates/products.php'; 
		}

		add_action('wc_extend_webspark_notice', function(){
			if($notice = get_transient('wc_extend_webspark_notice')){
				echo $notice;
				delete_transient('wc_extend_webspark_notice');
			} else {
				echo '<div class="wc_extend_webspark_notice"></div>';
			}
		});

		add_action('template_redirect', 'wc_extend_webspark_save_product_request');
		function wc_extend_webspark_save_product_request(){
			if(isset($_REQUEST['_action']) && isset($_REQUEST['_wpnonce']) && wp_verify_nonce($_REQUEST['_wpnonce'], 'wc_extend_webspark')){
				if('wc_extend_webspark_delete_product' == $_REQUEST['_action']){
					$product = new WC_Product_Simple($_REQUEST['_product_id']);
					$product->delete();

					$notice = '<div class="wc_extend_webspark-notice notice-success"><p>';
					$notice .= __('The product has been deleted', 'wc_extend_webspark');
					$notice .= '</p></div>';
					set_transient('wc_extend_webspark_notice', $notice, 60);

					$redirect_url = site_url('/my-account/product-list/');
					wp_safe_redirect($redirect_url);
					exit();
				}
				if('wc_extend_webspark_save_product' == $_REQUEST['_action']){
					$image_id = null;
					if($_FILES && $_FILES['product_image']){
						require_once ABSPATH.'wp-admin/includes/admin.php';
						$file_return = wp_handle_upload($_FILES['product_image'], array('test_form' => false));
						if(!isset($file_return['error']) && !isset($file_return['upload_error_handler'])){
							$filename = $file_return['file'];
							$attachment = array(
								'post_mime_type' => $file_return['type'],
								'post_content' => '',
								'post_type' => 'attachment',
								'post_status' => 'inherit',
								'guid' => $file_return['url']
							);

							if($_REQUEST['product_name']){
								$attachment['post_title'] = $_REQUEST['product_name'];
							}

							$attachment_id = wp_insert_attachment($attachment, $filename);
							require_once(ABSPATH . 'wp-admin/includes/image.php');
							$attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);
							wp_update_attachment_metadata($attachment_id, $attachment_data);

							if(0 < intval($attachment_id)){
								$image_id = $attachment_id;
							}
						}
					}

					if(isset($_REQUEST['_product_id']) && $_REQUEST['_product_id'] > 0){
						$product = new WC_Product_Simple($_REQUEST['_product_id']);
					} else {
						$product = new WC_Product_Simple();
					}
					$product->set_name($_REQUEST['product_name']);
					$product->set_regular_price($_REQUEST['product_price']);
					$product->set_stock_quantity($_REQUEST['product_quantity']);
					$product->set_description($_REQUEST['product_description']);
					if($image_id){
						$product->set_image_id($image_id);
					}
					$product->set_status('pending');
					$product->save();

					$notice = '<div class="wc_extend_webspark-notice notice-success"><p>';
					$notice .= __('The product has been successfully added', 'wc_extend_webspark');
					$notice .= '</p></div>';
					set_transient('wc_extend_webspark_notice', $notice, 60);
					
					$redirect_url = site_url('/my-account/add-product/');
					if(isset($_REQUEST['_product_id']) && $_REQUEST['_product_id'] > 0){
						$redirect_url .= '?id=' . $_REQUEST['_product_id'];
					}
					wp_safe_redirect($redirect_url);
					exit();
				}
			}
		}

		/*
		add_action('wp_ajax_wc_extend_webspark_save_product', 'wc_extend_webspark_save_product');
		add_action('wp_ajax_nopriv_wc_extend_webspark_save_product', 'wc_extend_webspark_save_product');
		function wc_extend_webspark_save_product(){
			if(!wp_verify_nonce($_POST['wpnonce'], 'wc_extend_webspark')){
				wp_send_json_error(array('message' => 'Forbidden'));
				wp_die();
			}

			$image_id = null;
			if($_FILES && $_FILES['product_image']){
				require_once ABSPATH.'wp-admin/includes/admin.php';
				$file_return = wp_handle_upload($_FILES['product_image'], array('test_form' => false));
				if(!isset($file_return['error']) && !isset($file_return['upload_error_handler'])){
					$filename = $file_return['file'];
					$attachment = array(
						'post_mime_type' => $file_return['type'],
						'post_content' => '',
						'post_type' => 'attachment',
						'post_status' => 'inherit',
						'guid' => $file_return['url']
					);

					if($_POST['product_name']){
						$attachment['post_title'] = $_POST['product_name'];
					}

					$attachment_id = wp_insert_attachment($attachment, $filename);
					require_once(ABSPATH . 'wp-admin/includes/image.php');
					$attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);
					wp_update_attachment_metadata($attachment_id, $attachment_data);

					if(0 < intval($attachment_id)){
						$image_id = $attachment_id;
					}
				}
			}

			$product = new WC_Product_Simple();
			$product->set_name($_POST['product_name']);
			$product->set_regular_price($_POST['product_price']);
			$product->set_stock_quantity($_POST['product_quantity']);
			$product->set_description($_POST['product_description']);
			if($image_id){
				$product->set_image_id($image_id);
			}
			$product->set_status('pending');
			$product->save();

			$message = '<div class="wc_extend_webspark-notice notice-success"><p>';
			$message .= __('The product has been successfully added', 'wc_extend_webspark');
			$message .= '</p></div>';

			wp_send_json_success(array('message' => $message));
			wp_die();
		}

		add_action('wp_footer', function(){
?>
<script>
	function wcewAjax(url, data, callback, beforeSend = null){
		if(beforeSend){
			beforeSend();
		}
		fetch(url, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				// 'Content-Type': 'application/json',
			},
			body: new URLSearchParams(
				data
			)
		})
		.then((response)=>response.json())
		.then((json)=>{
			if(json.success){
				callback(json);
			}
		})
		.catch((error)=>{
			console.error('Error:', error);
		});
	}
	function wcewSubmit(form){
		var data = {};
		data.action = form.querySelector('input[name="_action"]').value;
		data.wpnonce = form.querySelector('input[name="_wpnonce"]').value;
		data.product_name = form.querySelector('input[name="product_name"]').value;
		data.product_price = form.querySelector('input[name="product_price"]').value;
		data.product_quantity = form.querySelector('input[name="product_quantity"]').value;
		data.product_description = form.querySelector('textarea[name="product_description"]').value;
		
		wcewAjax(
			'<?php echo admin_url('admin-ajax.php'); ?>',
			data,
			function(resp){
				document.querySelector('.wc_extend_webspark_notice').innerHTML = resp.data.message;
				form.reset();
			},
			function(){
				document.querySelector('.wc_extend_webspark_notice').innerHTML = '';
			}
		);
	}
</script>
<?php
		});
		*/
	} // ********* wc_extend_webspark_load

	public function need_woocommerce(){
		$error_message = sprintf(
			esc_html__('WooCommerce Extend Webspark requires %1$sWooCommerce%2$s to be installed & activated!' , 'wc_extend_webspark'),
			'<a href="http://wordpress.org/extend/plugins/woocommerce/">',
			'</a>'
		);

		$message  = '<div class="error">';
		$message .= sprintf('<p>%s</p>', $error_message);
		$message .= '</div>';

		echo wp_kses_post($message);
	}
}

return WC_EXTEND_WEBSPARK::instance();