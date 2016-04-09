<?php if (!defined('APPLICATION')) exit();

/**
 *  @@ MyGroupsSettingsDomain @@
 *
 *  Links Settings Worker to the worker collection
 *  and retrieves it. Auto initialising.
 *
 *  Provides a simple way for other workers, or
 *  the plugin file to call it method and access its
 *  properties.
 *
 *  A worker will reference the Settings work like so:
 *  $this->plgn->settings()
 *
 *  The plugin file can access it like so:
 *  $this->settings()
 *
 *  @abstract
 */

abstract class MyGroupsSettingsDomain extends MyGroupsAPIDomain {

/**
 * The unique identifier to look up Worker
 * @var string $workerName
 */

  private $workerName = 'settings';

  /**
   *  @@ settings @@
   *
   *  Settings Worker Domain address, 
   *  links and retrieves
   *
   *  @return void
   */

  public function settings() {
    $workerName = $this->workerName;
    $workerClass = $this->getPluginIndex() . $workerName;
    return $this->linkWorker($workerName, $workerClass);
  }
}

/**
 *  @@ MyGroupsSettings @@
 *
 *  The worker used to handle the backend
 *  settings interactions.
 *
 */

class MyGroupsSettings {
    
    protected $myGroupsModel = null;
    
  /**
    *  @@ settingsMenuItems @@
    *
    *  Basic settings menu item and link
    *
    *  @param Gdn_Controller $Sender
    *
    *  @return void
    */

    public function settingsMenuItems($sender) {
        $menu = $sender->EventArguments['SideMenu'];
        $menu->addLink('Forum', t('Groups') . ' ' . wrap('', 'span class="Popin" rel="' . url('settings/mygroups/requestscount') . '"'), 'settings/mygroups', 'Garden.Settings.Manage');
    }
    
  /**
   *  @@ settingsController @@
   *
   *  Used to manage groups
   *  @param Gdn_Controller $sender
   *
   *  @return void
   */
    
    public function settingsController($sender) {
        if (Gdn::session()->checkPermission('Garden.Settings.Manage') || Gdn::session()->checkPermission('Plugins.MyGroups.Manage')) {
            $sender->addSideMenu();
            $this->myGroupsModel = new MyGroupsModel();
            $this->plgn->utility()->miniDispatcher($sender, 'settings');
        } else {
            throw permissionException();
        }
    }
    
    public function settingsController_index($sender) {
        $sender->setData('Title', t('Groups'));
        
        list($offset, $limit) = offsetLimit(val(0, $sender->RequestArgs, 0), c('Plugins.MyGroups.PageLimit', 30) );
                
        $pagerFactory = new Gdn_PagerFactory();
        $sender->Pager = $pagerFactory->getPager('Pager', $sender);
        $sender->Pager->ClientID = 'Pager';
        $sender->Pager->configure(
            $offset,
            $limit,
            $this->myGroupsModel->getGroupsCount(),
            "settings/mygroups/{Page}"
        );
        $sender->setData('groupRequestCount', $this->myGroupsModel->getGroupRequestsCount());
        $sender->setData('groups', $this->myGroupsModel->getGroups($offset));
        $sender->View = $this->plgn->utility()->themeView('settingsgroups');
        $sender->render();
    }
    
    public function settingsController_requestsCount($sender) {
        $count = $this->myGroupsModel->getGroupRequestsCount();
        if ($count) {
            echo '<span class="Alert">', $count, '</span>';
        }
    }
    
    public function settingsController_requests($sender) {
        $sender->setData('Title', t('Group Requests'));
        
        if (Gdn::request()->get('notified')) {
            UserModel::setMeta(Gdn::session()->User->UserID, array('NewRequest'  => ''), 'MyGroups.');
            redirect('settings/mygroups/requests');
        }
        
        list($offset, $limit) = offsetLimit(val(0, $sender->RequestArgs, 0), c('Plugins.MyGroups.PageLimit', 30) );
        
        $pagerFactory = new Gdn_PagerFactory();
        $sender->Pager = $pagerFactory->getPager('Pager', $sender);
        $sender->Pager->ClientID = 'Pager';
        $sender->Pager->configure(
            $offset,
            $limit,
            $this->myGroupsModel->getGroupRequestsCount(),
            "settings/mygroups/{Page}"
        );
        $sender->setData('groupRequestCount', $this->myGroupsModel->getGroupRequestsCount());
        $sender->setData('groups', $this->myGroupsModel->getRequestedGroups(null, $offset));
        $sender->View = $this->plgn->utility()->themeView('settingsgrouprequests');
        $sender->render();
    }
    
    public function settingsController_add($sender) {
        $sender->setData('Title', t('Add Group'));
        $this->plgn->api()->groupSave($sender, 'settings/mygroups');
        $sender->View = $this->plgn->utility()->themeView('settingsgroup');
        $sender->render();
    }
    
    public function settingsController_edit($sender) {
        $groupID = val(1, $sender->RequestArgs);
        if ($groupID && ctype_digit($groupID)) {
            $group = $this->myGroupsModel->getGroup($groupID);
            $redirect = 'settings/mygroups';
            $force = array();
            if (!$group) {
                $group = $this->myGroupsModel->getRequestGroup($groupID);
                $redirect = 'settings/mygroups/requests';
                $force = array('Request' => true);
            }
            if ($group) {
                $sender->setData('Title', t('Edit Group'));
                $this->plgn->api()->groupSave($sender, $redirect, $force);
                $sender->setData('group', $group);
                $sender->Form->setData($group);
                $sender->View = $this->plgn->utility()->themeView('settingsgroup');
                $sender->render();
                return;
            }
        }
        
        throw notFoundException();
    }
    
    public function settingsController_delete($sender) {
        $groupID = val(1, $sender->RequestArgs);
        if ($groupID && ctype_digit($groupID)) {
            $group = $this->myGroupsModel->getGroup($groupID);
            $redirect = 'settings/mygroups';
            $force = array();
            if (!$group) {
                $group = $this->myGroupsModel->getRequestGroup($groupID);
                $redirect = 'settings/mygroups/requests';
            }
            
            if ($group) {
                $this->myGroupsModel->deleteGroup($groupID);
                redirect($redirect);
            }
        }
        
        throw notFoundException();
    }
    
    public function settingsController_approve($sender) {
        $groupID = val(1, $sender->RequestArgs);
        if ($groupID && ctype_digit($groupID)) {
            $group = $this->myGroupsModel->getRequestGroup($groupID);
            if ($group) {
                $this->myGroupsModel->approveGroup($groupID);
                $slug = Gdn_Format::url(val('Name', $group));
                $owners = $this->myGroupsModel->getGroupOwners($groupID);
                
                foreach($owners as $owner) {
                    $email = new Gdn_Email();
                    $email->to($owner)
                        ->subject(formatString(t('[{Site}] {Group} Group Approved'), array(
                            'Site' => c('Garden.Title'), 
                            'Group' => Gdn_Format::text(val('Name', $group))
                        )))
                        ->message(formatString(t("Hi {Name},\n\nYour group {Group} has been approved!\n\nView the group here:\n{Link}\n\nRegards,\n\nSite Management"), array(
                            'Group' => Gdn_Format::text(val('Name', $group)),
                            'Name' => Gdn_Format::text($owner->Name),
                            'Link' => url("group/{$slug}", true))
                        ))
                        ->send();
                }
                redirect('settings/mygroups/requests');
            }
        }
        throw notFoundException();
    }
}
