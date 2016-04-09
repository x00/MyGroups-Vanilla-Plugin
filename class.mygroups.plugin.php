<?php if (!defined('APPLICATION')) exit();

$PluginInfo['MyGroups'] = array(
    'Name' => 'My Groups' , 
    'Description' => 'Sets up private groups with ownership, applicants and members' , 
    'RequiredApplications' => array('Vanilla' => '2.2'), 
    'SettingsPermission' => 'Garden.Settings.Manage', 
    'RegisterPermissions' => array('Plugins.MyGroups.Manage'),
    'MobileFriendly' => true,
    'SettingsUrl' => '/settings/mygroups',
    'Version' => '0.1.18b' , 
    'Author' => "Paul Thomas" , 
    'AuthorEmail' => 'dt01pqt_pt@yahoo.com'
);

/**
 *  @@ myGroupsLoad function @@
 *
 *  A callback for spl_autoload_register
 *
 *  Will load class.[name].php for MyGroups[Name]
 *  or MyGroups[Name]Domain
 *
 *  @param string $Class class name to be matched
 *
 *  @return void
 */

function myGroupsLoad($class) {
    $match = array();
    if (preg_match('`^MyGroups(.*)`' , $class , $match)) {
        $file = strtolower(preg_replace('`Domain$`', '', $match[1]));
        if (file_exists(PATH_PLUGINS . DS . 'MyGroups' . DS . 'class.' . $file . '.php')) {
            include_once(PATH_PLUGINS . DS . 'MyGroups' . DS . 'class.' . $file . '.php');
        }
    }
}

// auto load worker/domain classes.
spl_autoload_register('myGroupsLoad');

// Initialise loader to be use by various libraries an architecture
MyGroupsUtility::initLoad();

MyGroupsUtility::registerLoadMap('`^MyGroups[A-Za-z]*Model$`','/','class.{$matches[0]}.php');


//<<<< must be flush no indentation !!!!
class MyGroups extends MyGroupsUIDomain {
    
    public function base_getAppSettingsMenuItems_handler($sender) {
        $this->settings()->settingsMenuItems($sender);
    }
    
    public function settingsController_myGroups_create($sender, $args) {
        $this->settings()->settingsController($sender);
    }
    
    public function assetModel_styleCss_handler($sender) {
        $sender->addCssFile('groups.css', 'plugins/MyGroups');
    }
    
    public function vanillaController_groups_create($sender, $args) {
        $this->ui()->groupsController($sender);
    }
    
    public function vanillaController_group_create($sender, $args) {
        $this->ui()->groupController($sender);
    }
    
    public function categoriesController_beforeCategoriesRender_handler($sender, $args) {
        $this->ui()->groupDiscussionsPrep($sender);
    }
    
    public function discussionController_beforeDiscussionRender_handler($sender, $args) {
        $this->ui()->groupDiscussionsPrep($sender);
    }
    
    public function postController_beforeDiscussionRender_handler($sender, $args) {
        $this->ui()->groupDiscussionsPrep($sender);
    }
    
    public function base_afterGetSession_handler($sender, $args) {
        $this->ui()->groupDiscussionsPermission($sender);
    }
    
    public function base_render_before($sender, $args) {
        $this->api()->groupsLink($sender);
        $this->api()->notify($sender);
    }
    
    public function postController_afterDiscussionSave_handler($sender, $args) {
        $this->api()->linkResources($sender, 'discussion');
    }
    
    public function postController_afterCommentSave_handler($sender, $args) {
        $this->api()->linkResources($sender, 'comment');
    }
    
    public function categoriesController_beforeDiscussionMeta_handler($sender, $args) {
        $this->ui()->listResources($sender);
    }
    
    public function activityController_afterActivityBody_handler($sender, $args) {
        $this->ui()->activityAttachments($sender);
    }
    
    public function base_beforeDispatch_handler($sender) {
        $this->utility()->hotLoad();
    }
    
    public function base_toolbarConfig_handler($sender, &$args) {
        $this->api()->addFileUpload($sender);
    }
    
    public function base_beforeLoadRoutes_handler($sender, &$args) {
        $match = array();
        preg_match('`^(categories|group|activity/post/group)/([^/]+)`', Gdn::request()->path(), $match);
        $slug = $match ? array_pop($match): null;

        if ($slug) {
            
            $myGroupModel = new MyGroupsModel();
            $group = $myGroupModel->getGroupBySlug($slug);
            
            if ($group) {
                $this->utility()->dynamicRoute($args['Routes'], '^group/' . $slug . '/resources(/p[0-9]+)?$', 'categories/' . $slug . '$1/resources', 'Internal');
                $this->utility()->dynamicRoute($args['Routes'], '^group/' . $slug . '/discussions(/p[0-9]+)?$', 'categories/' . $slug . '$1', 'Internal');
                $this->utility()->dynamicRoute($args['Routes'], '^activity/post/group/' . $slug . '/?$', 'vanilla/group/' . $slug . '/activity', 'Internal');
            
                $this->utility()->dynamicRoute($args['Routes'], '^categories/' . $slug . '(/p[0-9]+)?/?$', 'group/' . $slug.'/discussions$1', 'Temporary');
            }
        }
        
        $this->utility()->dynamicRoute($args['Routes'], '^categories/groups-root/?', 'groups', 'Temporary');
        
        $this->utility()->dynamicRoute($args['Routes'], '^group(s)?(/.*)?$', 'vanilla/group$1$2', 'Internal');
    }

    public function setup() {
        $this->utility()->hotLoad(true);
    }

    public function pluginSetup() {
        Gdn::structure()
            ->table('MyGroup')
            ->primaryKey('MyGroupID')
            ->column('CategoryID', 'int(11)', true, array('key' , 'unique'))
            ->column('Name', 'varchar(255)', false, array('unique'))
            ->column('Description', 'text')
            ->column('Picture', 'varchar(255)')
            ->column('ResourceCount', 'int(11)', 0)
            ->column('Request', 'int(4)', 0)
            ->set();
            
        Gdn::structure()
            ->table('MyGroupMember')
            ->primaryKey('MyGroupMemberID')
            ->column('MyGroupID', 'int(11)', false, array('unique.1'))
            ->column('MyGroupUserID', 'int(11)', false, array('unique.1'))
            ->column('Applicant', 'int(4)', 1)
            ->column('Owner', 'int(4)', 0)
            ->set();
            
        Gdn::structure()
            ->table('Activity')
            ->column('GroupID', 'int(11)', 0)
            ->set();
            
        Gdn::structure()
            ->table('Media')
            ->column('ForeignID', 'int(11)', true, 'index.Foreign')
            ->column('ForeignTable', 'varchar(24)', true, 'index.Foreign')
            ->column('GroupID', 'int(11)', 0)
            ->set();
            
        
        if (Gdn::sql()->getWhere('ActivityType', array('Name' => 'MyGroupsRequest'))->numRows() == 0) {
            Gdn::sql()->insert('ActivityType', array(
                'AllowComments'     => '0',
                'Name'              => 'MyGroupsRequest',
                'FullHeadline'      => 'New Applicant %8$s',
                'ProfileHeadline'   => 'New Applicant %8$s',
                'Notify' => '1',
                'Public' => 0
            ));
        }
        
        // parent category for groups
        $myGroupModel = new MyGroupsModel();
        $myGroupModel->groupCategory('Groups', 'Parent Category of Groups', true);
        
    }

}
