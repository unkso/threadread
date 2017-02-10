/**
 * Namespace for Threadread
 */
WBB.Threadread = {};

/**
 * Threadread write class
 */
WBB.Threadread.Write = Class.extend({

	_container: null,
	_link: null,
	_link_target: null,
	_threadread_max_child: null,
	init: function() {
		this._container = $('.threadread_box').find('.moreWrites');
		if(this._container.length > 0)
		{
			this._link_target = $('.threadread_box').find('.dataList:first');
			this._link = $('<button class="small">'+WCF.Language.get('wbb.threadread.more')+'</button>').appendTo(this._link_target);
			this._link.click($.proxy(this._click, this));
		}
    	},
	
	_click: function() {
		this._threadread_max_child = $('.threadread_max_child');
		
		if(this._container.css('display') === 'none')
		{
			this._threadread_max_child.removeClass('collapsed');
			this._container.css({ display: '' });
			this._link.text( WCF.Language.get('wbb.threadread.less'));
		}
		else
		{
			this._threadread_max_child.addClass('collapsed');
			this._container.css({ display: 'none' });
			this._link.text(WCF.Language.get('wbb.threadread.more'));
		}
	}
});