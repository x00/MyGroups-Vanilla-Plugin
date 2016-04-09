<?php if (!defined('APPLICATION')) exit();
$owners = $this->data('Owners');
$slug = Gdn_Format::url($this->data('Group.Name'));
?>
<div class="GroupMembers">
    <?php
        if (!empty($owners)) {
            foreach($owners as $owner) {
                ?>
                <ul class="GroupMember">
                    <?php
                        echo wrap(userPhoto($owner), 'li class="Narrow"');
                        echo wrap(userAnchor($owner), 'li');
                        if ($this->data('Owner')) {
                            echo wrap(anchor('Revoke Owner', "group/{$slug}/owner/revoke/{$owner->UserID}", 'Button'), 'li class="Narrow"');
                        }
                    ?>
                </ul>
                <?php
            }
        } else {
            echo wrap(t('No Owners Found'), 'div class="Info"');
        }
    ?>
</div>
<?php
    echo $this->Pager->render();
