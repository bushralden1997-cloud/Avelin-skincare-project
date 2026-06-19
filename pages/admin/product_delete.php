<?php
require_once '../../includes/config.php';
if(!is_logged_in()||!is_admin())redirect('../login.php');
$pid=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT);if(!$pid)redirect('products.php');
$s=$conn->prepare("SELECT name,image_url FROM products WHERE product_id=?");$s->bind_param("i",$pid);$s->execute();
$p=$s->get_result()->fetch_assoc();if(!$p)redirect('products.php');
$del=$conn->prepare("DELETE FROM products WHERE product_id=?");$del->bind_param("i",$pid);
if($del->execute()){if(!empty($p['image_url'])&&!in_array($p['image_url'],['hydro_boost.jpg','effaclar_duo.jpg'])&&file_exists('../../img/products/'.$p['image_url']))@unlink('../../img/products/'.$p['image_url']);$_SESSION['flash']="\"".htmlspecialchars($p['name'])."\" silindi.";}
redirect('products.php');
