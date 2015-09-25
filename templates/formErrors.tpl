{**
 * @file templates/formErrors.tpl
 *
 * Author: Božana Bokan, Center for Digital Systems (CeDiS), Freie Universität Berlin
 * Last update: September 25, 2015
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * List errors that occurred during VG Wort form processing.
 *}
{if $isError}
	<div id="formErrors">
		<p>
		<span class="pkp_form_error">{translate key="plugins.generic.vgWort.errorsOccurred"}:</span>
		<ul class="pkp_form_error_list">
		{foreach key=field item=message from=$errors}
			<li>{$message}</li>
		{/foreach}
		</ul>
		</p>
	</div>
	<script type="text/javascript">{literal}
		<!--
		// Jump to form errors.
		window.location.hash="formErrors";
		// -->
	{/literal}</script>
{/if}

