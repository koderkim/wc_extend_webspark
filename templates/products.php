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
.wc_extend_webspark_pagination{
	text-align: center;
}
.wc_extend_webspark_pagination span{
	margin: 0 10px;
}
.wc_extend_webspark_pagination a{
	margin: 0 10px;
}
</style>

<?php
function delete_product_form($product_id){
?>
<form action="" method="POST">
	<input type="hidden" name="_action" value="wc_extend_webspark_delete_product">
	<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wc_extend_webspark') ?>">
	<input type="hidden" name="_product_id" value="<?php echo $product_id; ?>">
	<button type="submit"><?php _e('Delete product', 'wc_extend_webspark'); ?></button>
</form>
<?php
}

do_action('wc_extend_webspark_notice');

$page = $_GET['nav'] ?? 1;
$args = array(
    'limit' => 3,
    'page' => $page,
    'paginate' => true,
    'status' => array('draft', 'pending', 'private', 'publish')
);
$_products = wc_get_products($args);
if(!empty($_products)){
?>
	<table border="1">
		<thead>
			<tr>
				<td>Name</td>
				<td>Quntity</td>
				<td>Price</td>
				<td>Status</td>
				<td>Action</td>
			</tr>
		</thead>
		<tbody>
<?php
	foreach($_products->products as $_product){
		echo '<tr>';
		echo '<td>'.$_product->get_name().'</td>';
		echo '<td>'.$_product->get_stock_quantity().'</td>';
		echo '<td>'.$_product->get_price().'</td>';
		echo '<td>'.$_product->get_status().'</td>';
		echo '<td>';
		echo '<a href="'.site_url('/my-account/add-product/?id=' . $_product->get_ID()).'">Edit</a>';
		delete_product_form($_product->get_ID());
		echo '</td>';
	}
?>
		</tbody>
	</table>
	<?php if($_products->max_num_pages > 1){ ?>
	<nav class="wc_extend_webspark_pagination">
		<?php for($i=1; $i<=$_products->max_num_pages; $i++): ?>
		<?php if($i == $page): ?>
		<span><?php echo $i; ?></span>
		<?php else: ?>
			<?php $link = site_url('/my-account/product-list/'); ?>
			<?php if($i > 1) $link .= '?nav=' . $i; ?>
		<a href="<?php echo $link; ?>"><?php echo $i; ?></a>
		<?php endif; ?>
		<?php endfor; ?>
	</nav>
	<?php } ?>
<?php
}
?>