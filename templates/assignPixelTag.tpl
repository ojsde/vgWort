{**
 * plugins/generic/vgWort/templates/assignPixelTag.tpl
 *
 * Copyright (c) 2018 Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Distributed under the GNU GPL v2. For full terms see the file LICENSE.
 *
 * Possibility to assign a pixel tag to an article, as well as to controll the pixel tag assignment to galleys
 *
 *}
<!-- VG Wort -->
{fbvFormArea id="vgWortAssignPixelFormArea" class="border" title="plugins.generic.vgWort.pixelTag"}
	{if $pubObject instanceof Submission}
		{if !$pixelTag}
			{fbvFormSection}
				<p class="pkp_help">{translate key="plugins.generic.vgWort.pixelTag.textType.description"}</p>
				{fbvElement type="select" id="vgWortTextType" name="vgWortTextType" from=$typeOptions selected=$vgWortTextType label="plugins.generic.vgWort.pixelTag.textType" translate=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
			{fbvFormSection list=true}
				{fbvElement type="checkbox" id="vgWortAssignPixel" name="vgWortAssignPixel" value="1" checked=true label="plugins.generic.vgWort.pixelTag.assign"}
			{/fbvFormSection}
		{else}
			{fbvFormSection}
			<div class="item pixelTag">
				<div class="sub_item">
					<span class="label">
						{capture assign=privateCode}{translate key="plugins.generic.vgWort.pixelTag.privateCode"}{/capture}
						{translate key="semicolon" label=$privateCode}
					</span>
					<span class="value">{$pixelTag->getPrivateCode()|escape}</span>
				</div>
				<div class="sub_item">
					<span class="label">
						{capture assign=publicCode}{translate key="plugins.generic.vgWort.pixelTag.publicCode"}{/capture}
						{translate key="semicolon" label=$publicCode}
					</span>
					<span class="value">{$pixelTag->getPublicCode()|escape}</span>
				</div>
				<div class="sub_item">
					<span class="label">
						{capture assign=dateAssigned}{translate key="plugins.generic.vgWort.pixelTag.dateAssigned"}{/capture}
						{translate key="semicolon" label=$dateAssigned}
					</span>
					<span class="value">{$pixelTag->getDateAssigned()|date_format:$dateFormatShort|default:"&mdash;"}</span>
				</div>
				<div class="sub_item">
					<span class="label">
						{capture assign=dateRemoved}{translate key="plugins.generic.vgWort.pixelTag.dateRemoved"}{/capture}
						{translate key="semicolon" label=$dateRemoved}
					</span>
					<span class="value">{$pixelTag->getDateRemoved()|date_format:$dateFormatShort|default:"&mdash;"}</span>
				</div>
				<div class="sub_item">
					<span class="label">
						{capture assign=dateRegistered}{translate key="plugins.generic.vgWort.pixelTag.dateRegistered"}{/capture}
						{translate key="semicolon" label=$dateRegistered}
					</span>
					<span class="value">{$pixelTag->getDateRegistered()|date_format:$dateFormatShort|default:"&mdash;"}</span>
				</div>
				<div class="sub_item">
					<span class="label">
						{capture assign=status}{translate key="plugins.generic.vgWort.pixelTag.status"}{/capture}
						{translate key="semicolon" label=$status}
					</span>
					<span class="value">{$pixelTag->getStatusString()|escape}</span>
				</div>
				<div class="sub_item">
					<span class="label">
						{capture assign=message}{translate key="plugins.generic.vgWort.pixelTag.message"}{/capture}
						{translate key="semicolon" label=$message}
					</span>
					<span class="value">{$pixelTag->getMessage()|escape|default:"&mdash;"}</span>
				</div>
			</div>
			{/fbvFormSection}
			{fbvFormSection}
				<p class="pkp_help">{translate key="plugins.generic.vgWort.pixelTag.textType.description"}</p>
				{fbvElement type="select" id="vgWortTextType" name="vgWortTextType" from=$typeOptions selected=$vgWortTextType label="plugins.generic.vgWort.pixelTag.textType" translate=true size=$fbvStyles.size.SMALL}
			{/fbvFormSection}
			{if $pixelTag->getDateRemoved()}
				<p>{translate key="plugins.generic.vgWort.pixelTag.removed"}</p>
				{fbvFormSection list=true}
					{fbvElement type="checkbox" id="vgWortAssignPixel" name="vgWortAssignPixel" value="1" checked=true label="plugins.generic.vgWort.pixelTag.assign"}
				{/fbvFormSection}
			{else}
				{fbvFormSection list=true}
					{fbvElement type="checkbox" id="removeVGWortPixel" name="removeVGWortPixel" value="1" label="plugins.generic.vgWort.pixelTag.remove"}
				{/fbvFormSection}
			{/if}
		{/if}
	{elseif $pubObject instanceof Representation}
		{if !$pixelTag}
			{fbvFormSection}
				<p>{translate key="plugins.generic.vgWort.pixelTag.representation.notAssigned"}</p>
			{/fbvFormSection}
		{else}	
			{if $pixelTag->getDateRemoved()}
				{fbvFormSection}
					<p>{translate key="plugins.generic.vgWort.pixelTag.removed"}</p>
				{/fbvFormSection}
			{elseif $galleyNotSupported}
				{fbvFormSection}
					<p>{translate key="plugins.generic.vgWort.pixelTag.representation.notSupported"}</p>
				{/fbvFormSection}
			{else}
				{if $pubObject->getData('excludeVGWortAssignPixel')}
					<p>{translate key="plugins.generic.vgWort.pixelTag.representation.excluded"}</p>
				{else}
					<p>{translate key="plugins.generic.vgWort.pixelTag.representation.assigned"}</p>
				{/if}
				{fbvFormSection list=true}
					{fbvElement type="checkbox" id="excludeVGWortAssignPixel" name="excludeVGWortAssignPixel" value="1" checked=$excludeVGWortAssignPixel|compare:true label="plugins.generic.vgWort.pixelTag.representation.exclude"}
				{/fbvFormSection}
			{/if}
		{/if}
	{/if}
{/fbvFormArea}
<!-- /VG Wort -->

