<div id="leftmenu">
<div id="leftmenu-design">

<div class="objectinfo">

<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h4>{'Object information'|i18n( 'design/admin/content/upload' )}</h4>

</div></div></div></div></div></div>

<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-br"><div class="box-bl"><div class="box-content">

{let content_object=fetch( content, object, hash( object_id, $upload.content.object_id  ) )
     content_version=fetch( content, version, hash( object_id, $upload.content.object_id, version_id, $upload.content.object_version ) )}
<p>
<label>{'ID'|i18n( 'design/admin/content/upload' )}:</label>
{$content_object.id}
</p>

<p>
<label>{'Created'|i18n( 'design/admin/content/upload' )}:</label>
{section show=$content_object.published}
{$content_object.published|l10n( shortdatetime )}<br />
{$content_object.current.creator.name}
{section-else}
{'Not yet published'|i18n( 'design/admin/content/upload' )}
{/section}
</p>

<p>
<label>{'Modified'|i18n( 'design/admin/content/upload' )}:</label>
{section show=$content_object.modified}
{$content_object.modified|l10n( shortdatetime )}<br />
{fetch( content, object, hash( object_id, $content_object.content_class.modifier_id ) ).name}
{section-else}
{'Not yet published'|i18n( 'design/admin/content/upload' )}
{/section}
</p>

<p>
<label>{'Published version'|i18n( 'design/admin/content/upload' )}:</label>
{section show=$content_object.published}
{$content_object.current.version}
{section-else}
{'Not yet published'|i18n( 'design/admin/content/upload ' )}
{/section}
</p>


{* Manage versions. *}
<div class="block">
<input class="button-disabled" type="submit" name="" value="{'Manage versions'|i18n( 'design/admin/content/upload' )}" disabled="disabled" />
</div>

</div></div></div></div></div></div>

</div>

<br />

<div class="drafts">

<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h4>{'Current draft'|i18n( 'design/admin/content/upload' )}</h4>

</div></div></div></div></div></div>

<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-bl"><div class="box-br"><div class="box-content">

{* Created. *}
<p>
<label>{'Created'|i18n( 'design/admin/content/upload' )}:</label>
{$content_version.created|l10n( shortdatetime )}<br />
{$content_version.creator.name}
</p>

{* Modified. *}
<p>
<label>{'Modified'|i18n( 'design/admin/content/upload' )}:</label>
{$content_version.modified|l10n( shortdatetime )}<br />
{$content_version.creator.name}
</p>

{* Version. *}
<p>
<label>{'Version'|i18n( 'design/admin/content/upload' )}:</label>
{$content_version.version}
</p>

</div></div></div></div></div></div>

</div>

</div>
</div>

<div id="maincontent"><div id="fix">
<div id="maincontent-design">
<!-- Maincontent START -->


<form method="post" action={'/content/removeassignment/'|ezurl}>

<div class="context-block">

{* DESIGN: Header START *}<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">

<h2 class="context-title">{'Removal of locations'|i18n( 'design/admin/content/removeassignment' )}</h2>

{* DESIGN: Mainline *}<div class="header-mainline"></div>

{* DESIGN: Header END *}</div></div></div></div></div></div>

{* DESIGN: Content START *}<div class="box-ml"><div class="box-mr"><div class="box-content">

<p>{'Some of the locations that are about to be removed have sub items.'|i18n( 'design/admin/content/removeassignment' )}</p>
<p>{'Removing these locations will also result in the removal of the items below them.'|i18n( 'design/admin/content/removeassignment' )}</p>
<p>{'Are you sure you want to remove these locations along with their contents?'|i18n( 'design/admin/content/removeassignment' )}</p>

<table class="list" cellspacing="0">
<tr>
    <th>{'Location'|i18n( 'design/admin/content/removeassignment' )}</th>
    <th>{'Sub items'|i18n( 'design/admin/content/removeassignment' )}</th>
</tr>

{section var=remove_item loop=$remove_list sequence=array( bglight, bgdark )}
<tr class="{$remove_item.sequence}">
    <td>

    {* $remove_item.node.object.class_identifier|class_icon( small, $remove_item.node.object.class_name )*}{section var=path_node loop=$remove_item.node.path}{$path_node.name|wash}{delimiter} / {/delimiter}{/section}</td>
    <td>{section show=$remove_item.count|eq( 1 )}{$remove_item.count} item{section-else}{$remove_item.count} items{/section}</td>
</tr>
{/section}
</table>

{* DESIGN: Content END *}</div></div></div>


<div class="controlbar">
{* DESIGN: Control bar START *}<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
<div class="block">
<input type="submit" class="button" name="ConfirmRemovalButton" value="{'OK'|i18n( 'design/admin/content/removeassignment' )}" title="{'Remove the locations along with their contents.'|i18n( 'design/admin/content/removeassignment' )}" />
<input type="submit" class="button" name="CancelRemovalButton" value="{'Cancel'|i18n( 'design/admin/content/removeassignment' )}" title="{'Cancel the removal operation and go back to the edit page.'|i18n( 'design/admin/content/removeassignment' )}" />
</div>
{* DESIGN: Control bar END *}</div></div></div></div></div></div>
</div>

</div>

</form>


<!-- Maincontent END -->
</div>
<div class="break"></div>
</div></div>
