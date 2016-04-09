<?php if (!defined('APPLICATION')) exit();
echo Gdn_Theme::module('GroupsTabsModule');
?>
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
                        echo Wrap(anchor($group->Name, "group/{$slug}"), 'h2 class="GroupsHeading"');
                        echo img("/uploads/mygroups/{$group->Picture}", array('class' => 'GroupsPicture'));
                        echo Gdn_Format::html(strlen($group->Description) > 300  ? sliceString($group->Description, 280, anchor('&hellip;', "group/{$slug}")) : $group->Description );
                    ?>
                    </div>
                    <?php if ($this->data('Active') != 'Mine' && !(Gdn::session()->isValid() &&$group->MyGroupUserID == Gdn::session()->User->UserID && $group->Applicant)) { ?>
                    <div class="GroupsListingJoin">
                    <?php echo anchor('Join Group', "group/{$slug}/applicant/join", 'Button BigButton GroupsListingJoinButton'); ?>
                    </div>
                    <?php } 
                    if (Gdn::session()->isValid() && $group->MyGroupUserID == Gdn::session()->User->UserID && $group->Applicant) { 
                        echo wrap(t('Your application is pending'), 'div class="Info GroupsListingJoin"');
                    } ?>
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
            echo wrap(t('No Groups Found'), 'div class="Info"');
        }
    ?>
    <div class="GroupsRemaining"></div>
</div>
<?php
    echo $this->Pager->render();
