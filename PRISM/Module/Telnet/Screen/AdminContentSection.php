<?php

namespace PRISM\Module\Telnet\Screen;
use PRISM\Module\Telnet\Screen\Section as TSSection;

define('TS_AACTION_ADD', 0);
define('TS_AACTION_EDIT', 1);

class AdminContentSection extends TSSection // TSAdminContentSection
{
    private $actionType		= 0;
    private $username		= '';
    private $userDetails	= array();

    public function __construct(ScreenContainer $parentSection, $actionType, $width, $height, $username, $ttype = 0)
    {
        parent::__construct($parentSection);

        $this->actionType = $actionType;
        $this->setLocation(20, 3);
        $this->setSize($width, $height);
        $this->setTType($ttype);
        $this->setId('adminsContent');
        $this->setBorder(TS_BORDER_REGULAR);

        if ($username) {
            $this->setUsername($username);
            $this->setCaption('Edit admin '.$this->username);
        } else {
            $this->setCaption('Add new administrator');
        }

        $this->createAdminContent();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function handleKey($key)
    {
        switch ($key) {
            case KEY_SHIFTTAB :
            case KEY_CURUP :
                $newItem = $this->previousItem();
                $this->setInputMode();
                break;

            case KEY_TAB :
            case KEY_CURDOWN :
                $newItem = $this->nextItem();
                $this->setInputMode();
                break;

            case KEY_CURRIGHT :
                break;

            case KEY_ENTER :
                switch ($this->getCurObject()->getId()) {
                    case 'adminSave' :
                        $this->adminSave();
                        break;

                    case 'adminDelete' :
                        $this->adminDelete();
                        break;
                }
                break;

            default :
                return false;
        }

        return true;
    }

    protected function setInputMode()
    {
        $object = $this->getCurObject();
        switch ($object->getId()) {
            case 'adminUsername' :
                $this->setInputCallback(
                    $this,
                    'handleAdminInput',
                    TELNET_MODE_LINEEDIT,
                    array(31 + strlen($object->getText()), 6),
                    $object->getText(),
                    23
                );
//				console('Setting username line edit callback');
                break;

            case 'adminPassword' :
                if ($this->actionType == TS_AACTION_ADD) {
                    $this->setInputCallback(
                        $this,
                        'handleAdminInput',
                        TELNET_MODE_LINEEDIT,
                        array(31 + strlen($object->getText()), 10),
                        $object->getText(),
                        24
                    );
                } else {
                    $this->setInputCallback(
                        $this,
                        'handleAdminInput',
                        TELNET_MODE_LINEEDIT,
                        array(31 + strlen($object->getText()), 6),
                        $object->getText(),
                        24
                    );
                }
//				console('Setting password line edit callback');
                break;

            case 'adminFlags' :
                if ($this->actionType == TS_AACTION_ADD) {
                    $this->setInputCallback(
                        $this,
                        'handleAdminInput',
                        TELNET_MODE_LINEEDIT,
                        array(31 + strlen($object->getText()), 14),
                        $object->getText(),
                        26
                    );
                } else {
                    $this->setInputCallback(
                        $this,
                        'handleAdminInput',
                        TELNET_MODE_LINEEDIT,
                        array(31 + strlen($object->getText()), 10),
                        $object->getText(),
                        26
                    );
                }
//				console('Setting flags line edit callback');
                break;

            default :
                $this->setInputCallback(null);
//				console('Setting key edit callback');
                break;
        }

    }

    protected function selectItem()
    {

    }

    private function createAdminContent()
    {
        if ($this->actionType == TS_AACTION_ADD) {
            // New username
            $textArea = new TSTextInput(30, 5, 30, 3);
            $textArea->setId('adminUsername');
            $textArea->setTType($this->getTType());
            $textArea->setMaxLength(23);
            $textArea->setText('');
            $textArea->setOptions(TS_OPT_ISSELECTABLE | TS_OPT_ISEDITABLE);
            $textArea->setBorder(TS_BORDER_REGULAR);
            $textArea->setCaption('LFS Username (exact match)');
            $this->add($textArea);

            // New password
            $textArea = new TSTextInput(30, 9, 30, 3);
            $textArea->setId('adminPassword');
            $textArea->setTType($this->getTType());
            $textArea->setMaxLength(23);
            $textArea->setText('');
            $textArea->setOptions(TS_OPT_ISSELECTABLE | TS_OPT_ISEDITABLE);
            $textArea->setBorder(TS_BORDER_REGULAR);
            $textArea->setCaption('Prism Password');
            $this->add($textArea);

            // Admin flags
            $textArea = new TSTextInput(30, 13, 30, 3);
            $textArea->setId('adminFlags');
            $textArea->setTType($this->getTType());
            $textArea->setMaxLength(26);
            $textArea->setText('abcdetc');
            $textArea->setOptions(TS_OPT_ISSELECTABLE | TS_OPT_ISEDITABLE);
            $textArea->setBorder(TS_BORDER_REGULAR);
            $textArea->setCaption('Permission flags');
            $this->add($textArea);

            // Save
            $textArea = new TSTextArea(30, 17, 12, 3);
            $textArea->setId('adminSave');
            $textArea->setTType($this->getTType());
            $textArea->setText('Save admin');
            $textArea->setOptions(TS_OPT_ISSELECTABLE);
            $textArea->setBorder(TS_BORDER_REGULAR);
            $this->add($textArea);
        } else {
            // New password
            $textArea = new TSTextInput(30, 5, 30, 3);
            $textArea->setId('adminPassword');
            $textArea->setTType($this->getTType());
            $textArea->setText('');
            $textArea->setOptions(TS_OPT_ISSELECTABLE | TS_OPT_ISEDITABLE);
            $textArea->setBorder(TS_BORDER_REGULAR);
            $textArea->setCaption('Prism Password');
            $this->add($textArea);

            // Admin flags
            $textArea = new TSTextInput(30, 9, 30, 3);
            $textArea->setId('adminFlags');
            $textArea->setTType($this->getTType());
            $textArea->setText(flagsToString($this->userDetails['accessFlags']));
            $textArea->setOptions(TS_OPT_ISSELECTABLE | TS_OPT_ISEDITABLE);
            $textArea->setBorder(TS_BORDER_REGULAR);
            $textArea->setCaption('Permission flags');
            $this->add($textArea);

            // Save
            $textArea = new TSTextArea(30, 13, 12, 3);
            $textArea->setId('adminSave');
            $textArea->setTType($this->getTType());
            $textArea->setText('Save admin');
            $textArea->setOptions(TS_OPT_ISSELECTABLE);
            $textArea->setBorder(TS_BORDER_REGULAR);
            $this->add($textArea);

            // Delete
            $textArea = new TSTextArea(30, 17, 14, 3);
            $textArea->setId('adminDelete');
            $textArea->setTType($this->getTType());
            $textArea->setText('Delete admin');
            $textArea->setOptions(TS_OPT_ISSELECTABLE);
            $textArea->setBorder(TS_BORDER_REGULAR);
            $this->add($textArea);
        }

    }

    public function setUsername($username)
    {
        global $PRISM;

        $this->username = $username;
        $this->userDetails = $PRISM->admins->getAdminInfo($username);
    }

    public function getUsername()
    {
        return $this->username;
    }

    private function adminSave()
    {
        global $PRISM;
        // Collect data from input fields
        $username	= ($this->actionType == TS_AACTION_ADD) ? $this->getObjectById('adminUsername')->getText() : $this->username;
        $password	= $this->getObjectById('adminPassword')->getText();
        $flags		= $this->getObjectById('adminFlags')->getText();

        // Save admin
        if ($PRISM->admins->adminExists($username)) {
            // Update admin
            if ($password != '')
                $PRISM->admins->changePassword($username, $password);
            $PRISM->admins->setAccessFlags($username, flagsToInteger($flags));
        } else {
            // New admin
            if ($username == '' || $password == '')
                return;
            $PRISM->admins->addAccount($username, $password, flagsToInteger($flags));
            $PRISM->admins->setAccessFlags($username, flagsToInteger($flags));
        }

        $this->parentSection->redrawMenu();

//		console('Save this admin '.$username.' / '.$password.' / '.$flags);
    }

    private function adminDelete()
    {
        global $PRISM;

        if ($this->username)
            $PRISM->admins->deleteAccount($this->username);

        $this->parentSection->redrawMenu();

//		console('Delete this admin '.$this->username);
    }

    public function handleAdminInput($line)
    {
        $this->getCurObject()->setText($line);
        $this->setInputMode();
//		console('handleAdminInput ('.$this->getCurObject()->getId().') received a line : '.$line);
    }
}
