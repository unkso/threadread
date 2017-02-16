{include file='documentHeader'}

<head>
	<title>{$thread->topic} - {lang}wbb.thread.read.pagetitle{/lang} - {PAGE_TITLE|language}</title>
	
	{include file='headInclude'}
</head>

<body id="tpl{$templateName|ucfirst}" data-template="{$templateName}" data-application="{$templateNameApplication}">

{include file='header' title='{lang}wbb.thread.read.pagetitle{/lang}' light=true}

<div class="container">

    {include file='userNotice'}

    {event name='afterUserNotice'}

    <div class="action-bar top clearfix wbbThread">
	<div class="buttons">
		<a href="{link controller='Thread' id=$threadID}{/link}" class="btn btn-primary"><i class="fa {lang}wbb.thread.read.back.icon{/lang}"></i> <span>{lang}wbb.thread.read.back{/lang}</span></a>
	</div>
    </div>

    <section class="timeline" id="timeline">
    	{foreach from=$history item=entries key=primarydate}
    		{assign var='date' value=$primarydate}
    		<div class="timeline-date">
    			<h3 class="heading-primary">{$primarydate}</h3>
    		</div>
    		
    		{foreach from=$entries item=entry}
	    		{assign var='type' value=$entry['type']}
	    		{if $type=='visits'}
	    			{if $entry['users']|count > 0}
	    				<article class="timeline-box right">
	    					<div class="portfolio-item">
	    						<h4>{lang}wbb.thread.read.viewedby{/lang}</h4>
	    						<p><ul class="dataList">
				    			{foreach from=$entry['users'] item=user}
				    				<li>{$user['username']}</li>
				    			{/foreach}
			    				</ul></p>
			    			</div>
		    			</article>
	    			{/if}
	    		{else}
	    			{assign var='username' value=$entry['username']}
	    			{assign var='message' value=$entry['message']}
	    			{assign var='postID' value=$entry['postID']}
	    			<article class="timeline-box left">
	    				<div class="portfolio-item">
	    					<h4><a href="{link controller='Thread' id=$threadID postID=$postID}{/link}#post{$postID}">{lang}wbb.thread.read.postby{/lang}{$username}</a></h4>
	    					<p>{$message}</p>
	    				</div>
	    			</article>
	    		{/if}
    		{/foreach}
    	{/foreach}

    </section>
    
    <div class="action-bar bottom clearfix" style="margin-top:21px;">
	<div class="buttons">
		<a href="{link controller='Thread' id=$threadID}{/link}" class="btn btn-primary"><i class="fa {lang}wbb.thread.read.back.icon{/lang}"></i> <span>{lang}wbb.thread.read.back{/lang}</span></a>
	</div>
    </div>

</div>

{include file='footer' skipBreadcrumbs=false}

</body>
</html>