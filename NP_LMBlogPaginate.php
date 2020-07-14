<?php
/*
    LMBlogPaginate Nucleus plugin
    Copyright (C) 2012-2013 Leo (www.slightlysome.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
	(http://www.gnu.org/licenses/gpl-2.0.html)
	
	See lmblogpaginate/help.html for plugin description, install, usage and change history.
*/
class NP_LMBlogPaginate extends NucleusPlugin
{
	var $pageParm;
	var $urlPartTypeId;
	
	// name of plugin 
	function getName()
	{
		return 'LMBlogPaginate';
	}

	// author of plugin
	function getAuthor()
	{
		return 'Leo (www.slightlysome.net)';
	}

	// an URL to the plugin website
	// can also be of the form mailto:foo@bar.com
	function getURL()
	{
		return 'http://www.slightlysome.net/nucleus-plugins/np_lmblogpaginate';
	}

	// version of the plugin
	function getVersion()
	{
		return '1.0.1';
	}

	// a description to be shown on the installed plugins listing
	function getDescription()
	{
		return 'Paginate the blog items on the index page and show a list of links that points to various pages of blog items.';
	}

	function supportsFeature ($what)
	{
		switch ($what)
		{
			case 'SqlTablePrefix':
				return 1;
			case 'SqlApi':
				return 1;
			case 'HelpPage':
				return 1;
			default:
				return 0;
		}
	}
	
	function hasAdminArea()
	{
		return 1;
	}
	
	function getMinNucleusVersion()
	{
		return '360';
	}
	
	function getTableList()
	{	
		return 	array();
	}
	
	function getEventList() 
	{ 
		return array('AdminPrePageFoot', 'TemplateExtraFields', 'PostParseURL', 'PreBlogContent', 'PostBlogContent'); 
	}
	
	function getPluginDep() 
	{
		return array('NP_LMReplacementVars');
	}

	function install()
	{
		$sourcedataversion = $this->getDataVersion();

		$this->upgradeDataPerform(1, $sourcedataversion);
		$this->setCurrentDataVersion($sourcedataversion);
		$this->upgradeDataCommit(1, $sourcedataversion);
		$this->setCommitDataVersion($sourcedataversion);					
	}
	
	function unInstall()
	{
		global $manager;
		
		if ($this->getOption('del_uninstall') == 'yes')	
		{
			foreach ($this->getTableList() as $table) 
			{
				sql_query("DROP TABLE IF EXISTS ".$table);
			}
			
			$typeid = $this->_getURLPartTypeId();
			if($typeid) $this->_getURLPartPlugin()->removeType($typeid);
		}
	}

	function event_AdminPrePageFoot(&$data)
	{
		// Workaround for missing event: AdminPluginNotification
		$data['notifications'] = array();
			
		$this->event_AdminPluginNotification($data);
			
		foreach($data['notifications'] as $aNotification)
		{
			echo '<h2>Notification from plugin: '.htmlspecialchars($aNotification['plugin'], ENT_QUOTES, _CHARSET).'</h2>';
			echo $aNotification['text'];
		}
	}
	
	////////////////////////////////////////////////////////////
	//  Events
	function event_AdminPluginNotification(&$data)
	{
		global $member, $manager;
		
		$actions = array('overview', 'pluginlist', 'plugin_LMBlogPaginate');
		$text = "";
		
		if(in_array($data['action'], $actions))
		{			
			if(!$this->_checkReplacementVarsSourceVersion())
			{
				$text .= '<p><b>The installed version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin needs version '.$this->_needReplacementVarsSourceVersion().' or later of the LMReplacementvars plugin to function properly.</b> The latest version of the LMReplacementvars plugin can be downloaded from the LMReplacementvars <a href="http://www.slightlysome.net/nucleus-plugins/np_lmreplacementvars">plugin page</a>.</p>';
			}

			if($manager->pluginInstalled('NP_LMURLParts'))
			{
				if(!$this->_checkURLPartsSourceVersion())
				{
					$text .= '<p><b>The installed version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin needs version '.$this->_needURLPartsSourceVersion().' or later of the LMURLParts plugin to function properly.</b> The latest version of the LMURLParts plugin can be downloaded from the LMURLParts <a href="http://www.slightlysome.net/nucleus-plugins/np_lmurlparts">plugin page</a>.</p>';
				}
				elseif(!$this->_checkURLPartsDataVersion())
				{
					$text .= '<p><b>The LMURLParts plugin data needs to be upgraded before the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin can function properly.</b></p>';
				}
			}
			
			if($manager->pluginInstalled('NP_LMFancierURL'))
			{
				if(!$this->_checkFancierURLSourceVersion())
				{
					$text .= '<p><b>The installed version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin needs version '.$this->_needFancierURLSourceVersion().' or later of the LMFancierURL plugin to function properly.</b> The latest version of the LMFancierURL plugin can be downloaded from the LMFancierURL <a href="http://www.slightlysome.net/nucleus-plugins/np_lmfancierurl">plugin page</a>.</p>';
				}
			}
			
			$sourcedataversion = $this->getDataVersion();
			$commitdataversion = $this->getCommitDataVersion();
			$currentdataversion = $this->getCurrentDataVersion();
		
			if($currentdataversion > $sourcedataversion)
			{
				$text .= '<p>An old version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin files are installed. Downgrade of the plugin data is not supported. The correct version of the plugin files must be installed for the plugin to work properly.</p>';
			}
			
			if($currentdataversion < $sourcedataversion)
			{
				$text .= '<p>The version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin data is for an older version of the plugin than the version installed. ';
				$text .= 'The plugin data needs to be upgraded or the source files needs to be replaced with the source files for the old version before the plugin can be used. ';

				if($member->isAdmin())
				{
					$text .= 'Plugin data upgrade can be done on the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' <a href="'.$this->getAdminURL().'">admin page</a>.';
				}
				
				$text .= '</p>';
			}
			
			if($commitdataversion < $currentdataversion && $member->isAdmin())
			{
				$text .= '<p>The version of the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin data is upgraded, but the upgrade needs to commited or rolled back to finish the upgrade process. ';
				$text .= 'Plugin data upgrade commit and rollback can be done on the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' <a href="'.$this->getAdminURL().'">admin page</a>.</p>';
			}
		}
		
		if($text)
		{
			array_push(
				$data['notifications'],
				array(
					'plugin' => $this->getName(),
					'text' => $text
				)
			);
		}
	}

	function event_TemplateExtraFields(&$data) 
	{
		$data['fields']['NP_LMBlogPaginate'] = array(
			'lmblogpaginate_header' => 'Paginator Header',
			'lmblogpaginate_prev' => 'Paginator Prev',
			'lmblogpaginate_noprev' => 'Paginator No Prev',
			'lmblogpaginate_page' => 'Paginator Page',
			'lmblogpaginate_curr' => 'Paginator Current Page',
			'lmblogpaginate_gap' => 'Paginator Gap',
			'lmblogpaginate_next' => 'Paginator Next',
			'lmblogpaginate_nonext' => 'Paginator No Next',
			'lmblogpaginate_footer' => 'Paginator Footer'
		);
	}
					
	function event_PostParseURL(&$data)
	{
		global $manager, $CONF;
		
		if($manager->pluginInstalled('NP_LMFancierURL') && $CONF['URLMode'] == 'pathinfo')
		{
			// Get params from LMFancierURL
			if(method_exists($this->_getFancierURLPlugin(), 'getURLValue'))
			{
				$aPageParm = $this->_getFancierURLPlugin()->getURLValue('page');
				
				if($aPageParm)
				{
					$this->pageParm = array_shift($aPageParm);
				}
				else 
				{
					$this->pageParm = 0;
				}
			}
		}
		else
		{
			// Get params the normal way
			$this->pageParm = intRequestVar('page');
		}
	}

	function event_PreBlogContent(&$data)
	{	
		global $blogid;
		
		if($data['type'] == 'blog' && isset($data['skinvarparm']))
		{
			$skinvarparm = $data['skinvarparm'];

			if(isset($skinvarparm['lmblogpaginate']))
			{
				$lmblogpaginate = $skinvarparm['lmblogpaginate'];
			}
			else 
			{
				$lmblogpaginate = 'enable';
			}
			
			if($lmblogpaginate == 'enable')
			{
				$blog =& $data['blog'];
			
				if($blogid == $blog->getID())
				{
					$pagesize = $data['limit'];
					$currentpage = $this->pageParm;

					if($currentpage)
					{
						if(is_array($currentpage))
						{
							$currentpage = $currentpage['0'];
						}
					}
					else
					{
						$currentpage = 1;
					}
			
					$data['startpos'] = ($currentpage - 1) * $pagesize;
				}
			}
		}
	}
	
	function event_PostBlogContent(&$data)
	{
		if($data['type'] == 'blog' && isset($data['skinvarparm']))
		{
			$skinvarparm = $data['skinvarparm'];

			if(isset($skinvarparm['lmblogpaginate']))
			{
				$lmblogpaginate = $skinvarparm['lmblogpaginate'];
			}
			else 
			{
				$lmblogpaginate = 'enable';
			}

			if($lmblogpaginate == 'enable')
			{
				$this->_showPaginator($data);
			}
		}
	}

	////////////////////////////////////////////////////////////
	//  Private functions
	function &_getURLPartPlugin()
	{
		global $manager;
		
		$oURLPartPlugin =& $manager->getPlugin('NP_LMURLParts');

		if(!$oURLPartPlugin)
		{
			// Panic
			echo '<p>Couldn\'t get plugin NP_LMURLParts. This plugin must be installed for the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin to work.</p>';
			return false;
		}
		
		return $oURLPartPlugin;
	}
	
	function &_getFancierURLPlugin()
	{
		global $manager;
		
		$oFancierURLPlugin =& $manager->getPlugin('NP_LMFancierURL');

		if(!$oFancierURLPlugin)
		{
			// Panic
			echo '<p>Couldn\'t get plugin LMFancierURL. This plugin must be installed for the '.htmlspecialchars($this->getName(), ENT_QUOTES, _CHARSET).' plugin to work.</p>';
			return false;
		}
		
		return $oFancierURLPlugin;
	}

	function &_getReplacementVarsPlugin()
	{
		global $manager;
		
		$oReplacementVarsPlugin =& $manager->getPlugin('NP_LMReplacementVars');

		if(!$oReplacementVarsPlugin)
		{
			// Panic
			echo '<p>Couldn\'t get plugin LMReplacementVars. This plugin must be installed for the '.$this->getName().' plugin to work.</p>';
			return false;
		}
		
		return $oReplacementVarsPlugin;
	}

	function _getURLPartTypeId()
	{
		global $manager;

		if($manager->pluginInstalled('NP_LMURLParts'))
		{
			if(!$this->urlPartTypeId)
			{
				$this->urlPartTypeId = $this->_getURLPartPlugin()->findTypeId('Page', $this->getName());
				
				if($this->urlPartTypeId === false)
				{
					return false;
				}

				if(!$this->urlPartTypeId)
				{
					$this->urlPartTypeId = $this->_getURLPartPlugin()->addType('Page', $this->getName(), 'B', 'page', 82, 'page');
				}
			}
		}
		else
		{
			$this->urlPartTypeId = 0;
		}
		
		return $this->urlPartTypeId;
	}

	function _createContextLink(&$blog, $extra = '')
	{
		global $manager, $itemid, $archive, $CONF;

		$contexturl = '';

		$catid = $blog->getSelectedCategory();
		$blogid = $blog->getID();
		
		if($archive)
		{
			if($catid)
			{
				$extra['catid'] = $catid;
			}
			
			$contexturl = createArchiveLink($blogid, $archive, $extra);
		} 
		else if($catid)
		{
			$contexturl = createCategoryLink($catid, $extra);
		}

		if(!$contexturl)
		{
			$contexturl = createBlogidLink($blogid, $extra);
		}
		
		return $contexturl;
	}

	function _showPaginator(&$data)
	{
		global $manager;
		// 	Templates:	Header, Footer, Next, Prev, Page, CurrentPage, Gap 
		//	Header	Prev 	First(n)	Between(n)	Current(n)	Between(n)	Last(n)		Next	Footer
		// Params: firstlast=2, between=2, current=2

		$templatename = $data['templatename'];
		
		if($templatename)
		{
			$template =& $manager->getTemplate($templatename);
		}
		else
		{
			$template = array();
		}

		$localtemplate = array();
		
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_header', 'pagetemplateheader');
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_prev', 'pagetemplateprev');
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_noprev', 'pagetemplatenoprev');
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_page', 'pagetemplatepage');
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_curr', 'pagetemplatecurr');
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_gap', 'pagetemplategap');
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_next', 'pagetemplatenext');
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_nonext', 'pagetemplatenonext');
		$this->_checkSetTemplate($template, $localtemplate, 'lmblogpaginate_footer', 'pagetemplatefooter');

		if(isset($data['skinvarparm']['firstlast']))
		{
			$firstlast = $data['skinvarparm']['firstlast'];
		}
		else
		{
			$firstlast=2;
		}
		
		if(isset($data['skinvarparm']['between']))
		{
			$between = $data['skinvarparm']['between'];
		}
		else
		{
			$between=2;
		}
		
		if(isset($data['skinvarparm']['current']))
		{
			$current = $data['skinvarparm']['current'];
		}
		else
		{
			$current=2;
		}

		$currentpage = $this->pageParm;

		if($currentpage)
		{
			if(is_array($currentpage))
			{
				$currentpage = $currentpage['0'];
			}
		}
		else
		{
			$currentpage = 1;
		}
		
		$blog =& $data['blog'];
		$extraquery = $data['extraquery'];
		$pagesize = $data['limit'];

		$query = $blog->getSqlBlog($extraquery, 'count');
		
		$res = sql_query($query);
		
		while ($o = sql_fetch_object($res))
		{
			$items = $o->result;
		}

		$pages = ceil($items / $pagesize);
		if($currentpage > $pages)
		{
			$currentpage = $pages;
		}

		$this->_registerPagesInURLParts($blog->getID(), $pages);
	
		$slots = ($firstlast * 2) + ($between * 2) + ($current * 2) + 1;
		
		$aSlots = array();
		
		for($n = 1; $n <= $firstlast; $n++)
		{
			if($n < $pages)
			{
				array_push($aSlots, $n);
			}
			
			$p = $pages - ($n - 1);
			
			if($p > 0 )
			{
				array_push($aSlots, $p);
			}
		}
		
		$diff = $currentpage - $current - $firstlast - 1;
		
		if($diff > 0)
		{
			for($n = 1; $n <= $between; $n++)
			{
				$r1 = $currentpage - $current;
				$r2 = intval(round($diff / pow(2, $n)));

				$p = $currentpage - $current - intval(round($diff / pow(2, $n)));
				
				if($p > 0 && $p <= $pages)
				{
					array_push($aSlots, $p);
				}
			}
		}

		$diff = $pages - ($currentpage + $current + $firstlast);
		
		if($diff > 0)
		{
			for($n = 1; $n <= $between; $n++)
			{
				$r1 = $currentpage + $current;
				$r2 = intval(round($diff / pow(2, $n)));
				$p = $currentpage + $current + intval(round($diff / pow(2, $n)));

				if($p > 0 && $p <= $pages)
				{
					array_push($aSlots, $p);
				}
			}
		}

		for($n = $currentpage - $current; $n <= $currentpage + $current; $n++)
		{
			if($n > 0 && $n <= $pages)
			{
				array_push($aSlots, $n);
			}
		}

		$aSlots = array_unique($aSlots);
		sort($aSlots);

		$extra = array();
		$eventdata = array(
				'blog' => &$blog,
				'linkparams' => &$extra
			);
		
		$manager->notify('LMBlogPaginate_LinkParams', $eventdata);

		$nopageurl = $this->_createContextLink($blog, $extra);
		
		$aHeaderFooter = array(
			'contexturl' => $nopageurl 
		);

		echo TEMPLATE::fill($localtemplate['lmblogpaginate_header'], $aHeaderFooter);
		
		$prevpage = $currentpage - 1;

		if($prevpage > 0)
		{
			$firstitem = (($prevpage - 1) * $pagesize) + 1;
			$lastitem = $prevpage * $pagesize;
			
			$extra['page'] = $prevpage;

			$pageurl = $this->_createContextLink($blog, $extra);

			$aPage = array(
				'contexturl' => $pageurl,
				'pagenumber' => $prevpage,
				'firstitem' => $firstitem,
				'lastitem' => $lastitem
			);

			echo TEMPLATE::fill($localtemplate['lmblogpaginate_prev'], $aPage);
		}
		else
		{
			$aPage = array(
				'contexturl' => $nopageurl
			);
			echo TEMPLATE::fill($localtemplate['lmblogpaginate_noprev'], $aPage);
		}
		
		$lastpage = 0;
		
		foreach($aSlots as $pagenr)
		{
			if($pagenr > $lastpage + 1)
			{
				$aPage = array();

				echo TEMPLATE::fill($localtemplate['lmblogpaginate_gap'], $aPage);
				
				$aftergap = 'aftergap';
			}
			else
			{
				$aftergap = '';
			}
	
			$firstitem = (($pagenr - 1) * $pagesize) + 1;
			$lastitem = $pagenr * $pagesize;

			$extra['page'] = $pagenr;
			
			$pageurl = $this->_createContextLink($blog, $extra);
			
			$aPage = array(
				'contexturl' => $pageurl,
				'pagenumber' => $pagenr,
				'firstitem' => $firstitem,
				'lastitem' => $lastitem,
				'aftergap' => $aftergap
			);

			if($pagenr == $currentpage)
			{
				echo TEMPLATE::fill($localtemplate['lmblogpaginate_curr'], $aPage);
			}
			else
			{
				echo TEMPLATE::fill($localtemplate['lmblogpaginate_page'], $aPage);
			}

			$lastpage = $pagenr;
		}

		$nextpage = $currentpage + 1;
		
		if($nextpage <= $pages)
		{
			$firstitem = (($nextpage - 1) * $pagesize) + 1;
			$lastitem = $nextpage * $pagesize;

			$extra['page'] = $nextpage;

			$pageurl = $this->_createContextLink($blog, $extra);

			$aPage = array(
				'contexturl' => $pageurl,
				'pagenumber' => $nextpage,
				'firstitem' => $firstitem,
				'lastitem' => $lastitem
			);

			echo TEMPLATE::fill($localtemplate['lmblogpaginate_next'], $aPage);
		}
		else
		{
			$aPage = array(
				'contexturl' => $nopageurl
			);
			echo TEMPLATE::fill($localtemplate['lmblogpaginate_nonext'], $aPage);
		}

		echo TEMPLATE::fill($localtemplate['lmblogpaginate_footer'], $aHeaderFooter);
	}

	function _registerPagesInURLParts($blogid, $maxpage)
	{
		global $manager, $CONF;
		
		if($manager->pluginInstalled('NP_LMFancierURL') && $CONF['URLMode'] == 'pathinfo')
		{
			$typeid = $this->_getURLPartTypeId();
			if($typeid)
			{
				for($n = $maxpage; $n > 0; $n--)
				{
					$urlpart = $this->_getURLPartPlugin()->findURLPartByTypeIdRefIdBlogId($typeid, $n, $blogid);
					
					if(! $urlpart)
					{
						$this->_getURLPartPlugin()->addChangeURLPart('p'.$n, $typeid, $n, $blogid);
					}
				}
			}
		}
	}

	function _checkSetTemplate(&$globaltemplate, &$localtemplate, $index, $option)
	{
		if(isset($globaltemplate[$index]))
		{
			$val = $globaltemplate[$index];
		}
		else
		{
			$val = '';
		}
		
		if($val == '#empty#')
		{
			$val = '';
		}
		else if($val == '')
		{
			$val = $this->getOption($option);
		}
		
		$localtemplate[$index] = $val;
	}
	
	/////////////////////////////////////////////////////
	// Data access and manipulation functions
	

	////////////////////////////////////////////////////////////////////////
	// Plugin Upgrade handling functions
	function getCurrentDataVersion()
	{
		$currentdataversion = $this->getOption('currentdataversion');
		
		if(!$currentdataversion)
		{
			$currentdataversion = 0;
		}
		
		return $currentdataversion;
	}

	function setCurrentDataVersion($currentdataversion)
	{
		$res = $this->setOption('currentdataversion', $currentdataversion);
		$this->clearOptionValueCache(); // Workaround for bug in Nucleus Core
		
		return $res;
	}

	function getCommitDataVersion()
	{
		$commitdataversion = $this->getOption('commitdataversion');
		
		if(!$commitdataversion)
		{
			$commitdataversion = 0;
		}

		return $commitdataversion;
	}

	function setCommitDataVersion($commitdataversion)
	{	
		$res = $this->setOption('commitdataversion', $commitdataversion);
		$this->clearOptionValueCache(); // Workaround for bug in Nucleus Core
		
		return $res;
	}

	function getDataVersion()
	{
		return 1;
	}
	
	function upgradeDataTest($fromdataversion, $todataversion)
	{
		// returns true if rollback will be possible after upgrade
		$res = true;
				
		return $res;
	}
	
	function upgradeDataPerform($fromdataversion, $todataversion)
	{
		// Returns true if upgrade was successfull
		
		for($ver = $fromdataversion; $ver <= $todataversion; $ver++)
		{
			switch($ver)
			{
				case 1:
					$this->createOption('del_uninstall', 'Delete NP_LMBlogPaginate data tables on uninstall?', 'yesno','no');
					$this->createOption('currentdataversion', 'currentdataversion', 'text','0', 'access=hidden');
					$this->createOption('commitdataversion', 'commitdataversion', 'text','0', 'access=hidden');

					$this->createOption('pagetemplateheader', 'Paginator Header', 'textarea', '<ul id="lmbppagination">');
					$this->createOption('pagetemplateprev', 'Paginator Prev', 'textarea', '<li><a id="lmbpprev" href="<%contexturl%>" title="Page: <%pagenumber%> Items: <%firstitem%> - <%lastitem%>">&laquo;Prev</a></li>');
					$this->createOption('pagetemplatenoprev', 'Paginator No Prev', 'textarea', '<li><span id="lmbpprev">&laquo;Prev</span></li>');
					$this->createOption('pagetemplatepage', 'Paginator Page', 'textarea', '<li><a class="lmbppage<%aftergap%>" href="<%contexturl%>" title="Page: <%pagenumber%> Items: <%firstitem%> - <%lastitem%>"><%pagenumber%></a></li>');
					$this->createOption('pagetemplatecurr', 'Paginator Current Page', 'textarea', '<li><span id="lmbpcurrentpage"><%pagenumber%></span></li>');
					$this->createOption('pagetemplategap', 'Paginator Gap', 'textarea', '<li><span class="lmbppagegap">-</span></li>');
					$this->createOption('pagetemplatenext', 'Paginator Next', 'textarea', '<li><a id="lmbpnext" href="<%contexturl%>" title="Page: <%pagenumber%> Items: <%firstitem%> - <%lastitem%>">Next&raquo;</a></li>');
					$this->createOption('pagetemplatenonext', 'Paginator No Next', 'textarea', '<li><span id="lmbpnext">Next&raquo;</span></li>');
					$this->createOption('pagetemplatefooter', 'Paginator Footer', 'textarea', '</ul>');

					$res = true;
					break;
				case 2:
					$res = true;
					break;
				case 3:
					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}
		
		return true;
	}
	
	function upgradeDataRollback($fromdataversion, $todataversion)
	{
		// Returns true if rollback was successfull
		for($ver = $fromdataversion; $ver >= $todataversion; $ver--)
		{
			switch($ver)
			{
				case 1:
				case 2:
				case 3:
					$res = true;
					break;
				
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}

		return true;
	}

	function upgradeDataCommit($fromdataversion, $todataversion)
	{
		// Returns true if commit was successfull
		for($ver = $fromdataversion; $ver <= $todataversion; $ver++)
		{
			switch($ver)
			{
				case 1:
				case 2:
				case 3:
					$res = true;
					break;
				default:
					$res = false;
					break;
			}
			
			if(!$res)
			{
				return false;
			}
		}
		return true;
	}
	
	function _checkColumnIfExists($table, $column)
	{
		// Retuns: $column: Found, '' (empty string): Not found, false: error
		$found = '';
		
		$res = sql_query("SELECT * FROM ".$table." WHERE 1 = 2");

		if($res)
		{
			$numcolumns = sql_num_fields($res);

			for($offset = 0; $offset < $numcolumns && !$found; $offset++)
			{
				if(sql_field_name($res, $offset) == $column)
				{
					$found = $column;
				}
			}
		}
		
		return $found;
	}
	
	function _addColumnIfNotExists($table, $column, $columnattributes)
	{
		$found = $this->_checkColumnIfExists($table, $column);
		
		if($found === false) 
		{
			return false;
		}
		
		if(!$found)
		{
			$res = sql_query("ALTER TABLE ".$table." ADD ".$column." ".$columnattributes);

			if(!$res)
			{
				return false;
			}
		}

		return true;
	}

	function _dropColumnIfExists($table, $column)
	{
		$found = $this->_checkColumnIfExists($table, $column);
		
		if($found === false) 
		{
			return false;
		}
		
		if($found)
		{
			$res = sql_query("ALTER TABLE ".$table." DROP COLUMN ".$column);

			if(!$res)
			{
				return false;
			}
		}

		return true;
	}

	function _needURLPartsSourceVersion()
	{
		return '1.1.1';
	}
	
	function _checkURLPartsSourceVersion()
	{
		$urlPartsVersion = $this->_needURLPartsSourceVersion();
		$aVersion = explode('.', $urlPartsVersion);
		$needmajor = $aVersion['0']; $needminor = $aVersion['1']; $needpatch = $aVersion['2'];
		
		$urlPartsVersion = $this->_getURLPartPlugin()->getVersion();
		$aVersion = explode('.', $urlPartsVersion);
		$major = $aVersion['0']; $minor = $aVersion['1']; $patch = $aVersion['2'];
		
		if($major < $needmajor || (($major == $needmajor) && ($minor < $needminor)) || (($major == $needmajor) && ($minor == $needminor) && ($patch < $needpatch)))
		{
			return false;
		}

		return true;
	}

	function _checkURLPartsDataVersion()
	{
		if(!method_exists($this->_getURLPartPlugin(), 'getDataVersion'))
		{
			return false;
		}
		
		$current = $this->_getURLPartPlugin()->getCurrentDataVersion();
		$source = $this->_getURLPartPlugin()->getDataVersion();
		
		if($current < $source)
		{
			return false;
		}

		return true;
	}

	function _needFancierURLSourceVersion()
	{
		return '3.0.0';
	}
	
	function _checkFancierURLSourceVersion()
	{
		$fancierURLVersion = $this->_needFancierURLSourceVersion();
		$aVersion = explode('.', $fancierURLVersion);
		$needmajor = $aVersion['0']; $needminor = $aVersion['1']; $needpatch = $aVersion['2'];
		
		$fancierURLVersion = $this->_getFancierURLPlugin()->getVersion();
		$aVersion = explode('.', $fancierURLVersion);
		$major = $aVersion['0']; $minor = $aVersion['1']; $patch = $aVersion['2'];
		
		if($major < $needmajor || (($major == $needmajor) && ($minor < $needminor)) || (($major == $needmajor) && ($minor == $needminor) && ($patch < $needpatch)))
		{
			return false;
		}

		return true;
	}

	function _needReplacementVarsSourceVersion()
	{
		return '1.0.0';
	}
	
	function _checkReplacementVarsSourceVersion()
	{
		$replacementVarsVersion = $this->_needReplacementVarsSourceVersion();
		$aVersion = explode('.', $replacementVarsVersion);
		$needmajor = $aVersion['0']; $needminor = $aVersion['1']; $needpatch = $aVersion['2'];
		
		$replacementVarsVersion = $this->_getReplacementVarsPlugin()->getVersion();
		$aVersion = explode('.', $replacementVarsVersion);
		$major = $aVersion['0']; $minor = $aVersion['1']; $patch = $aVersion['2'];
		
		if($major < $needmajor || (($major == $needmajor) && ($minor < $needminor)) || (($major == $needmajor) && ($minor == $needminor) && ($patch < $needpatch)))
		{
			return false;
		}

		return true;
	}
}
?>
