<?php if (!defined('APPLICATION')) exit();
echo '<div class="DataListWrap">';

$Session = Gdn::session();
if ($Session->isValid() && checkPermission('Garden.Profiles.Edit')) {
    $this->fireEvent('BeforeStatusForm');
    $ButtonText = 'Add Comment';


    echo '<div class="FormWrapper FormWrapper-Condensed">';
    echo $this->Form->open(array('action' => url($this->data('ActivityUrl')), 'class' => 'Activity'));
    echo $this->Form->errors();
    echo $this->Form->bodyBox('Comment', array('Wrap' => TRUE));
    echo '<div class="Buttons">';
    echo $this->Form->button($ButtonText, array('class' => 'Button Primary'));
    echo '</div>';
    echo $this->Form->close();
    echo '</div>';
}

// Include the activities
include($this->fetchViewLocation('index', 'activity', 'dashboard'));
echo '</div>';
