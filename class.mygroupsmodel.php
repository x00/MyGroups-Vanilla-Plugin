<?php if (!defined('APPLICATION')) exit();
class MyGroupsModel extends VanillaModel {
    
    protected static $groups = array();
    protected static $memberGroups = array();
    protected static $availGroups = array();
    protected static $groupMembers = array();
    protected static $groupOwners = array();
    protected static $groupApplicants = array();
    
    public function __construct() {
        parent::__construct('MyGroup');
    }
    
    public function saveGroup($fields) {
        
        if (!isset($fields['CategoryID'])) {
            $fields['CategoryID'] = $this->groupCategory($fields['Name'], $fields['Description'])->CategoryID;
        } else if ($fields['CategoryID'] > 0) {
             $this->groupCategory($fields['Name'], $fields['Description'], false, $fields['CategoryID']);
        }
        
        if (isset($fields['MyGroupID']) && $fields['MyGroupID']) {
            $this->SQL
                ->update('MyGroup')
                ->set(
                    array(
                        'CategoryID'    =>  $fields['CategoryID'], 
                        'Name'          =>  $fields['Name'], 
                        'Description'   =>  $fields['Description'], 
                        'Picture'       =>  $fields['Picture'],
                        'Request'       =>  isset($fields['Request']) && $fields['Request'] ? 1 : 0
                    ) 
                )
                ->where('MyGroupID', $fields['MyGroupID'])
                ->put();
        } else {
            $this->SQL
                ->insert('MyGroup', 
                    array(
                        'MyGroupID'     =>  $fields['MyGroupID'], 
                        'CategoryID'    =>  $fields['CategoryID'], 
                        'Name'          =>  $fields['Name'], 
                        'Description'   =>  $fields['Description'], 
                        'Picture'       =>  $fields['Picture'],
                        'Request'       =>  isset($fields['Request']) && $fields['Request'] ? 1 : 0  
                    )
                );    
        }
        
        $groupID = isset($formValues['MyGroupID']) && $formValues['MyGroupID'] ? $formValues['MyGroupID'] : $this->SQL->Database->connection()->lastInsertId();
        
        $this->getGroupsCount(true);
        $this->getGroupRequestsCount(true);
        
        return $groupID;
    }
    
    public function approveGroup($groupID) {
        if (!ctype_digit($groupID)) {
            return;
        }
        
        $group = $this->getRequestGroup($groupID);
        
        if ($group) {
            
            $select = $this->SQL
                ->put('MyGroup',
                    array(
                        'Request' => 0,
                        'CategoryID' => $this->groupCategory($group->Name, $group->Description)->CategoryID
                    ),
                    array('MyGroupID' => $groupID)
                );
            self::$groups = array();
        }
    }
    
    public function saveResourceCount($groupID, $count) {
        $this->SQL
            ->update('MyGroup')
            ->set('ResourceCount', $count)
            ->where('MyGroupID', $groupID)
            ->put();
            
        $this->getGroups(true);        
    }
    
    public function saveGroupMember($userID, $groupID, $fields) {
        
        if (!ctype_digit($userID) ||  !ctype_digit($groupID)) {
            return;
        }
        
        $isApplicant = $this->getGroupApplicant($userID, $groupID);
        $isMember = $this->getGroupMember($userID, $groupID);

        if ($isApplicant || $isMember) {
            $this->SQL
                ->update('MyGroupMember')
                ->set(
                    array(
                        'Owner'         =>  isset($fields['Owner']) && $fields['Owner'] ? 1 : 0, 
                        'Applicant'     =>  isset($fields['Applicant']) && $fields['Applicant'] ? 1 : 0
                    ) 
                )
                ->where('MyGroupID', $groupID)
                ->where('MyGroupUserID', $userID)
                ->put();

        } else {
            $this->SQL
                ->insert('MyGroupMember',
                    array(
                        'MyGroupID'     =>  $groupID, 
                        'MyGroupUserID' =>  $userID, 
                        'Owner'         =>  isset($fields['Owner']) && $fields['Owner'] ? 1 : 0, 
                        'Applicant'     =>  isset($fields['Applicant']) && $fields['Applicant'] ? 1 : 0
                    ) 
                );
        }
        
        $this->getGroupApplicantCount($groupID, true);
        $this->getGroupMemberCount($groupID, true);
        $this->getGroupOwnerCount($groupID, true);
        
        $this->getGroupsByUserIDCount($userID, true);
        
        if (isset(self::$groupMembers[$groupID]) && isset(self::$groupMembers[$groupID][$userID])) {
            unset(self::$groupMembers[$groupID][$userID]);
            $this->getGroupMember($userID, $groupID);
        }
        
        if (isset(self::$groupApplicants[$groupID]) && isset(self::$groupApplicants[$groupID][$userID])) {
            unset(self::$groupApplicants[$groupID][$userID]);
            $this->getGroupApplicant($userID, $groupID);
        }
        
    }
    
    public function getGroup($groupID) {
        
        if (!ctype_digit($groupID)) {
            return null;
        }
        
        $result = $this->getGroups(0, 1, $groupID);
        
        if (is_array($result)) {
            $result = array_pop($result);
        }
        
        return $result;
    }
    
    public function getRequestGroup($groupID) {
        
        if (!ctype_digit($groupID)) {
            return null;
        }
        
        $result = $this->getGroups(0, 1, array('g.MyGroupID' => $groupID, 'g.Request' => true));
        
        if (is_array($result)) {
            $result = array_pop($result);
        }
        
        return $result;
    }
    
    public function getGroupByCategoryID($categoryID) {
        
        $result = $this->getGroups(0, 1, array('CategoryID' => $categoryID));
        
        if (is_array($result)) {
            $result = array_pop($result);
        }
        
        return $result;
    }
    
    public function getGroupBySlug($slug) {
        
        $categoryModel = new categoryModel();
        $category = $categoryModel->getByCode($slug);
        
        $result = false;
        
        if ($category) {
            $result = $this->getGroupByCategoryID($category->CategoryID);
        }
        
        return $result;
    }
    
    public function getGroupsByUserID($userID, $offset = 0, $limit = 0) {
        
        $limit = $limit ? $limit : c('Plugins.MyGroups.PageLimit', 30);
        
        $groups =  $this->SQL
            ->select('g.*, gm.*')
            ->from('MyGroup g')
            ->join('MyGroupMember gm', 'g.MyGroupID = gm.MyGroupID')
            ->where('g.Request', false)
            ->where('gm.MyGroupUserID', $userID)
            ->where('gm.Applicant', false)
            ->limit($limit, $offset)
            ->get()
            ->result();
            
        if (!$groups) {
            return array();
        }
            
        Gdn::userModel()->joinUsers($groups, array('MyGroupUserID'));
        
        $memberGroups = array();
        
        foreach ($groups as $group) {
            self::$memberGroups[$group->MyGroupID] = $group;
            $memberGroups[$group->MyGroupID] = $group;
        }
        
        return $memberGroups;
    }
    
    public function getGroupsByUserIDCount($userID, $force = false) {
        $key = "MyGroups.GroupsCount";
        $force = true;
        
        if (!$force) {
            $count = Gdn::userModel()->getMeta($userID, $key, '', 0);
        }
        
        if ($force || !$count) {

            $result = $this->SQL
                ->select('Count(g.MyGroupID) as TheCount')
                ->from('MyGroup g')
                ->join('MyGroupMember gm', 'g.MyGroupID = gm.MyGroupID')
                ->where('g.Request', false)
                ->where('gm.MyGroupUserID', $userID)
                ->where('gm.Applicant', false)
                ->get()
                ->firstRow();
                
            $count = $result && $result->TheCount > 0 ? $result->TheCount : 0;
            
            Gdn::userModel()->setMeta($userID, array($key => $count));
        }
        
        return $count;
    }
    
    public function getGroupsAvailable($userID, $offset = 0, $limit = 0) {
        
        $limit = $limit ? $limit : c('Plugins.MyGroups.PageLimit', 30);
        
        $groups =  $this->SQL
            ->select('g.*, gm.*')
            ->from('MyGroup g')
            ->join('MyGroupMember gm', 'g.MyGroupID = gm.MyGroupID')
            ->where('g.Request', false)
            ->whereNotIn(
                'g.MyGroupID', array(
                    'SELECT gm2.MyGroupID FROM ' .
                    $this->SQL->Database->DatabasePrefix . 'MyGroupMember gm2 ' .
                    'WHERE gm2.Applicant = FALSE AND gm2.MyGroupUserID = ' . (int) $userID
                    ),
                false)
            ->groupBy('g.MyGroupID')
            ->limit($limit, $offset)
            ->get()
            ->result();
        
        if (!$groups) {
            return array();
        } 
        
        Gdn::userModel()->joinUsers($groups, array('MyGroupUserID'));
        
        $availGroups = array();
        
        foreach ($groups as $group) {
            self::$availGroups[$group->MyGroupID] = $group;
            $availGroups[$group->MyGroupID] = $group;
        }
        
        return $availGroups;
    }
    
    public function getGroupsAvailableCount($userID) {
        return $this->getGroupsCount() - $this->getGroupsByUserIDCount($userID);
    }
    
    public function getGroups($offset = 0, $limit = 0, $where = null, $clear = false) {
    
        $limit = $limit ? $limit : c('Plugins.MyGroups.PageLimit', 30);
        
        if ($clear) {
            self::$groups = array();
        }
        
        if (ctype_digit($where)) {
            if (isset(self::$groups[$where])) {
                return array(self::$groups[$where]);
            } else {
                $where = array('g.MyGroupID' => $where);
            }
        }
        
        $this->SQL
            ->select('g.*')
            ->from('MyGroup g')
            ->where('g.Request', isset($where['g.Request']) ? $where['g.Request'] : false);
        
        if (is_array($where)) {
            $this->SQL
                ->where($where);
        }
            
        $groups = $this->SQL   
            ->limit($limit, $offset)
            ->get()
            ->result();
        
        $result = array();
        
        foreach ($groups as $group) {
            self::$groups[$group->MyGroupID] = $group;
            $result[$group->MyGroupID] = $group;
        }
        
        return self::$groups;
    }
    
    
    
    public function getRequestedGroups($userID = 0, $offset = 0, $limit = 0, $where = null) {
    
        $limit = $limit ? $limit : c('Plugins.MyGroups.PageLimit', 30);
        
        if (ctype_digit($where)) {
            if (isset(self::$groups[$where])) {
                return array(self::$groups[$where]);
            } else {
                $where = array('MyGroupID' => $where);
            }
        }
        
        $this->SQL
            ->select('g.*')
            ->from('MyGroup g')
            ->join('MyGroupMember gm', 'g.MyGroupID = gm.MyGroupID')
            ->where('g.Request', true);
            
        if ($userID) {
            $this->SQL
                ->where('gm.MyGroupUserID', $userID);
        }
        
        if (is_array($where)) {
            $this->SQL
                ->where($where);
        }
            
        $groups = $this->SQL   
            ->limit($limit, $offset)
            ->get()
            ->result();
        
        return $groups;
    }
    
    public function getGroupsCount($force = false) {
        
        $key = "MyGroups.GroupsCount";
        $force = true;
        
        if (!$force) {
            $count = Gdn::get($key, 0);
        }
        
        if ($force || !$count) {

            $result = $this->SQL
                ->select('Count(g.MyGroupID) as TheCount')
                ->where('g.Request', false)
                ->from('MyGroup g')
                ->get()
                ->firstRow();
                
            $count = $result && $result->TheCount > 0 ? $result->TheCount : 0;
            
            Gdn::set($key, $count);
        }
        
        return $count;
    }
    
    public function getGroupRequestsCount($force = false) {
        
        $key = "MyGroups.GroupRequestsCount";
        $force = true;
        
        if (!$force) {
            $count = Gdn::get($key, 0);
        }
        
        if ($force || !$count) {

            $result = $this->SQL
                ->select('Count(g.MyGroupID) as TheCount')
                ->where('g.Request', true)
                ->from('MyGroup g')
                ->get()
                ->firstRow();
                
            $count = $result && $result->TheCount > 0 ? $result->TheCount : 0;
            
            Gdn::set($key, $count);
        }
        
        return $count;
    }
    
    public function getGroupMember($userID, $groupID) {
        
        if (!ctype_digit($userID) ||  !ctype_digit($groupID)) {
            return null;
        }
        
        if (!isset(self::$groupMembers[$groupID])) {
            self::$groupMembers[$groupID] = array();
        }
        if (!isset(self::$groupMembers[$groupID][$userID])) {
            $groupsMember =  $this->SQL
                ->select('gm.*, gm.MyGroupUserID as UserID')
                ->from('MyGroupMember gm')
                ->where('MyGroupUserID', $userID)
                ->where('MyGroupID', $groupID)
                ->where('Applicant', false)
                ->get()
                ->firstRow();
                
            if (!$groupsMember) {
                return null;
            }
                
            Gdn::userModel()->joinUsers($groupsMember, array('UserID'));

            self::$groupMembers[$groupID][$userID] = $groupsMember;
        }
        
        return self::$groupMembers[$groupID][$userID];
    }
    
    public function isOwner($userID, $groupID) {
        
        if (!ctype_digit($userID) ||  !ctype_digit($groupID)) {
            return false;
        }
        
        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return true;
        }
        
        if (Gdn::session()->checkPermission('Plugin.MyGroups.Manage')) {
            return true;
        }
        
        $member = $this->getGroupMember($userID, $groupID);
        return $member && $member->Owner;
    }
    
    public function isMember($userID, $groupID) {
        
        if (!ctype_digit($userID) ||  !ctype_digit($groupID)) {
            return false;
        }
        
        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return true;
        }
        
        if (Gdn::session()->checkPermission('Plugins.MyGroups.Manage')) {
            return true;
        }
        
        $member = $this->getGroupMember($userID, $groupID);
        return $member && !$member->Applicant;
    }
    
    public function isApplicant($userID, $groupID) {
        
        if (!ctype_digit($userID) ||  !ctype_digit($groupID)) {
            return false;
        }
        
        $member = $this->getGroupApplicant($userID, $groupID);
        return $member && $member->Applicant;
    }
    
    public function getGroupApplicant($userID, $groupID) {
    
        if (!ctype_digit($userID) ||  !ctype_digit($groupID)) {
            return null;
        }
        
        if (!isset(self::$groupApplicants[$groupID])) {
            self::$groupApplicants[$groupID] = array();
        }
        
        if (!isset(self::$groupApplicants[$groupID][$userID])) {
            $groupsApplicant =  $this->SQL
                ->select('gm.*')
                ->from('MyGroupMember gm')
                ->where('MyGroupUserID', $userID)
                ->where('MyGroupID', $groupID)
                ->where('Applicant', true)
                ->get()
                ->firstRow();
                
            if (!$groupsApplicant) {
                return null;
            }
                
            Gdn::userModel()->joinUsers($groupsApplicant, array('UserID'));

            self::$groupApplicants[$groupID][$userID] = $groupsApplicant;
        }
        
        return self::$groupApplicants[$groupID][$userID];
    }
    
    public function getGroupMembers($groupID, $offset = 0, $limit = 0) {
        
        $limit = $limit ? $limit : c('Plugins.MyGroups.PageLimit', 30);
        
        if (!ctype_digit($groupID)) {
            return array();
        }
        
        $groupMembers =  $this->SQL
            ->select('gm.*, gm.MyGroupUserID as UserID')
            ->from('MyGroupMember gm')
            ->where('MyGroupID', $groupID)
            ->where('Applicant', false)
            ->limit($limit, $offset)
            ->get()
            ->result();
        
        if (!$groupMembers) {
            return array();
        }
        
        Gdn::userModel()->joinUsers($groupMembers, array('UserID'));
            
        if (!isset(self::$groupMembers[$groupID])) {
            self::$groupMembers[$groupID] = array();
        }
            
        foreach ($groupMembers as $groupMember) {
            self::$groupMembers[$groupID][$groupMember->MyGroupUserID] = $groupMember;
        }
        
        return $groupMembers;
    }
    
    public function getGroupMemberCount($groupID, $force = false) {
        
        $countType = 'MyGroup.Member.' . $groupID;
        
        if ($force) {
            $result = $this->SQL
            ->select('Count(gm.MyGroupUserID) TheCount')
            ->from('MyGroupMember gm')
            ->where('MyGroupID', $groupID)
            ->where('Applicant', false)
            ->get()
            ->firstRow();
            
            Gdn::set($countType, $result ? $result->TheCount : 0);
        }
        
        return Gdn::get($countType, 0);
    }
    
    public function getGroupApplicants($groupID, $offset = 0, $limit = 0) {
        
        $limit = $limit ? $limit : c('Plugins.MyGroups.PageLimit', 30);
        
        if (!ctype_digit($groupID)) {
            return array();
        }
        
        $groupApplicants =  $this->SQL
            ->select('gm.*, gm.MyGroupUserID as UserID')
            ->from('MyGroupMember gm')
            ->where('MyGroupID', $groupID)
            ->where('Applicant', true)
            ->limit($limit, $offset)
            ->get();
            
        Gdn::userModel()->joinUsers($groupApplicants, array('UserID'));
            
        if (!isset(self::$groupApplicants[$groupID])) {
            self::$groupApplicants[$groupID] = array();
        }
            
        foreach ($groupApplicants as $groupsApplicant) {
            self::$groupApplicants[$groupID][$groupsApplicant->MyGroupUserID] = $groupsApplicant;
        }
        
        return $groupApplicants;
    }
    
    public function getGroupApplicantCount($groupID, $force = false) {
        
        $countType = 'MyGroup.Applicants.' . $groupID;
        
        if ($force) {
            $result = $this->SQL
            ->select('Count(gm.MyGroupUserID) TheCount')
            ->from('MyGroupMember gm')
            ->where('MyGroupID', $groupID)
            ->where('Applicant', true)
            ->get()
            ->firstRow();
            
            Gdn::set($countType, $result ? $result->TheCount : 0);
        }
        
        return Gdn::get($countType, 0);
    }
    
    public function getGroupOwners($groupID, $offset = 0, $limit = 0) {
        
        $limit = $limit ? $limit : c('Plugins.MyGroups.PageLimit', 30);
        
        if (!ctype_digit($groupID)) {
            return array();
        }
        
        $groupOwners =  $this->SQL
            ->select('gm.*, gm.MyGroupUserID as UserID')
            ->from('MyGroupMember gm')
            ->where('MyGroupID', $groupID)
            ->where('Applicant', false)
            ->where('Owner', true)
            ->limit($limit, $offset)
            ->get()
            ->result();
            
        Gdn::userModel()->joinUsers($groupOwners, array('UserID'));
            
        if (!isset(self::$groupOwners[$groupID])) {
            self::$groupOwners[$groupID] = array();
        }
            
        foreach ($groupOwners as $groupOwner) {
            self::$groupOwners[$groupID][$groupOwner->MyGroupUserID] = $groupOwners;
        }
        
        return $groupOwners;
    }
    
    public function getGroupOwnerCount($groupID, $force = false) {
        
        $countType = 'MyGroup.Owner.' . $groupID;
        
        if ($force) {
            $result = $this->SQL
            ->select('Count(gm.MyGroupUserID) TheCount')
            ->from('MyGroupMember gm')
            ->where('MyGroupID', $groupID)
            ->where('Applicant', false)
            ->where('Owner', true)
            ->get()
            ->firstRow();
            
            Gdn::set($countType, $result ? $result->TheCount : 0);
        }
        
        return Gdn::get($countType, 0);
    }
    
    public function deleteGroup($groupID) {
        
        if (!ctype_digit($groupID)) {
            return;
        }
        
        $group = $this->getGroup($groupID);
        if ($group) {
            $this->SQL
                ->delete('MyGroup', array('MyGroupID' => $groupID));
                
            $this->getGroupsCount(true);
            $this->getGroupRequestsCount(true);
            $this->getGroupsByUserIDCount(true);
        }
    }
    
    public function deleteGroupMember($userID, $groupID) {
        
        if (!ctype_digit($userID) ||  !ctype_digit($groupID)) {
            return;
        }
        
        if ($this->getGroupMember($userID, $groupID)) {
            $this->SQL
                ->delete('MyGroupMember', array('MyGroupID' => $groupID, 'MyGroupUserID' => $userID, 'Applicant' => false));
                
            if (isset(self::$groupMembers[$groupID]) && isset(self::$groupMembers[$groupID][$userID])) {
                unset(self::$groupMembers[$groupID][$userID]);
            }
            
            $this->getGroupMemberCount($groupID, true);
            
        }
    }
    
    public function deleteGroupApplicant($userID, $groupID) {
        
        if (!ctype_digit($userID) ||  !ctype_digit($groupID)) {
            return;
        }
        
        if ($this->getGroupApplicant($userID, $groupID)) {
            $this->SQL
                ->delete('MyGroupMember', array('MyGroupID' => $groupID, 'MyGroupUserID' => $userID, 'Applicant' => true));
                
            if (isset(self::$groupApplicants[$groupID]) && isset(self::$groupApplicants[$groupID][$userID])) {
                unset(self::$groupApplicants[$groupID][$userID]);
            }
            
            $this->getGroupApplicantCount($groupID, true);
            
        }
    }
    
    public function groupCategory($name, $description, $root = false, $categoryID = 0) {
        $slug = $root ? 'groups-root' : Gdn_Format::url($name);
        $categoryModel = new categoryModel();
        if ($categoryID || !$categoryModel->getByCode($slug)) {
            $myGroupsCategory = array(
                'Name' => $name, 
                'Description' => sliceString($description, 200), 
                'UrlCode' => $slug, 
                'CustomPermissions' => true, 
                'Permissions' => array(), 
                'AllowDiscussions' => $root ? 0 : 1, 
                'ParentCategoryID'  => ($root ? -1 : $categoryModel->getByCode('groups-root')->CategoryID)
            );
            
            if ($categoryID) {
                $myGroupsCategory['CategoryID'] = $categoryID;
            }
            
            $categoryModel->save($myGroupsCategory);
        }
        
        return $categoryModel->getByCode($slug);
        
    }
    
    public function groupCategoryById($id) {
        $categoryModel = new categoryModel($id);
        return $categoryModel->getID($id);
    }
    
    public function rootCategory() {
        $categoryModel = new categoryModel();
        return $categoryModel->getByCode('groups-root');
    }
    
}
