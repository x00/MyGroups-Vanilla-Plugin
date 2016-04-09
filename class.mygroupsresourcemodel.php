<?php if (!defined('APPLICATION')) exit();
class MyGroupsResourceModel extends VanillaModel {
    
    public function __construct() {
        parent::__construct('Media');
    }
    
    public function getResources($groupID) {
        
        $this->SQL
            ->select('m.*')
            ->from('Media m')
            ->where('GroupID', $groupID)
            ->get()
            ->result();        
    }
    
    
    public function saveResources($foreignID, $groupID, $type = 'discussion') {
        
        $this->SQL
            ->update('Media m')
            ->set('GroupID', $groupID)
            ->where('ForeignID', $foreignID)
            ->where('ForeignTable', $type)
            ->put();
            
        $count = $this->SQL
            ->select('COUNT(m.MediaID) as TheCount')
            ->from('Media m')
            ->where('GroupID', $groupID)
            ->get()
            ->firstRow()
            ->TheCount;
                    
        $myGroupsModel = new MyGroupsModel();
                 
        $myGroupsModel->saveResourceCount($groupID, $count);
    }
    
}
