<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
<div class="Info">
    <?php echo anchor(t('Groups'), '/settings/mygroups', array('class' => 'Button')); ?>
    <?php echo anchor(t('Group Requests') . ($this->data('groupRequestCount') ? ' ' . wrap($this->data('groupRequestCount', 0), 'span class="Alert"') : ''), '/settings/mygroups/requests', array('class' => 'Button Active')); ?>
    <?php echo anchor(t('Add Group'), '/settings/mygroups/add', array('class' => 'Button')); ?>
</div>
<div class="Listings GroupListings">
<table>
    <tr>
        <th><?php echo t('Image'); ?></th>
        <th><?php echo t('Name'); ?></th>
        <th><?php echo t('Description'); ?></th>
        <th><?php echo t('Edit'); ?></th>
        <th><?php echo t('Delete'); ?></th>
        <th><?php echo t('Approve'); ?></th>
    </tr>
    <?php
    foreach($this->data('groups') As $group){
    ?>
    <tr>
        <td><?php echo img('/uploads/mygroups/'.$group->Picture, array('width' => 40)); ?></td>
        <td><?php echo Gdn_Format::text($group->Name); ?></td>
        <td><?php echo Gdn_Format::html($group->Description); ?></td>
        <td><?php echo anchor(t('Edit'), '/settings/mygroups/edit/' . intval($group->MyGroupID), array('class' => 'DeleteGroup Button SmallButton')); ?></td>
        <td><?php echo anchor(t('Delete'), '/settings/mygroups/delete/' . intval($group->MyGroupID), array('class' => 'DeleteGroup Button SmallButton')); ?></td>
        <td><?php echo anchor(t('Approve'), '/settings/mygroups/approve/' . intval($group->MyGroupID), array('class' => 'DeleteGroup Button SmallButton')); ?></td>
    </tr>
    <?php
    }
    ?>
</table>
<?php
    echo $this->Pager->render();
