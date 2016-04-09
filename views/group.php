<?php if (!defined('APPLICATION')) exit();
$group = $this->data('Group');
?>
<div class="GroupsListingBig">
    <?php
        if (!empty($group)) {
            $slug = Gdn_Format::url($group->Name);
            ?>
            <div class="GroupsListing">
                <div class="GroupsListingDetail">
                <?php
                    echo img("uploads/mygroups/{$group->Picture}", array('class' => 'GroupsPicture'));
                    echo Gdn_Format::html($group->Description);
                ?>
                </div>
                <?php if (!$this->data('Member') && !$this->data('Applicant')) { ?>
                <div class="GroupsListingJoin">
                <?php echo anchor('Join Group', "group/{$slug}/applicant/join", 'Button BigButton GroupsListingJoinButton'); ?>
                </div>
                <?php } ?>
                <?php if ($this->data('Applicant')) { 
                    echo wrap(t('Your application is pending'), 'div class="Info GroupsListingJoin"');
                } ?>
            </div>
            <?php
        } 
    ?>
    <div class="GroupRemaining"></div>
</div>
