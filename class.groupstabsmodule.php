<?php if (!defined('APPLICATION')) exit();
class GroupsTabsModule extends Gdn_Module {
    
    protected $active = '';
    
    
    public function __construct($Sender = '') {
        parent::__construct($Sender);
    }
    
    public function getData() {
        $this->active = Gdn::Controller()->data('Active');
    }

    public function assetTarget() {
        return 'Content';
    }
   
    public function toString() {
        $this->getData();
        $active = $this->active;
        if (Gdn::session()->IsValid()) { ?>
        <ul class="Tabs GroupTabs">
            <li><?php echo anchor('Available', 'groups', $active == 'Available' ? 'Active' : ''); ?></li>
            <li><?php echo anchor('My Groups', 'groups/mine', $active == 'Mine' ? 'Active' : ''); ?></li>
            <li><?php echo anchor('My Requests', 'groups/requests', $active == 'Requests' ? 'Active' : ''); ?></li>
        </ul>
        <?php 
        }
    }
}
