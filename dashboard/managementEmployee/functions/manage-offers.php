<?php
$message=$_GET['message']??''; $type=$message?'success':'';
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['offer_action'])){
 $id=(int)($_POST['offer_id']??0); $action=$_POST['offer_action'];
 if($id>0){
  try{
   if($action==='toggle_status'){$stmt=$pdo->prepare("UPDATE offers SET status=IF(status=1,0,1) WHERE id=?");$stmt->execute([$id]);$message='Offer status updated.';$type='success';}
   elseif($action==='delete'){$stmt=$pdo->prepare("DELETE FROM offers WHERE id=?");$stmt->execute([$id]);$message='Offer deleted.';$type='success';}
  }catch(PDOException $e){$message='Unable to update the offer.';$type='error';}
 }
}
$offers=$pdo->query("SELECT * FROM offers ORDER BY created_at DESC,id DESC")->fetchAll();
?>
<div class="panel-header"><h2>Manage Offers</h2><a class="btn btn-primary" href="?page=offer-form">Create Offer</a></div>
<?php if($message):?><div class="message <?=htmlspecialchars($type)?>"><?=htmlspecialchars($message)?></div><?php endif;?>
<div class="ops-table"><div class="ops-table-heading ops-table-row"><span>Offer</span><span>Discount</span><span>Valid Text</span><span>Status</span><span>Actions</span></div>
<?php foreach($offers as $o):?><div class="ops-table-row"><span><strong><?=htmlspecialchars($o['title'])?></strong><br><small><?=htmlspecialchars($o['offer_type'])?></small></span><span><?=htmlspecialchars($o['discount'])?></span><span><?=htmlspecialchars($o['valid_text'])?></span><span><?= $o['status']?'Active':'Inactive'?></span><span><a class="btn btn-small" href="?page=offer-form&id=<?=$o['id']?>">Edit</a><form method="post" style="display:inline"><input type="hidden" name="offer_id" value="<?=$o['id']?>"><button class="btn btn-small" name="offer_action" value="toggle_status"><?= $o['status']?'Deactivate':'Activate'?></button><button class="btn btn-small" name="offer_action" value="delete" onclick="return confirm('Delete this offer?')">Delete</button></form></span></div><?php endforeach;?></div>