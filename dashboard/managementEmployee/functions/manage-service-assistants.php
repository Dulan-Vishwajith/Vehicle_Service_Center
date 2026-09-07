<?php
$message=''; $type='';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['assistant_action'])) {
    $action=$_POST['assistant_action']; $id=(int)($_POST['assistant_id']??0);
    if($action==='save'){
        $name=trim($_POST['name']??''); $email=trim($_POST['email']??''); $phone=trim($_POST['phone']??''); $password=$_POST['password']??'';
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||$phone===''){$message='Enter valid name, email and phone.';$type='error';}
        else{
            try{
                $stmt=$pdo->prepare("SELECT role_id FROM roles WHERE role_name='Service Assistant' LIMIT 1");$stmt->execute();$roleId=(int)$stmt->fetchColumn();
                if(!$roleId) throw new RuntimeException('Service Assistant role is missing.');
                if($id>0){
                    if($password!==''){$stmt=$pdo->prepare("UPDATE users SET name=?,email=?,phone=?,password=? WHERE user_id=? AND role_id=?");$stmt->execute([$name,$email,$phone,password_hash($password,PASSWORD_DEFAULT),$id,$roleId]);}
                    else {$stmt=$pdo->prepare("UPDATE users SET name=?,email=?,phone=? WHERE user_id=? AND role_id=?");$stmt->execute([$name,$email,$phone,$id,$roleId]);}
                    $message='Assistant updated.';$type='success';
                }else{
                    if(strlen($password)<6) throw new RuntimeException('Password must be at least 6 characters.');
                    $stmt=$pdo->prepare("INSERT INTO users (name,email,password,phone,role_id) VALUES (?,?,?,?,?)");$stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$phone,$roleId]);
                    $message='Assistant account created.';$type='success';
                }
            }catch(Throwable $e){$message=$e->getMessage();$type='error';}
        }
    } elseif($action==='delete'&&$id>0){
        $stmt=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE assigned_assistant_id=?");$stmt->execute([$id]);
        if((int)$stmt->fetchColumn()>0){$message='Cannot delete an assistant with booking assignments.';$type='error';}
        else{$stmt=$pdo->prepare("DELETE FROM users WHERE user_id=? AND role_id=2");$stmt->execute([$id]);$message='Assistant deleted.';$type='success';}
    }
}
$editId=(int)($_GET['edit']??0); $edit=null;
if($editId){$stmt=$pdo->prepare("SELECT user_id,name,email,phone FROM users WHERE user_id=? AND role_id=2");$stmt->execute([$editId]);$edit=$stmt->fetch();}
$stmt=$pdo->query("SELECT u.user_id,u.name,u.email,u.phone,COUNT(b.id) AS current_assignments FROM users u LEFT JOIN bookings b ON b.assigned_assistant_id=u.user_id AND b.status IN ('pending','booked','confirmed','service') WHERE u.role_id=2 GROUP BY u.user_id ORDER BY u.name");
$assistants=$stmt->fetchAll();
?>
<div class="panel-header"><h2>Manage Service Assistants</h2></div>
<?php if($message):?><div class="message <?=htmlspecialchars($type)?>"><?=htmlspecialchars($message)?></div><?php endif;?>
<form method="post" class="management-form">
<input type="hidden" name="assistant_action" value="save"><input type="hidden" name="assistant_id" value="<?= (int)($edit['user_id']??0) ?>">
<label>Full Name<input name="name" required value="<?=htmlspecialchars($edit['name']??'')?>"></label>
<label>Email<input type="email" name="email" required value="<?=htmlspecialchars($edit['email']??'')?>"></label>
<label>Phone<input name="phone" required value="<?=htmlspecialchars($edit['phone']??'')?>"></label>
<label>Password <?= $edit?'(leave empty to keep current password)':'*' ?><input type="password" name="password" <?= $edit?'':'required' ?>></label>
<div class="form-actions"><button class="btn btn-primary"><?= $edit?'Update Assistant':'Create Assistant' ?></button><?php if($edit):?><a class="btn" href="?page=assistants">Cancel</a><?php endif;?></div>
</form>
<div class="ops-table"><div class="ops-table-heading ops-table-row"><span>Name</span><span>Email</span><span>Phone</span><span>Current Assignments</span><span>Actions</span></div>
<?php foreach($assistants as $a):?><div class="ops-table-row"><span><?=htmlspecialchars($a['name'])?></span><span><?=htmlspecialchars($a['email'])?></span><span><?=htmlspecialchars($a['phone'])?></span><span><?= (int)$a['current_assignments']?></span><span><a class="btn btn-small" href="?page=assistants&edit=<?=$a['user_id']?>">Edit</a><form method="post" style="display:inline"><input type="hidden" name="assistant_action" value="delete"><input type="hidden" name="assistant_id" value="<?=$a['user_id']?>"><button class="btn btn-small" onclick="return confirm('Delete this assistant?')">Delete</button></form></span></div><?php endforeach;?></div>