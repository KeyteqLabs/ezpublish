<?php
//
// Created on: <17-Apr-2002 10:34:48 bf>
//
// Copyright (C) 1999-2003 eZ systems as. All rights reserved.
//
// This source file is part of the eZ publish (tm) Open Source Content
// Management System.
//
// This file may be distributed and/or modified under the terms of the
// "GNU General Public License" version 2 as published by the Free
// Software Foundation and appearing in the file LICENSE.GPL included in
// the packaging of this file.
//
// Licencees holding valid "eZ publish professional licences" may use this
// file in accordance with the "eZ publish professional licence" Agreement
// provided with the Software.
//
// This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
// THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
// PURPOSE.
//
// The "eZ publish professional licence" is available at
// http://ez.no/home/licences/professional/. For pricing of this licence
// please contact us via e-mail to licence@ez.no. Further contact
// information is available at http://ez.no/home/contact/.
//
// The "GNU General Public License" (GPL) is available at
// http://www.gnu.org/copyleft/gpl.html.
//
// Contact licence@ez.no if any conditions of this licencing isn't clear to
// you.
//

/*!
  \file attribute_edit.php
  This file is a shared code file which is used by different parts of the system
  to edit objects. This file only implements editing of attributes and uses
  hooks to allow external code to add functionality.
  \param $Module must be set by the code which includes this file
*/

include_once( 'kernel/classes/ezcontentclass.php' );
include_once( 'kernel/classes/ezcontentclassattribute.php' );

include_once( 'kernel/classes/ezcontentobject.php' );
include_once( 'kernel/classes/ezcontentobjectversion.php' );
include_once( 'kernel/classes/ezcontentobjectattribute.php' );

include_once( 'lib/ezutils/classes/ezhttptool.php' );

include_once( 'kernel/common/template.php' );

if ( isset( $Module ) )
    $Module =& $Params['Module'];
$ObjectID =& $Params['ObjectID'];
if ( !isset( $EditVersion ) )
    $EditVersion =& $Params['EditVersion'];
if ( !isset( $EditLanguage ) )
    $EditLanguage = $Params['EditLanguage'];
if ( !is_string( $EditLanguage ) or
     strlen( $EditLanguage ) == 0 )
    $EditLanguage = false;
if ( $EditLanguage == eZContentObject::defaultLanguage() )
    $EditLanguage = false;

if ( $Module->runHooks( 'pre_fetch', array( $ObjectID, &$EditVersion, &$EditLanguage ) ) )
    return;

$object =& eZContentObject::fetch( $ObjectID );
if ( $object === null )
    return $Module->handleError( EZ_ERROR_KERNEL_NOT_AVAILABLE, 'kernel' );

$version =& $object->version( $EditVersion );
$classID = $object->attribute( 'contentclass_id' );

$class =& eZContentClass::fetch( $classID );
$contentObjectAttributes =& $version->contentObjectAttributes( $EditLanguage );
if ( $contentObjectAttributes === null or
     count( $contentObjectAttributes ) == 0 )
    $contentObjectAttributes =& $version->contentObjectAttributes();

$http =& eZHTTPTool::instance();

if ( $Module->runHooks( 'post_fetch', array( &$class, &$object, &$version, &$contentObjectAttributes, $EditVersion, $EditLanguage ) ) )
    return;

// Checking if object has at least one placement, if not user needs to choose it from browse page
$assignments =& $version->attribute( 'parent_nodes' );
if ( count( $assignments ) < 1 && $Module->isCurrentAction( 'Publish' ) )
{
    $Module->setCurrentAction( 'BrowseForNodes' );
}
//----------


$validation = array( 'processed' => false,
                     'attributes' => array(),
                     'placement' => array() );

/********** Custom Action Code Start ***************/
$customAction = false;
$customActionAttributeArray = array();
// Check for custom actions
if ( $http->hasPostVariable( "CustomActionButton" ) )
{
    $customActionArray = $http->postVariable( "CustomActionButton" );
    foreach ( $customActionArray as $customActionKey => $customActionValue )
    {
        $customActionString = $customActionKey;

        $customActionAttributeID = preg_match( "#^([0-9]+)_(.*)$#", $customActionString, $matchArray );
        $customActionAttributeID = $matchArray[1];
        $customAction = $matchArray[2];
        $customActionAttributeArray[$customActionAttributeID] = array( 'id' => $customActionAttributeID,
                                                                       'value' => $customAction );
    }
}
/********** Custom Action Code End ***************/
$storeActions = array( 'Preview',
                       'Translate',
                       'VersionEdit',
                       'Apply',
                       'Publish',
                       'Store',
                       'Discard',
                       'CustomAction',
                       'EditLanguage',
                       'BrowseForObjects',
                       'NewObject',
                       'BrowseForNodes',
                       'DeleteRelation',
                       'DeleteNode',
                       'MoveNode' );
$storingAllowed = in_array( $Module->currentAction(), $storeActions );

// These variables will be modified according to validation
$inputValidated = true;
$requireFixup = false;
$validatedAttributesLog = array();

if ( $storingAllowed )
{
    // Validate input
    include_once( 'lib/ezutils/classes/ezinputvalidator.php' );
    $unvalidatedAttributes = array();
    foreach( array_keys( $contentObjectAttributes ) as $key )
    {
        $contentObjectAttribute =& $contentObjectAttributes[$key];
        $contentClassAttribute =& $contentObjectAttribute->contentClassAttribute();
        $status = $contentObjectAttribute->validateInput( $http, 'ContentObjectAttribute' );

        if ( $status == EZ_INPUT_VALIDATOR_STATE_INTERMEDIATE )
            $requireFixup = true;
        else if ( $status == EZ_INPUT_VALIDATOR_STATE_INVALID )
        {
            $inputValidated = false;
            $dataType =& $contentObjectAttribute->dataType();
            $attributeName = $dataType->attribute( 'information' );
            $attributeName = $attributeName['name'];
            $unvalidatedAttributes[] = array( 'id' => $contentObjectAttribute->attribute( 'id' ),
                                              'identifier' => $contentClassAttribute->attribute( 'identifier' ),
                                              'name' => $contentClassAttribute->attribute( 'name' ),
                                              'description' => $contentObjectAttribute->attribute( 'validation_error' ) );
        }
        else if ( $status == EZ_INPUT_VALIDATOR_STATE_ACCEPTED )
        {
//             $inputValidated = true;
            $dataType =& $contentObjectAttribute->dataType();
            $attributeName = $dataType->attribute( 'information' );
            $attributeName = $attributeName['name'];
            if ( $contentObjectAttribute->attribute( 'validation_log' ) != null )
            {
                $validatedAttributesLog[] = array(  'id' => $contentObjectAttribute->attribute( 'id' ),
                                                    'identifier' => $contentClassAttribute->attribute( 'identifier' ),
                                                    'name' => $contentClassAttribute->attribute( 'name' ),
                                                    'description' => $contentObjectAttribute->attribute( 'validation_log' ) );
            }
        }
    }

    // Fixup input
    if ( $requireFixup )
    {
        foreach ( array_keys( $contentObjectAttributes ) as $key )
        {
            $contentObjectAttribute =& $contentObjectAttributes[$key];
            $contentObjectAttribute->fixupInput( $http, 'ContentObjectAttribute' );
        }
    }
    $requireStoreAction= false;
    // If no redirection uri we assume it's content/edit
    if ( !isset( $currentRedirectionURI ) )
        $currentRedirectionURI = $Module->redirectionURI( 'content', 'edit', array( $ObjectID, $EditVersion, $EditLanguage ) );
    foreach( array_keys( $contentObjectAttributes ) as $key )
    {
        $contentObjectAttribute =& $contentObjectAttributes[$key];
        if ( $contentObjectAttribute->fetchInput( $http, "ContentObjectAttribute" ) )
        {
            $requireStoreAction= true;
        }
/********** Custom Action Code Start ***************/
        if ( isset( $customActionAttributeArray[$contentObjectAttribute->attribute( "id" )] ) )
        {
            $customActionAttributeID = $customActionAttributeArray[$contentObjectAttribute->attribute( "id" )]['id'];
            $customAction = $customActionAttributeArray[$contentObjectAttribute->attribute( "id" )]['value'];
            $contentObjectAttribute->customHTTPAction( $http, $customAction, array( 'module' => &$Module,
                                                                                    'current-redirection-uri' => $currentRedirectionURI ) );
        }
/********** Custom Action Code End ***************/

    }

    if ( $Module->isCurrentAction( 'Discard' ) )
    {
        $inputValidated = true;
    }

    if ( $inputValidated and $requireStoreAction )
    {
        if ( $Module->runHooks( 'pre_commit', array( &$class, &$object, &$version, &$contentObjectAttributes, $EditVersion, $EditLanguage ) ) )
            return;

        include_once( 'lib/ezlocale/classes/ezdatetime.php' );
        $version->setAttribute( 'modified', eZDateTime::currentTimeStamp() );
        $version->setAttribute( 'status', EZ_VERSION_STATUS_DRAFT );
        $version->store();

        // Tell attributes to store themselves if necessary
        foreach( array_keys( $contentObjectAttributes ) as $key )
        {
            $contentObjectAttribute =& $contentObjectAttributes[$key];
            $contentObjectAttribute->store();
        }
    }

    $validation['processed'] = true;
    $validation['attributes'] = $unvalidatedAttributes;

}

if ( $Module->isCurrentAction( 'Publish' ) )
{
    $mainFound = false;
    $assignments =& $version->attribute( 'parent_nodes' );
    foreach ( array_keys( $assignments ) as $key )
    {
        if ( $assignments[$key]->attribute( 'is_main' ) == 1 )
        {
            $mainFound = true;
            break;
        }
    }
    if ( !$mainFound and count( $assignments ) > 0 )
    {
        $validation[ 'placement' ][] = array( 'text' => ezi18n( 'kernel/content', 'No main node selected, please select one.' ) );
        $validation[ 'processed' ] = true;
        $inputValidated = false;
        eZDebugSetting::writeDebug( 'kernel-content-edit', "placement is not validated" );
    }
    else
        eZDebugSetting::writeDebug( 'kernel-content-edit', "placement is validated" );

}

// After the object has been validated we can check for other actions

if ( $inputValidated == true )
{
    if ( $Module->runHooks( 'action_check', array( &$class, &$object, &$version, &$contentObjectAttributes, $EditVersion, $EditLanguage ) ) )
        return;
}

if ( isset( $Params['TemplateObject'] ) )
    $tpl =& $Params['TemplateObject'];

if ( !isset( $tpl ) || get_class( $tpl ) != 'eztemplate' )
    $tpl =& templateInit();

$tpl->setVariable( 'validation', $validation );
$tpl->setVariable( 'validation_log', $validatedAttributesLog );


$Module->setTitle( 'Edit ' . $class->attribute( 'name' ) . ' - ' . $object->attribute( 'name' ) );
$res =& eZTemplateDesignResource::instance();

$res->setKeys( array( array( 'object', $object->attribute( 'id' ) ), // Object ID
                      array( 'class', $class->attribute( 'id' ) ) // Class ID
                      ) ); // Section ID

if ( !isset( $OmitSectionSetting ) )
    $OmitSectionSetting = false;
if ( $OmitSectionSetting !== true )
{
    include_once( 'kernel/classes/ezsection.php' );
    eZSection::setGlobalID( $object->attribute( 'section_id' ) );
}

$tpl->setVariable( 'edit_version', $EditVersion );
$tpl->setVariable( 'edit_language', $EditLanguage );
$tpl->setVariable( 'content_version', $version );
$tpl->setVariable( 'http', $http );
$tpl->setVariable( 'content_attributes', $contentObjectAttributes );
$tpl->setVariable( 'class', $class );
$tpl->setVariable( 'object', $object );
if ( $Module->runHooks( 'pre_template', array( &$class, &$object, &$version, &$contentObjectAttributes, $EditVersion, $EditLanguage, &$tpl ) ) )
    return;
$templateName = 'design:content/edit.tpl';

if ( isset( $Params['TemplateName'] ) )
    $templateName = $Params['TemplateName'];

$Result = array();
$Result['content'] =& $tpl->fetch( $templateName );
$Result['path'] = array( array( 'text' => ezi18n( 'kernel/content', 'Content' ),
                                'url' => false ),
                         array( 'text' => ezi18n( 'kernel/content', 'Edit' ),
                                'url' => false ),
                         array( 'text' => $object->attribute( 'name' ),
                                'url' => false ) );
// Fetch the navigation part from the section information
include_once( 'kernel/classes/ezsection.php' );
$section =& eZSection::fetch( $object->attribute( 'section_id' ) );
if ( $section )
    $Result['navigation_part'] = $section->attribute( 'navigation_part_identifier' );

?>
