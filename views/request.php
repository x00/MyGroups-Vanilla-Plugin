<?php if (!defined('APPLICATION')) exit();
echo Gdn_Theme::module('GroupsTabsModule');
?>
<div class="GroupsRequestForm">
    <div class="FormWrapper">
<?php
    echo $this->Form->open(array('enctype' => 'multipart/form-data'));
    echo $this->Form->errors();
    if (!$this->data('group.MyGroupID')) {
        $this->Form->addHidden('MyGroupID', 0);
        echo $this->Form->hidden('MyGroupID', array('value' => 0));
    } else {
        $this->Form->addHidden('MyGroupID', $this->data('group.MyGroupID'));
        echo $this->Form->hidden('MyGroupID', array('value' => $this->data('group.MyGroupID')));
        $this->Form->addHidden('Picture', $this->data('group.Picture'));
        echo $this->Form->hidden('Picture', array('value' =>$this->data('group.Picture')));
        $this->Form->addHidden('CategoryID', $this->data('group.CategoryID'));
        echo $this->Form->hidden('CategoryID', array('value' => $this->data('group.CategoryID')));
    }
    
    if (!$this->data('group.MyGroupID')) {
?>
    <div class="P">
        <?php echo wrap(t('Fill out this form to request a new group'), 'div class="Info"'); ?>
    </div>
<?php
    }
?>    
    
    <div class="P">
        <?php echo $this->Form->label('Image'); ?>
    </div>
    <div class="P">
        <?php
        if ($this->data('group.Picture')) {
            echo wrap(img('/uploads/mygroups/'.$this->data('group.Picture'), array('width' => 150)),'div class="GroupSettingsImg"');
        }
        $this->Form->addHidden('MAX_FILE_SIZE', 150000);
        echo $this->Form->hidden('MAX_FILE_SIZE', array('value'=> 150000));
        echo $this->Form->input('ImgFile', 'File');
        ?>
    </div>
    <div class="P">
        <?php echo $this->Form->label('Name', 'Name'); ?>
        <?php
        echo wrap($this->Form->textBox('Name'), 'div class="TextBoxWrapper"');
        ?>
    </div>
    <div class="P">
        <?php echo $this->Form->label('Description'); ?>
        <?php echo wrap($this->Form->bodyBox('Description', array('MultiLine' => true)), 'div class="TextBoxWrapper"'); ?>
    </div>
    <div class="P">
        <?php echo wrap($this->Form->button('Request Group',array('class'=>'Button Primary')),'div class="Buttons"'); ?>
    </div>
    <?php
    echo $this->Form->close();
    ?>
    </div>
</div>

