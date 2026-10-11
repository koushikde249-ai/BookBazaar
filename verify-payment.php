<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/payment.php';
require_login();
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: my-orders.php'); exit; }
verify_csrf();
$orderId=filter_input(INPUT_POST,'order_id',FILTER_VALIDATE_INT);
$rzOrder=trim((string)($_POST['razorpay_order_id']??'')); $rzPayment=trim((string)($_POST['razorpay_payment_id']??'')); $signature=trim((string)($_POST['razorpay_signature']??''));
$stmt=$pdo->prepare('SELECT * FROM orders WHERE id=? AND buyer_id=? LIMIT 1'); $stmt->execute([$orderId ?: 0,(int)current_user()['id']]); $order=$stmt->fetch();
if (!$order || $order['payment_method']!=='razorpay' || !$order['razorpay_order_id'] || !hash_equals((string)$order['razorpay_order_id'],$rzOrder) || $rzPayment==='' || $signature==='') { http_response_code(400); exit('Invalid payment response.'); }
$expected=hash_hmac('sha256',$rzOrder.'|'.$rzPayment,RAZORPAY_KEY_SECRET);
if (!hash_equals($expected,$signature)) { $pdo->prepare("UPDATE orders SET payment_status='failed' WHERE id=? AND buyer_id=?")->execute([$orderId,(int)current_user()['id']]); flash('Payment signature verification failed. Do not assume payment succeeded. Contact support if money was deducted.','error'); header('Location: order.php?id='.$orderId); exit; }
$pdo->prepare("UPDATE orders SET payment_status='paid',razorpay_payment_id=? WHERE id=? AND buyer_id=?")->execute([$rzPayment,$orderId,(int)current_user()['id']]);
flash('Payment verified successfully. Your order is confirmed.'); header('Location: order.php?id='.$orderId); exit;
