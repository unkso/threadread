{if WBB_THREADREAD_ACTIVE && $__wcf->getSession()->getPermission('user.threadread.canView')}
	{if $whoReadUsers|isset}
		<script data-relocate="true" src="{@$__wcf->getPath('wbb')}js/WBB.threadread.js?v={@$__wcfVersion}"></script>
		<script data-relocate="true">
			//<![CDATA[
			$(function () {
				WCF.Language.addObject({
					'wbb.threadread.more': '{lang}wbb.threadread.more{/lang}',
					'wbb.threadread.less': '{lang}wbb.threadread.less{/lang}'
				});
				new WBB.Threadread.Write();
			});
			//]]>
		</script>
		
		<style>
		{if WBB_THREADREAD_MAX_ENABLE && $whoReadUsers|count > WBB_THREADREAD_MAX}
		ul.dataList li.collapsed::after,
		ul.dataList li:nth-last-child(2)::after {
			content: "" !important;
		}
		{/if}
		.dividedBottomLarge {
			margin-bottom: 21px;
			padding-bottom: 21px;
			border-bottom: solid 1px #dddddd;
		}
		</style>
		
		<div class="threadread_box marginTopLarge">
			<h3 class="big">{lang}wbb.thread.read.whoread{/lang}</h3>
			<small>
				<span>{lang}wbb.threadread.readcount{/lang}</span>
				<span style="margin-left:5px;">{lang}wbb.threadread.viewcount{/lang}</span>
				<span style="margin-left:5px;">
					<a href="{link controller='ThreadRead' id=$threadID}{/link}" class="label label-default jsTooltip">{lang}wbb.thread.read.gotimeline{/lang}</a>
				</span>
			</small>
			<p class="marginTop"><ul class="dataList">
				{if WBB_THREADREAD_MAX_ENABLE}
					{assign var=counter value=1}
					{foreach from=$whoReadUsers item='whoReadUser' key='key'}
						{assign var=userID value=$whoReadUser['userID']}
						{assign var=username value=$whoReadUser['username']}
						{assign var=firstvisit value=$whoReadUser['firstvisit']}
						{assign var=lastvisit value=$whoReadUser['lastvisit']}
						
						{if $counter <= WBB_THREADREAD_MAX}
							<li {if $counter == WBB_THREADREAD_MAX}class="threadread_max_child collapsed"{/if}>
							{if $whoReadUser['userID'] != 0}
								<span class="jsTooltip" title="last viewed: {$lastvisit} (first viewed: {$firstvisit})">{$username}</span>
							{/if}
							</li>
						{else}
							<li class="moreWrites" style="display:none;">
								{if $whoReadUser['userID'] != 0}
									<span class="jsTooltip" title="last viewed: {$lastvisit} (first viewed: {$firstvisit})">{$username}</span>
								{/if}
							</li>
						{/if}
						{assign var=counter value=$counter + 1}
					{/foreach}
				{else}
					{foreach from=$whoReadUsers item='whoReadUser' key='key'}
						{assign var=userID value=$whoReadUser['userID']}
						{assign var=username value=$whoReadUser['username']}
						{assign var=firstvisit value=$whoReadUser['firstvisit']}
						{assign var=lastvisit value=$whoReadUser['lastvisit']}
						
						<li>
							{if $whoReadUser['userID'] != 0}
								<span class="jsTooltip" title="last viewed: {$lastvisit} (first viewed: {$firstvisit})">{$username}</span>
							{/if}
						</li>
					{/foreach}
				{/if}
				
				
			</ul></p>
		</div>
	{else}
		<div class="threadread_box marginTopLarge">
			<h3 class="big">{lang}wbb.thread.read.whoread{/lang}</h3>
			<small>
				<span>0 users have viewed this thread</span>
				<span style="margin-left:5px;">{lang}wbb.threadread.viewcount{/lang}</span>
			</small>
		</div>
	{/if}
{/if}