<?php if (!defined('APPLICATION')) exit();

/**
 *  @@ MyGroupsAPIDomain @@
 *
 *  Links API Worker to the worker collection
 *  and retrieves it. Auto initialising.
 *
 *  Provides a simple way for other workers, or
 *  the plugin file to call it method and access its
 *  properties.
 *
 *  A worker will reference the API work like so:
 *  $this->plgn->api()
 *
 *  The plugin file can access it like so:
 *  $this->api()
 *
 *  @abstract
 */

abstract class MyGroupsAPIDomain extends MyGroupsUtilityDomain {

/**
 * The unique identifier to look up Worker
 * @var string $workerName
 */

  private $workerName = 'api';

  /**
   *  @@ api @@
   *
   *  API Worker Domain address , 
   *  links and retrieves
   *
   *  @return void
   */

  public function api(){
    $workerName = $this->workerName;
    $workerClass = $this->getPluginIndex() . $workerName;
    return $this->linkWorker($workerName , $workerClass);
  }

}

/**
 *  @@ MyGroupsAPI @@
 *
 *  The worker used for the internals
 *
 *  Also can be access by other plugin by
 *  hooking MyGroups_Loaded_Handler
 *  and accessing $sender->Plgn->aPI();
 *
 */

class MyGroupsAPI {
    
    public static $cache = array();

    public function groupsLink($sender) {
        if ($sender->Menu) {
            $sender->Menu->addLink('MyGroups', t('Groups'), '/groups');
        }
    }

    public function notify($sender) {
        if (Gdn::session()->checkPermission('Garden.Settings.Manage') || Gdn::session()->checkPermission('Plugins.MyGroups.Manage')) {
            $requests = UserModel::getMeta(Gdn::session()->User->UserID, 'MyGroups.NewRequest', 'MyGroups.');
            if ($requests && getvalueR('NewRequest', $requests)) {
                Gdn::controller()->informMessage(formatString(t('New Group Request(s) <a href="{RequestsUrl}">here</a>.'), array('RequestsUrl' => url('/settings/mygroups/requests?notified=1'))), array('CssClass' => 'HasSprite'));
            }
        }
    }
    
    public function setPermissions($permissions, $categoryID) {
        if (Gdn::session()->isValid()) {
            $perms = &Gdn::session()->User->Permissions;
            $perms = Gdn_Format::unserialize($perms);
            foreach($permissions as $permissionKey) {
                if (!isset($perms[$permissionKey])) {
                    $perms[$permissionKey] = array(-1);
                }
                $perms[$permissionKey][] = $categoryID;
            }
        }
    }
    
    public function linkResources($sender, $type) {
        $myGroupsResourceModel = new MyGroupsResourceModel();
        
        $categoryID = getValueR('Discussion.CategoryID', $sender->EventArguments);
        
        if ($categoryID) {
            $myGroupsModel = new MyGroupsModel();
            $group = $myGroupsModel->getGroupByCategoryID($categoryID);
            $groupID = val('MyGroupID', $group);
            
            if ($type == 'comment') {
                $foreignID = getValueR('Comment.CommentID', $sender->EventArguments);
            } else {
                $foreignID = getValueR('Discussion.DiscussionID', $sender->EventArguments);
            }
            
        }
        
        if ($groupID) {
            $myGroupsResourceModel->saveResources($foreignID, $groupID, $type);
        }
    }
    
    public function attachFile($sender) {
        $fileUpload = class_exists('FileUploadPlugin') ? Gdn::pluginManager()->getPluginInstance('FileUploadPlugin') : null;
        if ($fileUpload  && Gdn::session()->checkPermission('Plugins.Attachments.Upload.Allow', FALSE)) {
            return $sender->FetchView('attach_file', '', 'plugins/FileUpload');
        }
        return '';
    }
    
    protected function removeFile($mediaID) {
        $fileUpload = class_exists('FileUploadPlugin') ? Gdn::pluginManager()->getPluginInstance('FileUploadPlugin') : null;
        $media = $fileUpload->mediaModel()->getID($mediaID);

        if ($media) {
            $fileUpload->mediaModel()->delete($media);
            $removed = false;
            
            $this->EventArguments['Parsed'] = Gdn_Upload::parse($media->Path);
            $this->EventArguments['Handled'] =& $removed; 
            $fileUpload->fireEvent('TrashFile');

            if (!$removed) {
                $path = MediaModel::pathUploads() . DS .$media->Path;
                if (file_exists($path)) {
                   $removed = @unlink($path);
               }
            }

            if (!$removed) {
                $path = FileUploadPlugin::findLocalMedia($media, true, true);
                if (file_exists($path)){
                   $removed = @unlink($path);
                }
            }

        }
    }
    
    protected function saveAttachFile($uploadID, $foreignID, $foreignType, $groupID) {
        if (Gdn::Structure()->TableExists('Media')) {
            $model = new Gdn_Model('Media');

            $media = $model->getID($uploadID);
            $media->ForeignID = $foreignID;
            $media->ForeignTable = $foreignType;
            $media->GroupID = $groupID;
            try {
                $model->save($media);
                $myGroupsResourceModel = new MyGroupsResourceModel();
                $myGroupsResourceModel->saveResources($foreignID, $groupID, $foreignType);
            } catch (Exception $e) {
                die($e->getMessage());
                return false;
            }
            return true;
        }
        return false;
   }
    
    public function saveAttachments($foreignID, $foreignType, $groupID) {
        $allUploads = array();
        if (class_exists('FileUploadPlugin')) {
            $attachedUploads = Gdn::request()->getValue('AttachedUploads', array());
            $allUploads = Gdn::request()->getValue('AllUploads', array());
            if (!$attachedUploads) {
                return;
            }
        } else {
            $attachedUploads = Gdn::request()->getValue('MediaIDs', array());
        }

        $attached = array();
        foreach ($attachedUploads as $uploadID) {
            $attach = $this->saveAttachFile($uploadID, $foreignID, $foreignType, $groupID);
            if ($attach) {
                $attached[] = $uploadID;
            }
        }

        $deleteIDs = array_diff($allUploads, $attached);
        
        foreach ($deleteIDs as $deleteID) {
            $this->removeFile($deleteID);
        }
        
    }
    
    public function attachmentCache($foreignIDs, $foreignType) {
        
        if (!isset(self::$cache[$foreignType])) {
            self::$cache[$foreignType] = array();
        }
        
        $foreignIDs = array_diff($foreignIDs, array_keys(self::$cache[$foreignType]));
        
        if (empty($foreignIDs)) {
            return self::$cache[$foreignType];
        }
        
        $attachments = Gdn::SQL()
            ->select('m.*')
            ->from('Media m')
            ->where('m.ForeignID', $foreignIDs)
            ->where('m.ForeignTable', $foreignType)
            ->get()
            ->result();

        foreach($attachments as $attachment) {
            if (!isset(self::$cache[$foreignType][$attachment->ForeignID])) {
                self::$cache[$foreignType][$attachment->ForeignID] = array();
            }
            self::$cache[$foreignType][$attachment->ForeignID][] = $attachment;
        }
        
        return self::$cache[$foreignType];
    }
    
    public function attachmentsZip(&$rows, $table, $joinFeild) {
        $tableIDs = array();
        
        foreach ($rows as $row) {
            $tableIDs[] = val($joinFeild, $row);
        }
        
        $attachments = $this->attachmentCache($tableIDs, $table);
        
        foreach ($rows as &$row) {
            $attach = isset($attachments[val($joinFeild, $row)]) ? $attachments[val($joinFeild, $row)] : array();
            
            
            if (is_array($row)) {
                $row['Attachments'] = $attach;
            } else {
                $row->Attachments = $attach;
            }
        }
    }
    
    public function addFileUpload($sender) {
        if(Gdn::controller()->data('AddUpload')) {
            $sender->EventArguments['actions']['uploads'] = true;
            $sender->EventArguments['actions']['images'] = false;
        }
    }
    
    public function groupSave($sender, $redirect = '', $force = array(), $notify = false) {
        if ($sender->Form->isPostBack() != false) {
            $formValues = $sender->Form->formValues();
            
            $upload = new Gdn_Upload();
            try {
                $temp = $upload->validateUpload($sender->Form->escapeFieldName('ImgFile'), true);
                if($temp) {
                    if (!file_exists(PATH_ROOT . DS . 'uploads' . DS . 'mygroups')) {
                        mkdir(PATH_ROOT . DS . 'uploads' . DS . 'mygroups');
                    }
                    if (!is_writable(PATH_ROOT . DS . 'uploads' . DS . 'mygroups')) {
                        throw new exception(t('uploads/mygroups is not writable, please ensure that it exists and the web user has permission to save to this folder'));
                    }
                        
                    $img = $upload->generateTargetName(PATH_ROOT . DS . 'uploads' . DS . 'mygroups');

                    $uploadImg = $upload->saveAs(
                        $temp,
                        'mygroups' . DS . pathinfo($img, PATHINFO_BASENAME)
                    );
                    $savedImg = pathinfo($uploadImg['SaveName'], PATHINFO_BASENAME);
                    $formValues['Picture'] = $savedImg;
                }
            } catch(Exception $ex) {
                if($ex->getCode() != 400) {
                    $sender->Form->addError($ex->getMessage());
                }
            }
            
            if (!isset($formValues['Picture']) || !$formValues['Picture']) {
                $sender->Form->addError('Image Required');
            }
            
            $formValues = array_merge($formValues, $force);
            
            $myGroupsModel = new MyGroupsModel();
            $myGroupsModel->defineSchema();
            $myGroupsModel->Validation->validate($formValues);
            $sender->Form->setValidationResults($myGroupsModel->Validation->results());
            if ($sender->Form->errorCount() == 0) {
                try {
                    $groupID = $myGroupsModel->saveGroup($formValues);
                } catch(Exception $ex) {
                    $sender->Form->addError(t('Group could not be saved name may aready taken'));
                    return;
                }
                if ($groupID) {
                    $myGroupsModel->saveGroupMember(Gdn::session()->User->UserID, $groupID, array('Owner' => true));
                }
                
                if ($notify) {
                    $roles = Gdn::SQL()
                        ->select('r.*')
                        ->from('Role r')
                        ->join('Permission per', "per.RoleID = r.RoleID")
                        ->where('per.JunctionTable is null')
                        ->beginWhereGroup()
                        ->orWhere('per.`Plugins.MyGroups.Manage`', 1)
                        ->orWhere('per.`Garden.Settings.Manage`', 1)
                        ->endWhereGroup()
                        ->get()
                        ->result();
                    
                    if ($roles) {
                        $roleIDs = array();
                        
                        foreach($roles as $role) {
                            $roleIDs[] = $role->RoleID;
                        }
                        
                        if (count($roleIDs)) {
                            $users = Gdn::SQL()
                                ->select('u.*')
                                ->from('User u')
                                ->join('UserRole ur', 'u.UserID = ur.UserID')
                                ->where('ur.RoleID', $roleIDs)
                                ->orderBy('DateInserted', 'desc')
                                ->limit(30)
                                ->get()
                                ->result();
                            
                            if ($users) {
                                foreach ($users as $user) {
                                    UserModel::setMeta($user->UserID, array('NewRequest'  => 1), 'MyGroups.');
                                }
                            }
                        }
                    }
                }
                
                if ($redirect) {
                    redirect($redirect);
                }
            }
        }
    }

}
