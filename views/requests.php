<?php if (!defined('APPLICATION')) exit();
echo Gdn_Theme::module('GroupsTabsModule');
?>
<div class="RequestGroup"><?php echo anchor('Request Group', 'groups/request', 'Button Primary'); ?></div>
<div class="GroupsListings">
    <?php
        if ($this->data('Groups') && !empty($this->Data['Groups'])) {
            $cellCount = 0;
            foreach($this->data('Groups') as $group) {
                $cellCount++;
                if ($cellCount == 1) {
                    echo '<div class="GroupsListingRow">';
                }
                $slug = Gdn_Format::url($group->Name);
                ?>
                <div class="GroupsListing">
                    <div class="GroupsListingDetail">
                    <?php
                        echo Wrap(Gdn_Format::text($group->Name), 'h2 class="GroupsHeading"');
                        echo img("/uploads/mygroups/{$group->Picture}", array('class' => 'GroupsPicture'));
                        echo Gdn_Format::html(strlen($group->Description) > 300  ? sliceString($group->Description, 280, anchor('&hellip;', "group/{$slug}")) : $group->Description );
                    ?>
                    </div>
                    <div class="GroupsListingJoin">
                    <?php echo anchor('Edit Group', "groups/request/{$group->MyGroupID}", 'Button BigButton GroupsListingJoinButton'); ?>
                    </div>
                </div>
                <?php
                if ($cellCount == 3) {
                    echo '</div>';
                    $cellCount = 0;
                }
            }
            
            if ($cellCount) {
                echo '</div>'; 
            }
            
        } else {
            echo wrap(t('No Requests Found'), 'div class="Info"');
        }
    ?>
    <div class="GroupsRemaining"></div>
</div>
<?php
    echo $this->Pager->render();
