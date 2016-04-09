<?php if (!defined('APPLICATION')) exit(); ?>
<h1><?php echo $this->data('Title'); ?></h1>
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
?>
<div class="Configuration">
   <div class="ConfigurationForm">
    <ul>
        <li>
            <?php echo $this->Form->label('Image'); ?>
        </li>
        <li>
            <?php
            if ($this->data('group.Picture')) {
                echo wrap(img('/uploads/mygroups/'.$this->data('group.Picture'), array('width' => 150)),'div class="GroupSettingsImg"');
            }
            $this->Form->addHidden('MAX_FILE_SIZE', 150000);
            echo $this->Form->hidden('MAX_FILE_SIZE', array('value'=> 150000));
            echo $this->Form->input('ImgFile', 'File');
            ?>
        </li>
        <li>
            <?php echo $this->Form->label('Name', 'Name'); ?>
        </li>
        <li>
            <?php
            echo $this->Form->textBox('Name');
            ?>
        </li>
        <li>
            <?php echo $this->Form->label('Description'); ?>
        </li>
        <li>
            <?php echo $this->Form->textBox('Description', array('MultiLine' => true)); ?>
        </li>
        <li>
            <?php echo $this->Form->Button('Save',array('class'=>'SmallButton')); ?>
        </li>
    </ul>
   </div>
</div>
 <?php
      echo $this->Form->Close();
