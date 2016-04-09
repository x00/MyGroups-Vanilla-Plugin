<?php if (!defined('APPLICATION')) exit();
class GroupTabsModule extends Gdn_Module {
    
    protected $group = null;
    protected $active = null;
    protected $owner = false;
    protected $applicantCount = '';
    protected $memberCount = '';
    protected $ownerCount = '';
    protected $discussionCount = '';
    protected $msg = '';
    
    
    
    public function __construct($Sender = '') {
        parent::__construct($Sender);
    }
    
    public function getData() {
        $this->group = (object)Gdn::Controller()->data('Group');
        $this->active = Gdn::Controller()->data('Active');
        $this->owner = Gdn::Controller()->data('Owner', false);
        $this->member = Gdn::Controller()->data('Member', false);
        $this->applicantCount = Gdn::Controller()->data('applicantCount', '');
        $this->memberCount = Gdn::Controller()->data('memberCount', '');
        $this->ownerCount = Gdn::Controller()->data('ownerCount', '');
        $this->discussionCount = Gdn::Controller()->data('discussionCount', '');
        $this->resourceCount = Gdn::Controller()->data('resourceCount', '');
        $this->activityCount = Gdn::Controller()->data('activityCount', '');
        $this->msg = Gdn::Controller()->data('GroupMsg', '');
        $this->title = Gdn::Controller()->data('Title', '');
        $this->HasResources = Gdn::Controller()->data('HasResources', '');
    }

    public function assetTarget() {
        return 'Content';
    }
   
    public function toString() {
        $this->getData();
        $group = $this->group;
        if ($group) {
            $active = $this->active;
            $owner = $this->owner;
            $member = $this->member;
            $slug = Gdn_Format::url($group->Name);
            $title = $this->title;
            $HasResources = $this->HasResources;
            if (!empty($group)) {
            ?>
            <h1><?php echo $title; ?></h1>
            <ul class="Tabs GroupTabs">
                <li><?php echo anchor('About', "group/{$slug}", $active == 'About' ? 'Active' : ''); ?></li>
                <?php if ($member) { ?>
                <li><?php echo anchor(formatString(t('Activity{Count}'), array('Count' => $this->activityCount ? ' <span class="Count">' . $this->activityCount . '</span>' : '')), "group/{$slug}/activity", $active == 'Activity' ? 'Active' : ''); ?></li>
                <?php } ?>
                <?php if ($HasResources) { ?>
                    <li><?php echo anchor(formatString(t('Resources{Count}'), array('Count' => $this->resourceCount ? ' <span class="Count">' . $this->resourceCount . '</span>' : '')), "group/{$slug}/resources", $active == 'Resources' ? 'Active' : ''); ?></li>
                <?php } ?>
                <?php if ($member) { ?>
                <li><?php echo anchor(formatString(t('Discussions{Count}'), array('Count' => $this->discussionCount ? ' <span class="Count">' . $this->discussionCount . '</span>' : '')), "group/{$slug}/discussions", $active == 'Discussions' ? 'Active' : ''); ?></li>
                <li><?php echo anchor(formatString(t('Owners{Count}'), array('Count' => $this->ownerCount ? ' <span class="Count">' . $this->ownerCount . '</span>' : '')), "group/{$slug}/owners", $active == 'Owners' ? 'Active' : ''); ?></li>
                <li><?php echo anchor(formatString(t('Members{Count}'), array('Count' => $this->memberCount ? ' <span class="Count">' . $this->memberCount . '</span>' : '')), "group/{$slug}/members", $active == 'Members' ? 'Active' : ''); ?></li>
                <?php } ?>
                <?php if ($owner) { ?>
                    <li><?php echo anchor(formatString(t('Applicant{Count}'), array('Count' => $this->applicantCount ? ' <span class="Count">' . $this->applicantCount . '</span>' : '')) , "group/{$slug}/applicants", $active == 'Applicants' ? 'Active' : ''); ?></li>
                <?php } ?>
            </ul>
            <?php }
            if ($this->msg) {
                echo wrap($this->msg, 'div class="Info"');
            }
        }
    }
}
