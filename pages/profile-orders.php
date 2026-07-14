<?php
require_once dirname(__DIR__) . '/includes/functions.php';
require_login('/pages/login.php?redirect=/pages/profile-orders.php');
$user_id = auth_user()['id'];
$page_title = 'My Orders';
$page_num = max(1,(int)get_param('page',1));
$per_page = 10;
$sf = get_param('status');
$where = ['o.user_id=?']; $params = [$user_id];
if($sf){$where[]='o.status=?';$params[]=$sf;}
$ws = implode(' AND ',$where);
$total = (int)DB::val("SELECT COUNT(*) FROM orders o WHERE $ws",$params);
$pager = paginate($total,$per_page,$page_num);
$orders = DB::all("SELECT * FROM orders o WHERE $ws ORDER BY o.created_at DESC LIMIT $per_page OFFSET {$pager['offset']}",$params);
require_once dirname(__DIR__) . '/includes/header.php';
?>
<div class="profile-page"><div class="container"><div class="profile-layout">
<?php include dirname(__DIR__) . '/includes/profile-nav.php'; ?>
<main class="profile-main">
<?= flash_html('order') ?>
<div class="profile-card">
  <div class="profile-card-header"><h2><i class="fas fa-shopping-bag"></i> My Orders</h2></div>
  <div class="admin-status-tabs" style="margin-bottom:20px">
    <a href="?" class="<?= !$sf?'active':'' ?>">All</a>
    <?php foreach(['pending','paid','processing','shipped','delivered','cancelled'] as $s): ?>
    <a href="?status=<?=$s?>" class="<?=$sf===$s?'active':''?>"><?=ucfirst($s)?></a>
    <?php endforeach; ?>
  </div>
  <?php if(empty($orders)): ?>
  <div class="empty-state small"><i class="fas fa-box-open"></i><h3>No orders found</h3><a href="<?=BASE_URL?>/shop.php" class="btn btn-primary">Start Shopping</a></div>
  <?php else: ?>
  <div class="table-wrap"><table class="data-table">
    <thead><tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach($orders as $o): ?>
    <tr>
      <td><strong><?=clean($o['order_number'])?></strong></td>
      <td><?=date('M j, Y',strtotime($o['created_at']))?></td>
      <td><?=(int)DB::val("SELECT SUM(qty) FROM order_items WHERE order_id=?",[$o['id']])?> item(s)</td>
      <td><strong><?=money($o['total'])?></strong></td>
      <td><span class="status-pill status-<?=$o['payment_status']?>"><?=ucfirst($o['payment_status'])?></span></td>
      <td><span class="status-pill status-<?=$o['status']?>"><?=ucfirst($o['status'])?></span></td>
      <td><a href="<?=BASE_URL?>/pages/track-order.php?order=<?=clean($o['order_number'])?>" class="btn btn-outline btn-xs"><i class="fas fa-truck"></i> Track</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if($pager['total_pages']>1): ?>
  <nav class="pagination">
    <?php for($i=1;$i<=$pager['total_pages'];$i++): ?>
    <a href="?page=<?=$i?><?=$sf?'&status='.$sf:''?>" class="page-btn <?=$i===$pager['current']?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</div>
</main></div></div></div>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
