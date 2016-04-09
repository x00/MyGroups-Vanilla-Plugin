<?php if (!defined('APPLICATION')) exit();
$members = $this->data('Members');
$slug = Gdn_Format::url($this->data('Group.Name'));
?>

<?php
if ($this->data('Owner')) {
?>
<div class="GroupAddMembers">
<?php
    echo $this->Form->open(array('id' => 'Form_AddMembers'));
    echo Wrap($this->Form->textBox('AddMembers', array('MultiLine' => TRUE, 'class' => 'MultiComplete')), 'div', array('class' => 'TextBoxWrapper'));
    echo $this->Form->close('Add', '', array('class' => 'Button Action'));

?>
</div>
<?php
}
?>
<div class="GroupMembers">
    <?php
        if (!empty($members)) {
            foreach($members as $member) {
                ?>
                <ul class="GroupMember">
                    <?php
                        echo wrap(userPhoto($member), 'li class="Narrow"');
                        echo wrap(userAnchor($member), 'li');
                        if ($this->data('Owner')) {
                            echo wrap(anchor('Remove', "group/{$slug}/member/remove/{$member->UserID}", 'Button'), 'li class="Narrow"');
                            if ($member->Owner) {
                                echo wrap(anchor('Revoke Owner', "group/{$slug}/owner/revoke/{$member->UserID}", 'Button'), 'li class="Narrow"');
                            } else {
                                echo wrap(anchor('Make Owner', "group/{$slug}/owner/make/{$member->UserID}", 'Button'), 'li class="Narrow"');
                            }
                        }
                    ?>
                </ul>
                <?php
            }
        } else {
            echo wrap(t('No Members Found'), 'div class="Info"');
        }
    ?>
</div>
<?php
    echo $this->Pager->render();
