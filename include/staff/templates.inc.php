<?php
if(!defined('OSTADMININC') || !$thisstaff->isAdmin()) die('Access Denied');

$qs = array();
$sql='SELECT tpl.*,count(dept.tpl_id) as depts '.
     'FROM '.EMAIL_TEMPLATE_GRP_TABLE.' tpl '.
     'LEFT JOIN '.DEPT_TABLE.' dept USING(tpl_id) '.
     'WHERE 1 ';
$sortOptions=array('name'=>'tpl.name','status'=>'tpl.isactive','created'=>'tpl.created','updated'=>'tpl.updated');
$orderWays=array('DESC'=>'DESC','ASC'=>'ASC');
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'name';
//Sorting options...
if($sort && $sortOptions[$sort]) {
    $order_column =$sortOptions[$sort];
}
$order_column=$order_column?$order_column:'tpl.name';

if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])]) {
    $order=$orderWays[strtoupper($_REQUEST['order'])];
}
$order=$order?$order:'ASC';

if($order_column && strpos($order_column,',')){
    $order_column=str_replace(','," $order,",$order_column);
}
$x=$sort.'_sort';
$$x=' class="'.strtolower($order).'" ';
$order_by="$order_column $order ";

$total=db_count('SELECT count(*) FROM '.EMAIL_TEMPLATE_GRP_TABLE.' tpl ');
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('templates.php', $qs);
$qstr .= '&amp;order='.($order=='DESC' ? 'ASC' : 'DESC');
$query="$sql GROUP BY tpl.tpl_id ORDER BY $order_by LIMIT ".$pageNav->getStart().",".$pageNav->getLimit();
$res=db_query($query);
if($res && ($num=db_num_rows($res)))
    $showing=$pageNav->showing().' '._N('template', 'templates', $num);
else
    $showing=__('No templates found!');

?>

<div class="pull-left" style="width:700px;padding-top:5px;">
 <h2><?php echo __('Email Template Sets'); ?></h2>
</div>
<div class="pull-right flush-right" style="padding-top:5px;padding-right:5px;">
 <b><a href="templates.php?a=add" class="Icon newEmailTemplate"><?php echo __('Add New Template Set'); ?></a></b></div>
<div class="clear"></div>
<form action="templates.php" method="POST" name="tpls">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="mass_process" >
<input type="hidden" id="action" name="a" value="" >
 <table class="list" border="0" cellspacing="1" cellpadding="0" width="940">
    <caption><?php echo $showing; ?></caption>
    <thead>
        <tr>
            <th width="7">&nbsp;</th>
            <th width="350"><a <?php echo $name_sort; ?> href="templates.php?<?php echo $qstr; ?>&sort=name"><?php echo __('Name'); ?></a></th>
            <th width="100"><a  <?php echo $status_sort; ?> href="templates.php?<?php echo $qstr; ?>&sort=status"><?php echo __('Status'); ?></a></th>
            <th width="80"><a <?php echo $inuse_sort; ?> href="templates.php?<?php echo $qstr; ?>&sort=inuse"><?php echo __('In-Use'); ?></a></th>
            <th width="120" nowrap><a  <?php echo $created_sort; ?>href="templates.php?<?php echo $qstr; ?>&sort=created"><?php echo __('Date Added'); ?></a></th>
            <th width="150" nowrap><a  <?php echo $updated_sort; ?>href="templates.php?<?php echo $qstr; ?>&sort=updated"><?php echo __('Last Updated'); ?></a></th>
        </tr>
    </thead>
    <tbody>
    <?php
        $total=0;
        $ids=($errors && is_array($_POST['ids']))?$_POST['ids']:null;
        if($res && db_num_rows($res)):
            $defaultTplId=$cfg->getDefaultTemplateId();
            while ($row = db_fetch_array($res)) {
                $inuse=($row['depts'] || $row['tpl_id']==$defaultTplId);
                $sel=false;
                if($ids && in_array($row['tpl_id'],$ids))
                    $sel=true;

                $default=($defaultTplId==$row['tpl_id'])?'<small class="fadded">('.__('System Default').')</small>':'';
                ?>
            <tr id="<?php echo $row['tpl_id']; ?>">
                <td width=7px>
                  <input type="checkbox" class="ckb" name="ids[]" value="<?php echo $row['tpl_id']; ?>"
                            <?php echo $sel?'checked="checked"':''; ?> <?php echo $default?'disabled="disabled"':''; ?> >
                </td>
                <td>&nbsp;<a href="templates.php?tpl_id=<?php echo $row['tpl_id']; ?>"><?php echo Format::htmlchars($row['name']); ?></a>
                &nbsp;<?php echo $default; ?></td>
                <td>&nbsp;<?php echo $row['isactive']?__('Active'):'<b>'.__('Disabled').'</b>'; ?></td>
                <td>&nbsp;&nbsp;<?php echo ($inuse)?'<b>'.__('Yes').'</b>':__('No'); ?></td>
                <td>&nbsp;<?php echo Format::db_date($row['created']); ?></td>
                <td>&nbsp;<?php echo Format::db_datetime($row['updated']); ?></td>
            </tr>
            <?php
            } //end of while.
        endif; ?>
    <tfoot>
     <tr>
        <td colspan="6">
            <?php if($res && $num){ ?>
            <?php echo __('Select');?>:&nbsp;
            <a id="selectAll" href="#ckb"><?php echo __('All');?></a>&nbsp;&nbsp;
            <a id="selectNone" href="#ckb"><?php echo __('None');?></a>&nbsp;&nbsp;
            <a id="selectToggle" href="#ckb"><?php echo __('Toggle');?></a>&nbsp;&nbsp;
            <?php }else{
                echo __('No templates found!');
            } ?>
        </td>
     </tr>
    </tfoot>
</table>
<?php
if($res && $num): //Show options..
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
?>
<p class="centered" id="actions">
    <input class="button" type="submit" name="enable" value="<?php echo __('Enable');?>" >
    <input class="button" type="submit" name="disable" value="<?php echo __('Disable');?>" >
    <input class="button" type="submit" name="delete" value="<?php echo __('Delete');?>" >
</p>
<?php
endif;
?>
</form>

<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm');?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" style="display:none;" id="enable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>enable</b> %s?'),
            _N('selected template set', 'selected template sets', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="disable-confirm">
        <?php echo sprintf(__('Are you sure you want to <b>disable</b> %s?'),
            _N('selected template set', 'selected template sets', 2));?>
    </p>
    <p class="confirm-action" style="display:none;" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(__('Are you sure you want to DELETE %s?'),
            _N('selected template set', 'selected template sets', 2));?></strong></font>
        <br><br><?php echo __('Deleted data CANNOT be recovered.'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.');?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel');?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!');?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
