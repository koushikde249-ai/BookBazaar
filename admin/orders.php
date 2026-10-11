<?php
require_once __DIR__ . '/../config/database.php'; require_once __DIR__ . '/../includes/functions.php'; require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $id=filter_input(INPUT_POST,'order_id',FILTER_VALIDATE_INT); $action=(string)($_POST['action']??'');
 if($id && in_array($action,['confirm','complete','cancel'],true)){
  $pdo->beginTransaction();
  try{
   $q=$pdo->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$q->execute([$id]);$o=$q->fetch();
   if($o){
    if($action==='cancel' && $o['order_status']!=='completed'){$pdo->prepare("UPDATE orders SET order_status='cancelled' WHERE id=?")->execute([$id]);$pdo->prepare('UPDATE books SET is_sold=0 WHERE id=?')->execute([(int)$o['book_id']]);}
    elseif($action==='confirm' && $o['order_status']==='placed'){$pdo->prepare("UPDATE orders SET order_status='confirmed' WHERE id=?")->execute([$id]);}
    elseif($action==='complete' && $o['payment_status']==='paid' && $o['order_status']!=='cancelled'){$pdo->prepare("UPDATE orders SET order_status='completed' WHERE id=?")->execute([$id]);}
   }
   $pdo->commit(); flash('Order updated.');
  }catch(Throwable $e){$pdo->rollBack();flash('Could not update order.','error');}
 }
 header('Location: orders.php');exit;
}
$orders=$pdo->query("SELECT o.*,b.name AS buyer_account_name,s.name AS seller_name FROM orders o JOIN users b ON b.id=o.buyer_id JOIN users s ON s.id=o.seller_id ORDER BY o.created_at DESC LIMIT 200")->fetchAll();
$pageTitle='Manage Orders';require __DIR__.'/../includes/header.php';
?>
<section class="container order-page"><div class="section-heading"><div><span class="eyebrow">Administration</span><h2>Manage Orders</h2><p>Review orders and update status. Online payment status is only updated after signature verification.</p></div></div><?php if(!$orders): ?><div class="empty-state"><h3>No orders yet</h3></div><?php else: ?><div class="order-list"><?php foreach($orders as $o): ?><article class="order-card"><div class="order-top"><div><span class="eyebrow">Order #<?= (int)$o['id'] ?></span><h3><?= e($o['book_title']) ?></h3><p><?= e($o['created_at']) ?></p></div><strong class="price">₹<?= number_format((float)$o['amount'],2) ?></strong></div><div class="order-badges"><span class="status status-pending">Order: <?= e(ucfirst($o['order_status'])) ?></span><span class="status <?= $o['payment_status']==='paid'?'status-approved':($o['payment_status']==='failed'?'status-rejected':'status-pending') ?>">Payment: <?= e(ucfirst($o['payment_status'])) ?></span></div><p><strong>Buyer:</strong> <?= e($o['buyer_name']) ?> · <?= e($o['buyer_phone']) ?> · <?= e($o['buyer_email']) ?></p><p><strong>Seller:</strong> <?= e($o['seller_name']) ?> · <strong>Method:</strong> <?= e(strtoupper($o['payment_method'])) ?></p><div class="admin-actions"><a class="button button-small button-light" href="../order.php?id=<?= (int)$o['id'] ?>">Details</a><?php if($o['order_status']==='placed'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="action" value="confirm"><button class="button button-small" type="submit">Confirm</button></form><?php endif; ?><?php if($o['payment_status']==='paid' && $o['order_status']!=='completed' && $o['order_status']!=='cancelled'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="action" value="complete"><button class="button button-small" type="submit">Complete</button></form><?php endif; ?><?php if($o['order_status']!=='completed' && $o['order_status']!=='cancelled'): ?><form method="post" onsubmit="return confirm('Cancel this order and make the book available again?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><input type="hidden" name="action" value="cancel"><button class="button button-small button-light" type="submit">Cancel</button></form><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?></section>
<?php require __DIR__.'/../includes/footer.php'; ?>
