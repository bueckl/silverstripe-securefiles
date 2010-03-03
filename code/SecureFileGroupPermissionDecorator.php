<?php
/**
 * Creates a group based permission system for files
 *
 * @package securefiles
 * @author Hamish Campbell <hn.campbell@gmail.com>
 * @copyright copyright (c) 2010, Hamish Campbell 
 */
class SecureFileGroupPermissionDecorator extends DataObjectDecorator {
	
	function extraStatics() {
		return array(
			'many_many' => array(
				'GroupPermissions' => 'Group',
			),
		);
	}

	/**
	 * View permission check
	 * 
	 * @param Member $member
	 * @return noolean
	 */
	function canViewSecured(Member $member = null) {
		return $member ? $member->inGroups($this->owner->AllGroupPermissions()) : false;
	}
	
	/**
	 * Collate permissions for this and all parent folders.
	 * 
	 * @return DataObjectSet
	 */
	function AllGroupPermissions() {
		$groupSet = new DataObjectSet();
		$groups = $this->owner->GroupPermissions();
		foreach($groups as $group)
			$groupSet->push($group);
		if($this->owner->ParentID)
			$groupSet->merge($this->owner->InheritedGroupPermissions());
		$groupSet->removeDuplicates();
		return $groupSet;
	}
	
	/**
	 * Collage permissions for all parent folders
	 * 
	 * @return DataObjectSet
	 */
	function InheritedGroupPermissions() {
		if($this->owner->ParentID)
			return $this->owner->Parent()->AllGroupPermissions();
		else
			return new DataObjectSet();
	}
	
	/**
	 * Adds group select fields to CMS
	 * 
 	 * @param FieldSet $fields
 	 * @return void
 	 */
	public function updateCMSFields(FieldSet &$fields) {
		
		// Only modify folder objects with parent nodes
		if(!($this->owner instanceof Folder) || !$this->owner->ID)
			return;
			
		//Only allow ADMIN and SECURE_FILE_SETTINGS members to edit these options
		if(!Permission::checkMember($member, array('ADMIN', 'SECURE_FILE_SETTINGS')))
			return;
			
		$secureFilesTab = $fields->findOrMakeTab('Root.Security');
		$secureFilesTab->push(new HeaderField('Group Access'));
		$secureFilesTab->push(new TreeMultiselectField('GroupPermissions', 'Group Access'));	
			
		if($this->owner->InheritSecured()) {
			$permissionGroups = $this->owner->InheritedGroupPermissions();
			if($permissionGroups->Count()) {
				$fieldText = implode(", ", $permissionGroups->map());
			} else {
				$fieldText = "(None)";
			}
			$InheritedGroupsField = new ReadonlyField("InheritedGroupPermissionsText", "Inherited Group Permissions", $fieldText);
			$secureFilesTab->push($InheritedGroupsField);
		}

	}
	
}


?>