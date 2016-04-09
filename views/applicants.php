<?php if (!defined('APPLICATION')) exit(); ?>
<?php
$applicants = $this->data('Applicants');
$slug = Gdn_Format::url($this->data('Group.Name'));
?>
<div class="GroupMembers">
    <?php
        if (!empty($applicants)) {
            foreach($applicants as $applicant) {
                ?>
                <ul class="GroupMember">
                    <?php
                        echo wrap(userPhoto($applicant), 'li class="Narrow"');
                        echo wrap(userAnchor($applicant), 'li');
                        echo wrap(anchor('Approve', "group/{$slug}/applicant/approve/{$applicant->UserID}", 'Button'), 'li class="Narrow"');
                        echo wrap(anchor('Deny', "group/{$slug}/applicant/deny/{$applicant->UserID}", 'Button'), 'li class="Narrow"');
                    ?>
                </ul>
                <?php
            }
        } else {
            echo wrap(t('No Applicants Found'), 'div class="Info"');
        }
    ?>
</div>
<?php
    echo $this->Pager->render();
