<?php
/***************************************************************
*	Copyright notice
*
*	(c) 2007,2013 Michael Perlbach (info@mikelmade.de)
*	All rights reserved
*
*	This script is part of the TYPO3 project. The TYPO3 project is
*	free software; you can redistribute it and/or modify
*	it under the terms of the GNU General Public License as published by
*	the Free Software Foundation; either version 2 of the License, or
*	(at your option) any later version.
*
*	The GNU General Public License can be found at
*	http://www.gnu.org/copyleft/gpl.html.
*
*	This script is distributed in the hope that it will be useful,
*	but WITHOUT ANY WARRANTY; without even the implied warranty of
*	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
*	GNU General Public License for more details.
*
*	This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Module 'mw_imagemap' for the 'mwimagemap' extension.
 *
 * @author	Michael Perlbach <info@mikelmade.de>
 */

ini_set('error_reporting', 'E_ALL ^ E_NOTICE');

session_start();
	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require ('conf.php');
require ($GLOBALS['BACK_PATH'].'init.php');
//require ($GLOBALS['BACK_PATH'].'template.php');
$GLOBALS['LANG']->includeLLFile('EXT:mwimagemap/mod1/locallang_mod.xml');


if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) { define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/'); }
elseif (@is_dir(PATH_site.'tslib/')) { define('PATH_tslib', PATH_site.'tslib/'); }
else {
	// define path to tslib/ here:
	$configured_tslib_path = '';

	// example: $configured_tslib_path = '/var/www/mysite/typo3/sysext/cms/tslib/';
	define('PATH_tslib', $configured_tslib_path);
}

if (PATH_tslib=='') { die('Cannot find tslib/. Please set path by defining $configured_tslib_path in '.basename(PATH_thisScript).'.'); }

//require_once (PATH_t3lib.'class.t3lib_scbase.php');
//require_once (PATH_tslib.'class.tslib_content.php');
require_once (t3lib_extMgm::extPath('mwimagemap').'constants.php');
$GLOBALS['BE_USER']->modAccess($MCONF, 1);	// This checks permissions and exits if the users has no permission for entry.

define('UPLOAD_DIR', 'uploads/tx_mwimagemap/');
define('MODULE_DIR', t3lib_extMgm::extPath('mwimagemap').'mod1/');

class tx_mwimagemap_module1 extends t3lib_SCbase {
	var $pageinfo;
	var $extConf;
	var $impath;
	
	/**
	 * Initializes
	 */
	function init()	{
		parent::init();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 */
	function menuConfig()	{
		$this->MOD_MENU = Array ();
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 */
	function main()	{
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mwimagemap']);
		
		$this->transparent = '';
		$this->impath = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'];
		if($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] == 'gm') { $this->impath .= 'gm '; }
		$path = substr(t3lib_div::_GP('id'), strlen(PATH_site));

		// Draw the header.
		$this->doc = t3lib_div::makeInstance("mediumDoc");
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		// JavaScript
		$this->doc->JScode = '
			<script language="javascript" type="text/javascript">
				script_ended = 0;
				function jumpToUrl(URL)	{ document.location = URL; }
			</script>
			<script type="text/javascript" src="js/functions.js"></script>';
			
		$this->doc->postCode='
			<script language="javascript" type="text/javascript">
				script_ended = 1;
				if (top.fsMod) top.fsMod.recentIds["file"] = '.intval($this->id).';
				function setFormValueFromBrowseWin(a,b,c) {
					barr = b.split(\'_\');
					bnum = barr[(barr.length-1)];
					btitle = (c.length > 0) ? c : \'[\'+bnum+\']\';
					document.getElementById(\'cbid\').value = bnum;
					document.getElementById(\'cbtext\').innerHTML = btitle;
					document.getElementById(\'cbtext\').title = btitle;
					document.getElementById(\'cbtext\').style.display = \'block\';
					document.getElementById(\'cbdel\').style.display = \'block\';
					document.getElementById(\'cbwarning\').style.display = \'none\';
				}
				function delcbid() {
					document.getElementById(\'cbtext\').innerHTML = \'\';
					document.getElementById(\'cbtext\').style.display = \'none\';
					document.getElementById(\'cbdel\').style.display = \'none\';
					document.getElementById(\'cbid\').value = \'\';
					document.getElementById(\'cbtext\').title = \'\';
					document.getElementById(\'cbwarning\').style.display = \'none\';
				}	
			</script>
			<link rel="stylesheet" type="text/css" href="colpicker/js_color_picker_v2.css" media="screen" />
			<style type="text/css"> /*<![CDATA[*/ <!-- 
			body { padding-left:20px; }
			--> /*]]>*/
			</style>';
		// create the temporary folder for thumbnail-images if it doesn't exist
		if (!is_dir(PATH_site.'typo3temp/tx_mwimagemap/')) {
			t3lib_div::mkdir(PATH_site.'typo3temp/tx_mwimagemap/');
		}

		$headerSection = $this->doc->getHeader('pages', $this->pageinfo, $this->pageinfo['_thePath']).'<br />'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.path').': '.t3lib_div::fixed_lgd_cs($path, 50);
			
		$this->content.=$this->doc->startPage($GLOBALS['LANG']->getLL("title"));
		$this->content.=$this->doc->header($GLOBALS['LANG']->getLL("title"));
		$this->content.=$this->doc->spacer(5);
		$this->content.= $this->doc->section('', $this->doc->funcMenu($headerSection, t3lib_BEfunc::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function'])));
		$this->content.=$this->doc->divider(5);

		$carr = explode('<body', $this->content);
		$cpos = strpos($carr[1], '>');
		$btag = substr($carr[1], 0, $cpos+1);
		$bodytag = '<body'.$btag;

		// Render content:
		if ( t3lib_div::_GP('area_page') ) { $this->areaContent(); }
		else { $this->mapContent(); }

		// ShortCut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{	
			$this->content.=$this->doc->spacer(20).$this->doc->section("", $this->doc->makeShortcutIcon('id', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']));
		}
		$this->content.=$this->doc->spacer(10);
	}

	/**
	 * Prints out the module HTML
	 */
	function printContent()	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	function create_thumb($filename, $path) {
		$img_data = getimagesize ( PATH_site . $path . $filename );
		$width		= $img_data['0'];
		$height	 = $img_data['1'];
		
		if ($width >= $height) { $ratio = $width/100; }
		else { $ratio = $height/100; }
		$new_width	= ceil($width/$ratio);
		$new_height = ceil($height/$ratio);
		$new_path	 = PATH_site.'typo3temp/tx_mwimagemap/'.md5($path.$filename);
		
		if(preg_match("/WIN/", PHP_OS)) { exec('"'.rtrim($this->impath).'" convert -resize '.$new_width.'!x'.$new_height.'! -quality 100 -unsharp 1.5x1.2+1.0+0.10 "'.PATH_site.$path.$filename.'" "'.$new_path.'.jpg"'); }
		else { exec($this->impath.'convert -resize '.$new_width.'!x'.$new_height.'! -quality 100 -unsharp 1.5x1.2+1.0+0.10 '.PATH_site.$path.$filename.' '.$new_path.'.jpg'); }
		
		rename($new_path.'.jpg', $new_path);
	}

	/**
		* Generates the module content
		*/
	function mapContent() {
		$path = substr(t3lib_div::_GP('id'), strlen(PATH_site));
		
		if(preg_match('/\:/', t3lib_div::_GP('id'))) {
			$parr = explode(':', t3lib_div::_GP('id'));
			$path = 'fileadmin'.$parr[1];
		}
		
		if(isset($_GET['SLCMD']['SELECT']['txdamFolder'])) {
			$patharr = array_keys($_GET['SLCMD']['SELECT']['txdamFolder']);
			$path = $patharr[0];
			$path = str_replace(PATH_site, '', $path);
		}
		
		if ( $path == "" || ! is_dir(PATH_site . $path) ) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('err'), $GLOBALS['LANG']->getLL('choos_dir'), 0, 1);
			return TRUE;
		}
		if ( $path[strlen($path)-1] != '/' ) { $path .= '/'; }
		
		$db =& $GLOBALS['TYPO3_DB'];
		$content = '';
		$template = file_get_contents( MODULE_DIR.'/templates/template_map.html' );
		$add_part = $this->cObj->getSubpart( $template, '###ADD_PART###' );
		$list_part = $this->cObj->getSubpart( $template, '###LIST_PART###' );
		$list_item = $this->cObj->getSubpart( $template, '###LIST_ITEM###' );

		switch ( t3lib_div::_GP('action') ) {
			case 'add':
				if ( trim(t3lib_div::_GP('name')) === '' ) {
					if ( ! ( $res = $db->exec_SELECTquery('count(*)', 'tx_mwimagemap_map', '') ) || ! ( $row = $db->sql_fetch_row($res) ) ) {
						if ( $_FILES['usr_file']['tmp_name'] ) { unlink( $_FILES['usr_file']['tmp_name'] ); }
						$content .= 'exec_SELECT sql_error: '.$db->sql_error().'<br />';
						break;
					}
					$name = 'map_'.($row[0]+1);
				}
				else { $name = trim(t3lib_div::_GP('name')); }
				if ( is_uploaded_file($_FILES['usr_file']['tmp_name']) ) {
					if ( ! $_FILES['usr_file']['name'] || $_FILES['usr_file']['error'] ) {
						if ( $_FILES['usr_file']['tmp_name'] ) { unlink( $_FILES['usr_file']['tmp_name'] ); }
						$content .= $GLOBALS['LANG']->getLL('err_upload1').'<br />';
						break;
					}
					if ( $_FILES['usr_file']['size'] > 1000000 ) {
						unlink($_FILES['usr_file']['tmp_name']);
						$content .= $GLOBALS['LANG']->getLL('err_file_size').'<br />';
						break;
					}
					$file = str_replace(' ', '-', $_FILES['usr_file']['name']);
					if ( ! ( $res = $db->exec_SELECTquery('id', 'tx_mwimagemap_map', 'file = '.$db->fullQuoteStr($file, 'tx_mwimagemap_map').' and folder = '.$db->fullQuoteStr($path, 'tx_mwimagemap_map') ) ) ) {
						$content .= 'exec_SELECT sql_error: '.$db->sql_error().'<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( $db->sql_fetch_row($res) ) {
						$content .= $GLOBALS['LANG']->getLL('err_file_exists').'<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( $file[0] == 't' && $file[1] == '_' ) {
						$content .= $GLOBALS['LANG']->getLL('ilegal_filename').'<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( is_file( PATH_site . $path . $file ) ) { unlink( PATH_site . $path . $file ); }
					if ( ! move_uploaded_file($_FILES['usr_file']['tmp_name'], PATH_site . $path . $file) ) {
						$content .= 'move_uploaded_file NOT successful<br />'.$_FILES['usr_file']['tmp_name'].','. PATH_site . $path . $file.'<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( ! is_file(PATH_site . $path . $file) ) {
						$content .= '! is_file('.PATH_site . $path . $file.')<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( is_file(PATH_site . $path . $file) ) { t3lib_div::fixPermissions(PATH_site . $path . $file); }
					$new_file = TRUE;
				}
				elseif ( t3lib_div::_GP('use_pic') ) {
					$new_file = FALSE;
					if ( ! ( $res = $db->exec_SELECTquery('file', 'tx_mwimagemap_map', 'id='.intval(t3lib_div::_GP('use_pic')).' AND folder='.$db->fullQuoteStr($path, 'tx_mwimagemap_map') ) ) ) {
						$content .= 'exec_SELECT sql_error:'.$db->sql_error().'<br />';
						break;
					}
					if ( ! ( $row = $db->sql_fetch_row($res) ) ) {
						$content .= $GLOBALS['LANG']->getLL('nofile');
						break;
					}
					$file = $row[0];
				}
				elseif ( t3lib_div::_GP('use_file') ) {
					$new_file = FALSE;
					if ( is_file(PATH_site . $path . t3lib_div::_GP('use_file')) ) { $file = t3lib_div::_GP('use_file'); }
					else {
						$content .= $GLOBALS['LANG']->getLL('nofile');
						break;
					}
				}
				else {
					$content .= $GLOBALS['LANG']->getLL('nofile');
					break;
				}
				if ( ! $db->exec_INSERTquery('tx_mwimagemap_map', array( 'file' => $file, 'name' => $name, 'folder' => $path ) ) ) {
					$content .= 'exec_INSERT sql_error: '.$db->sql_error().'<br />';
					if ( $new_file ) { unlink(PATH_site . $path . $file); }
					break;
				}
				$mid = $db->sql_insert_id();
				$use_map = intval(t3lib_div::_GP('use_map'));
				if ( $new_file ) { $this->create_thumb($file, $path); }
				if ( $use_map != 0 ) {
					if ( ! ( $res = $db->exec_SELECTquery('*', 'tx_mwimagemap_area', 'mid = '.$use_map) ) ) {
						$content .= 'exec_SELECT sql_error: '.$db->sql_error().'<br />could not create a copy of the selected imagemap<br />';
						break;
					}
					while ( $row = $db->sql_fetch_assoc($res) ) {
						$row['mid'] = $mid;
						$old_aid = $row['id'];
						unset($row['id']);
						if ( ! $db->exec_INSERTquery('tx_mwimagemap_area', $row) ) {
							$content .= 'exec_INSERT sql_error: '.$db->sql_error().'<br />could not create a proper copy of the selected imagemap<br />';
							continue;
						}
						$aid = $db->sql_insert_id();
						if ( ! ( $res1 = $db->exec_SELECTquery('*', 'tx_mwimagemap_point', 'aid = '.$old_aid) ) ) {
							$content .= 'exec_SELECT sql_error: '.$db->sql_error().'<br />could not create a	proper copy of the selected imagemap<br />';
							continue;
						}
						while ( $row = $db->sql_fetch_assoc($res1) ) {
							$row['aid'] = $aid;
							unset($row['id']);
							if ( ! $db->exec_INSERTquery('tx_mwimagemap_point', $row) ) {
								$content .= 'exec_INSERT sql_error: '.$db->sql_error().'<br />could not create a proper copy of the selected imagemap<br />';
								continue;
							}
						}
					}
				}
				$this->createFePics(intval($mid), 0);
			break;

			case 'del':
				$this->deleteFePics(intval(t3lib_div::_GP('map_id')));
				if ( ! ( $res = $db->exec_SELECTquery( 'file', 'tx_mwimagemap_map', 'id = '.intval(t3lib_div::_GP('map_id')).' AND folder='.$db->fullQuoteStr($path, 'tx_mwimagemap_map') ) )
					|| ! ( $row = $db->sql_fetch_row($res) ) ) {
					$content .= 'Fatal db error, deleting \''.t3lib_div::_GP('map_id').'\' not successfull<br />sql_error:'.$db->sql_error().'<br />';
					break;
				}
				if ( ! ( $res = $db->exec_SELECTquery( 'id', 'tx_mwimagemap_map', 'file = \''.$row[0].'\' AND folder = '.$db->fullQuoteStr($path, 'tx_mwimagemap_map') ) ) ) {
					$content .= 'Fatal db error, deleting \''.t3lib_div::_GP('map_id').'\' not successfull<br />sql_error:'.$db->sql_error().'<br />';
					break;
				}
				if ( $db->sql_num_rows($res) == 1 && t3lib_div::_GP('del_unused') == 'on' ) {
					unlink(PATH_site . $path . $row[0]);
					unlink(PATH_site.'typo3temp/tx_mwimagemap/'.md5($path.$row[0]));
				}
				if (	! ( $res = $db->exec_SELECTquery('id', 'tx_mwimagemap_area', 'mid = '.intval(t3lib_div::_GP('map_id')) ) ) ) {
					$content .= 'db error, sql_error: '.$db->sql_error.'<br />imagemap might be not deleted completly!<br />';
				}
				else {
					while ( $row = $db->sql_fetch_row($res) ) {
						if ( ! $db->exec_DELETEquery( 'tx_mwimagemap_point', 'aid = '.$row[0] ) ) { $content .= 'sql_error:'.$db->sql_error().'<br />'; }
						if ( ! $db->exec_DELETEquery( 'tx_mwimagemap_area', 'mid = '.intval(t3lib_div::_GP('map_id')) ) ) {
							$content .= 'sql_error:'.$db->sql_error().'<br />';
						}
					}
				}

				if ( ! $db->exec_DELETEquery('tx_mwimagemap_map', 'id = '.intval(t3lib_div::_GP('map_id'))) ) { $content .= 'sql_error:'.$db->sql_error().'<br />'; }
				$this -> checkFecache();
			break;

			case 'chg_name':
				$file = '';
				if ( is_uploaded_file($_FILES['usr_file']['tmp_name']) ) {
					if ( ! $_FILES['usr_file']['name'] || $_FILES['usr_file']['error'] ) {
						if ( $_FILES['usr_file']['tmp_name'] ) { unlink( $_FILES['usr_file']['tmp_name'] ); }
						$content .= $GLOBALS['LANG']->getLL('err_upload1').'<br />';
						break;
					}
					if ( $_FILES['usr_file']['size'] > 1000000 ) {
						unlink($_FILES['usr_file']['tmp_name']);
						$content .= $GLOBALS['LANG']->getLL('err_file_size').'<br />';
						break;
					}
					$afile = str_replace(' ', '-', $_FILES['usr_file']['name']);
					if ( ! ( $res = $db->exec_SELECTquery('id', 'tx_mwimagemap_map', 'file = '.$db->fullQuoteStr($afile, 'tx_mwimagemap_map').' and folder = '.$db->fullQuoteStr($path, 'tx_mwimagemap_map') ) ) ) {
						$content .= 'exec_SELECT sql_error: '.$db->sql_error().'<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( $db->sql_fetch_row($res) ) {
						$content .= $GLOBALS['LANG']->getLL('err_file_exists').'<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( $afile[0] == 't' && $afile[1] == '_' ) {
						$content .= $GLOBALS['LANG']->getLL('ilegal_filename').'<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( is_file( PATH_site . $path . $afile ) ) { unlink( PATH_site . $path . $afile ); }
					if ( ! move_uploaded_file($_FILES['usr_file']['tmp_name'], PATH_site . $path . $afile) ) {
						$content .= 'move_uploaded_file NOT successfull<br />'.$_FILES['usr_file']['tmp_name'].','. PATH_site . $path . $afile.'<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( ! is_file(PATH_site . $path . $afile) ) {
						$content .= '! is_file('.PATH_site . $path . $afile.')<br />';
						unlink($_FILES['usr_file']['tmp_name']);
						break;
					}
					if ( is_file(PATH_site . $path . $afile) ) { t3lib_div::fixPermissions(PATH_site . $path . $afile); }
					$file = $afile;
				}
				$name = trim(t3lib_div::_GP('name'));
				if ( $file != '' ) {
					if ( ! ( $res = $db->exec_SELECTquery('file', 'tx_mwimagemap_map', 'id = '.intval(t3lib_div::_GP('map_id')).' AND folder='.$db->fullQuoteStr($path, 'tx_mwimagemap_map') ) ) ) {
						unlink( PATH_site . $path . $_FILES['usr_file']['name'] );
						$content .= 'exec_SELECT sql_error: '.$db->sql_error().'<br />';
						break;
					}
					if ( ! ( $row = $db->sql_fetch_row($res) ) ) {
						unlink( PATH_site . $path . $_FILES['usr_file']['name'] );
						$content .= 'error occured, action aborted<br />';
						break;
					}
					if ( ! ( $res = $db->exec_SELECTquery('id', 'tx_mwimagemap_map', 'file= "'.$row[0].'" AND folder='.$db->fullQuoteStr($path, 'tx_mwimagemap_map') ) ) ) {
						unlink( PATH_site . $path . $_FILES['usr_file']['name'] );
						$content .= 'exec_SELECT sql_error: '.$db->sql_error().'<br />';
						break;
					}
					if ( $db->sql_num_rows($res) == 1 && t3lib_div::_GP('del_unused') == 'on' ) { $do_unlink = TRUE; }
					else { $do_unlink = FALSE; }
				}
				if ( $name != '' || $file != '' ) {
					$res = array();
					if ( $name != '' ) { $res['name'] = $name; }
					if ( $file != '' ) { $res['file'] = $file; }
					if ( !$db->exec_UPDATEquery( 'tx_mwimagemap_map', 'id = '.intval(t3lib_div::_GP('map_id')).' AND folder='.$db->fullQuoteStr($path, 'tx_mwimagemap_map'), $res ) ) {
						unlink( PATH_site . $path . $_FILES['usr_file']['name'] );
						$content .= 'chg_name sql_error: '.$db->sql_error();
						break;
					}
					if ( $file != '' ) {
						$this->create_thumb($file, $path);
						if ( $do_unlink ) {
							@unlink(PATH_site . $path . $row[0]);
							@unlink(PATH_site.'typo3temp/tx_mwimagemap/'.md5($path.$row[0]));
						}
					}
				}
				$this->createFePics(intval(t3lib_div::_GP('map_id')), 0);
				$this -> checkFecache();
				break;
			}

			if ( $content ) { $this->content .= $this->doc->section($GLOBALS['LANG']->getLL('err'), $content, 0, 1); }
			$content = '';

			preg_match('/(.*?)\/(typo3|typo3conf)\/ext\//i', $_SERVER['SCRIPT_URL'], $match);
			$mA['###TYPO3_PATH###'] = $match[1];

			$markerArray['###MAP_NAME###'] = $GLOBALS['LANG']->getLL('Map Name');
			$markerArray['###PICTURE###'] = $GLOBALS['LANG']->getLL('Picture');
			$markerArray['###ADD_SUBMIT###'] = $GLOBALS['LANG']->getLL('Add');
			$markerArray['###USE_PICTURE###'] = $GLOBALS['LANG']->getLL('use_pic');
			$markerArray['###OPTIONS###'] = '';
			$markerArray['###USE_MAP###'] = $GLOBALS['LANG']->getLL('use_map');
			$markerArray['###USE_FILE###'] = $GLOBALS['LANG']->getLL('use_file');
			$markerArray['###PATHID###'] = t3lib_div::_GP('id');

			$i = 0;
			$res = $db->exec_SELECTquery('id, file, name', 'tx_mwimagemap_map', 'folder='.$db->fullQuoteStr($path, 'tx_mwimagemap_map') );
			$mA['###L_DEL###'] = $GLOBALS['LANG']->getLL('del');
			$mA['###L_SAVE###'] = $GLOBALS['LANG']->getLL('save');
			$mA['###NEW_PICTURE###'] = $GLOBALS['LANG']->getLL('new_pic');
			$mA['###DEL_UNUSED###'] = $GLOBALS['LANG']->getLL('del_unused');
			$files = array();
			while ( $row = $db->sql_fetch_row($res) ) {
				if ( ! file_exists(PATH_site.$path.$row[1]) ) { continue; }
				if ( ! is_file(PATH_site.'typo3temp/tx_mwimagemap/'.md5($path.$row[1])) ) { $this->create_thumb($row[1], $path); }
				$tw = 5;
				$isize = getimagesize(PATH_site.'typo3temp/tx_mwimagemap/'.md5($path.$row[1]));
				if(isset($isize[0]) ) { $tw = $isize[0]; }
				
				$mA['###ID###'] = $row[0];
				$mA['###NAME###'] = $row[2];
				$mA['###PATHID###'] = t3lib_div::_GP('id');
				$mA['###SRC###'] = t3lib_div::getIndpEnv('TYPO3_SITE_URL').'typo3temp/tx_mwimagemap/'.md5($path.$row[1]);
				$mA['###TW###'] = $tw;
				
				$content .= '<td>'.$this->cObj->substituteMarkerArray($list_item, $mA).'</td>';
				$markerArray['###OPTIONS###'] .= '<option value="'.$row[0].'">'.$row[2].'</option>';
				$files[] = $row[1];
			}

			$markerArray['###FILE_OPTIONS###'] = '';
			if ( $dh = opendir(PATH_site . $path) ) {
				while ( $dir = readdir($dh) ) {
					if ( $dir == '.' || $dir == '..' ) continue;
					if ( strpos($dir, '.png') !== strlen($dir) - 4 && strpos($dir, '.jpg') !== strlen($dir) - 4 && strpos($dir, '.jpeg') !== strlen($dir) - 5 && strpos($dir, '.gif') !== strlen($dir) - 4 )
						continue;
					if ( strpos($dir, 't_') === 0 ) $tmp = substr($dir, 2);
					else $tmp = $dir;
					if ( in_array( $tmp, $files ) ) continue;
					$markerArray['###FILE_OPTIONS###'] .= '<option>'.$dir.'</option>';
				}
				closedir($dh);
			}

			$markerArray['###CREATEMAP###'] = $GLOBALS['LANG']->getLL('upload_pic');
			$markerArray['###L_SHOWOPTIONS###'] = $GLOBALS['LANG']->getLL('show options');
			$markerArray['###L_HIDEOPTIONS###'] = $GLOBALS['LANG']->getLL('hide options');
			
			$this->content .= $this->cObj->substituteMarkerArray($add_part, $markerArray);
			
			if(strlen($content) != 0) {
				$this->content .= $this->cObj->substituteMarkerArray($list_part, array(
				'###LIST###' => $content,
				'###EIM###' => $GLOBALS['LANG']->getLL('eim'),
				'###DELIMG1###' => $GLOBALS['LANG']->getLL('delimg1'),
				'###DELIMG2###' => $GLOBALS['LANG']->getLL('delimg2'),
				'###ERROC###' => $GLOBALS['LANG']->getLL('erroc'),
				'###CONTACTION###' => $GLOBALS['LANG']->getLL('contaction'),
				'###L_SHOWOPTIONS###' => $GLOBALS['LANG']->getLL('show options'),
				'###L_HIDEOPTIONS###' => $GLOBALS['LANG']->getLL('hide options')
				));
			}
		}

	function areaContent() {
		$db =& $GLOBALS['TYPO3_DB'];
		$content = '';
		$area_id = intval(t3lib_div::_GP('area_id'));
		if ( $area_id == 0 ) $area_id = '';
		$map_id = intval(t3lib_div::_GP('map_id'));
		$type_array = array( 'Rectangle', 'Circle', 'Polygon' );

		$template = file_get_contents( MODULE_DIR.'/templates/template_area.html' );
		$add_part = $this->cObj->getSubpart( $template, '###ADD_PART###' );
		$edit_part = $this->cObj->getSubpart( $template, '###EDIT_PART###' );
		$img_part = $this->cObj->getSubpart( $template, '###IMG_PART###' );
		$list_part = $this->cObj->getSubpart( $template, '###LIST_PART###' );
		$list_item = $this->cObj->getSubpart( $template, '###LIST_ITEM###' );
		$frame_part = $this->cObj->getSubpart( $template, '###FRAME_PART###' );
		$addcol_part = $this->cObj->getSubpart( $template, '###ADDCOL_PART###' );
		$fe_part = $this->cObj->getSubpart( $template, '###FE_PART###' );
			
		$edit_rect_part = $this->cObj->getSubpart( $template, '###RECT_PART###' );
		$edit_circle_part = $this->cObj->getSubpart( $template, '###CIRCLE_PART###' );
		$edit_poly_part = $this->cObj->getSubpart( $template, '###POLY_PART###' );
		$edit_points_part = $this->cObj->getSubpart( $template, '###POINTS###' );

		$js_part = $this->cObj->getSubpart( $template, '###JS_PART###' );

		switch ( t3lib_div::_GP('action') ) {
			case 'add':
				if ( intval(t3lib_div::_GP('type')) == MWIM_DEFAULT ) {
					if ( ! ( $res = $db->exec_SELECTquery('id', 'tx_mwimagemap_area', 'mid = '.$map_id.' AND type ='.MWIM_DEFAULT) ) ) {
						$this->err .= 'exec_select sql_error: '.$db->sql_error().'<br />';
						break;
					}
					if ( $db->sql_num_rows($res) > 0 ) {
						$this->err .= $GLOBALS['LANG']->getLL('justonedef').'<br />';
						break;
					}
				}
				if ( trim(t3lib_div::_GP('descript')) === '' ) {
					if ( intval(t3lib_div::_GP('type')) == MWIM_DEFAULT ) { $desc = 'default link'; }
					else {
						if ( ! ( $res = $db->exec_SELECTquery('count(*)', 'tx_mwimagemap_area', 'mid = '.$map_id) ) || ! ( $row = $db->sql_fetch_row($res) ) ) {
							$this->err .= 'exec_SELECT sql_error: '.$db->sql_error().'<br />';
							break;
						}
						$desc = 'area_'.($row[0]+1);
					}
				}
				else { $desc = trim(t3lib_div::_GP('descript')); }
				$link = trim(t3lib_div::_GP('link'));
				if ( $link === '' ) { $link = '#'; }
				if ( ! $db->exec_INSERTquery( 'tx_mwimagemap_area', array( 'type' => intval(t3lib_div::_GP('type')),
					'link' => $link, 'description' => $desc, 'mid' => $map_id, 'color' => $this->extConf['def_color1'] ) ) ) {
					$this->err .= 'add sql_error: '.$db->sql_error().'<br />';
					break;
				}
				if ( ! ( $area_id = $db->sql_insert_id() ) ) {
					$this->err .= 'sql_insert_id() == '.$area_id.'<br />';
					break;
				}
				switch ( intval(t3lib_div::_GP('type')) ) {
					case MWIM_RECTANGLE:
					case MWIM_CIRCLE:
						if ( ! $db->exec_INSERTquery('tx_mwimagemap_point', array( 'aid' => $area_id, 'num' => '0' ) ) || ! $db->exec_INSERTquery('tx_mwimagemap_point', array( 'aid' => $area_id, 'num' => '1' ) ) ) {
							$this->err .= 'add point sql_error: '.$db->sql_error().'<br />';
							break 2;
						}
					break;
					default:
					break;
				}
				
				$active = (t3lib_div::_GP('cb_active') == 1) ? t3lib_div::_GP('cb_active') : 0;
				if ( ! $db->exec_INSERTquery( 'tx_mwimagemap_contentpopup',
				array( 'aid' => $area_id,
				'active' => $active,
				'content_id' => t3lib_div::_GP('cbid'),
				'popup_width' => t3lib_div::_GP('cb_width'),
				'popup_height' => t3lib_div::_GP('cb_height'),
				'popup_x' => t3lib_div::_GP('cb_x'),
				'popup_y' => t3lib_div::_GP('cb_y'),
				'popup_bordercolor' => t3lib_div::_GP('cb_bcol'),
				'popup_borderwidth' => t3lib_div::_GP('cb_borderthickness'),
				'popup_backgroundcolor' => t3lib_div::_GP('cb_bgcol')
				) ) ) {
					$this->err .= 'edit: sql_error: '.$db->sql_error().'<br />';
					break;
				}
				
				$this -> checkFecache();
			break;

			case 'del':
				if ( ! $db->exec_DELETEquery('tx_mwimagemap_area', 'id = ' . $area_id) ) {
					$this->err .= 'delarea sql_error: '.$db->sql_error();
					break;
				}
				if ( ! $db->exec_DELETEquery('tx_mwimagemap_point', 'aid = ' . $area_id) ) { $this->err .= 'delpoint sql_error: '.$db->sql_error(); }
				if ( ! $db->exec_DELETEquery('tx_mwimagemap_contentpopup', 'aid = ' . $area_id) ) { $this->err .= 'delpoint sql_error: '.$db->sql_error(); }
				$this -> checkFecache();
			break;

			case 'edit':
				$_SESSION['mwim_blink'] = intval(t3lib_div::_GP('blink'));
				$this->edit_colors( $map_id );
				if ( ! $area_id	) {
					$this->err .= 'edit: no area_id!<br />';
					break;
				}
				
				$desc = trim(t3lib_div::_GP('descript'));
				if ( $desc === '' ) {
					if ( ! ( $res = $db->exec_SELECTquery('count(*)', 'tx_mwimagemap_area', 'mid = '.$map_id) ) || ! ( $row = $db->sql_fetch_row($res) ) ) {
						$this->err .= 'edit: exec_SELECT sql_error: '.$db->sql_error().'<br />';
						break;
					}
					$desc = 'area_'.($row[0]+1);
				}
				$link = trim(t3lib_div::_GP('link'));
				if ( $link === '' ) { $link = '#'; }
				$param = str_replace('&quot;', '"', trim(t3lib_div::_GP('param')));
				
				$fe_bcol = t3lib_div::_GP('fe_bcol');
				$fe_bths = t3lib_div::_GP('fe_borderthickness');
				if(t3lib_div::_GP('fe_visible') == 1 || t3lib_div::_GP('fe_visible') == 2) {
					if (!preg_match("/^#[a-f0-9]{6}$/i", $fe_bcol) ) { $fe_bcol = t3lib_div::_GP('color'); }
					if(!preg_match("/^[-]?[0-9]+([\.][0-9]+)?$/i", $fe_bths)) { $fe_bths = '1'; }
				}

				$active = (t3lib_div::_GP('cb_active') == 1) ? t3lib_div::_GP('cb_active') : 0;
				if ( ! ( $res = $db->exec_SELECTquery('count(*)', 'tx_mwimagemap_contentpopup', 'aid = '.$area_id) ) || ! ( $row = $db->sql_fetch_row($res) ) ) {
						$this->err .= 'edit: exec_SELECT sql_error: '.$db->sql_error().'<br />';
						break;
				}
				if($row[0] > 0) {
					if ( ! $db->exec_UPDATEquery( 'tx_mwimagemap_contentpopup', 'aid = '.$area_id,
					array(
					'active' => $active,
					'content_id' => t3lib_div::_GP('cbid'),
					'popup_width' => t3lib_div::_GP('cb_width'),
					'popup_height' => t3lib_div::_GP('cb_height'),
					'popup_x' => t3lib_div::_GP('cb_x'),
					'popup_y' => t3lib_div::_GP('cb_y'),
					'popup_bordercolor' => t3lib_div::_GP('cb_bcol'),
					'popup_borderwidth' => t3lib_div::_GP('cb_borderthickness'),
					'popup_backgroundcolor' => t3lib_div::_GP('cb_bgcol')
					) ) ) {
						$this->err .= 'edit: sql_error: '.$db->sql_error().'<br />';
						break;
					}
				}
				else {
					if ( ! $db->exec_INSERTquery( 'tx_mwimagemap_contentpopup',
					array( 'aid' => $area_id,
					'active' => $active,
					'content_id' => t3lib_div::_GP('cbid'),
					'popup_width' => t3lib_div::_GP('cb_width'),
					'popup_height' => t3lib_div::_GP('cb_height'),
					'popup_x' => t3lib_div::_GP('cb_x'),
					'popup_y' => t3lib_div::_GP('cb_y'),
					'popup_bordercolor' => t3lib_div::_GP('cb_bcol'),
					'popup_borderwidth' => t3lib_div::_GP('cb_borderthickness'),
					'popup_backgroundcolor' => t3lib_div::_GP('cb_bgcol')
					) ) ) {
						$this->err .= 'edit: sql_error: '.$db->sql_error().'<br />';
						break;
					}
				}
				
				if ( ! $db->exec_UPDATEquery( 'tx_mwimagemap_area', 'id = '.$area_id,
				array( 'description' => $desc,
				'link' => $link,
				'color' => t3lib_div::_GP('color'),
				'param' => $param,
				'fe_visible' => t3lib_div::_GP('fe_visible'),
				'fe_bordercolor' => $fe_bcol,
				'fe_borderthickness' => $fe_bths
				) ) ) {
					$this->err .= 'edit: sql_error: '.$db->sql_error().'<br />';
					break;
				}
				
				switch ( intval(t3lib_div::_GP('type')) ) {
					case MWIM_RECTANGLE:
						$this->err .= $this->edit_rect($area_id);
					break;

					case MWIM_CIRCLE:
						$this->err .= $this->edit_circle($area_id);
					break;

					case MWIM_POLYGON:
						$_SESSION['mwim_close'] = intval(t3lib_div::_GP('close'));
						$_SESSION['mwim_spoint'] = intval(t3lib_div::_GP('spoint'));
						$_SESSION['mwim_epoint'] = intval(t3lib_div::_GP('epoint'));
						$this->err .= $this->edit_polygon($area_id);
					break;

					default:
					break;
				}
				$this->createFePics($map_id, $area_id);
				$this -> checkFecache();				
			break;

			case 'move':
				$x = intval(trim(t3lib_div::_GP('xmov')));
				$y = intval(trim(t3lib_div::_GP('ymov')));
				if ( !$x && !$y ) { break; }
				$arr = explode(',', t3lib_div::_GP('areaids'));
				foreach( $arr as $val ) {
					if ( ! ( $res = $db->exec_SELECTquery('type', 'tx_mwimagemap_area', 'id = '.$val) ) ) {
						$this->err .= 'exec_select sql_error: '.$db->sql_error().'<br />';
						continue;
					}
					if ( ! ( $row1 = $db->sql_fetch_row($res) ) ) {
						$this->err .= 'area '.$val.' not found!<br />';
						continue;
					}
					if ( ! ( $res = $db->exec_SELECTquery('id, x, y, num', 'tx_mwimagemap_point', 'aid = '.$val) ) ) {
						$this->err .= 'exec_select sql_error: '.$db->sql_error().'<br />';
						continue;
					}
					while ( $row = $db->sql_fetch_row($res) ) {
						if ( $row1[0] != MWIM_POLYGON && $row[3] == 0 ) { continue; }
						if ( ! $db->exec_UPDATEquery('tx_mwimagemap_point', 'id='.$row[0], array( 'x' => $row[1] + $x, 'y' => $row[2] + $y )) ) {
							$this->err .= 'exec_update sql_error: '.$db->sql_error().'<br />';
						}
					}
				}
				$this->createFePics($map_id, $val);
				$this -> checkFecache();
			break;
		}

		if ( ! ( $res = $db->exec_SELECTquery( 'name, file, folder', 'tx_mwimagemap_map', 'id = ' . $map_id ) ) ) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('err'), 'sql_error: '.$db->sql_error().'<br />Can\'t open imagemap '.$map_id);
			return;
		}
		if ( ! ( $row = $db->sql_fetch_row($res) ) ) {
			$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('err'), intval($GLOBALS['id']).'Can\'t open imagemap '.$map_id);
			return;
		}

		preg_match('/(.*?)\/(typo3|typo3conf)\/ext\//i', $_SERVER['SCRIPT_URL'], $match);
		$mA['###TYPO3_PATH###'] = $match[1];

		// Write Picture Section to buffer
		$markerArray['###NAME###'] = $row[0];
		$markerArray['###SRC###'] = t3lib_div::getIndpEnv('TYPO3_SITE_URL'). $row[2] . $row[1];
		$markerArray['###AID###'] = $area_id;
		$markerArray['###MID###'] = $map_id;
		$markerArray['###TOGGLED###'] = $GLOBALS['LANG']->getLL('toggle down');
		$markerArray['###TOGGLEU###'] = $GLOBALS['LANG']->getLL('toggle up');	

		$img_size = GetImageSize( PATH_site . $row[2] . $row[1] );
		$markerArray['###W###'] = $img_size[0];
		$markerArray['###H###'] = $img_size[1];
		$markerArray['###PATHID###'] = t3lib_div::_GP('id');
		$content .= $this->doc->section($GLOBALS['LANG']->getLL('Picture').':', '<span id="u"></span>'.$this->cObj->substituteMarkerArray($img_part, $markerArray), 0, 1).'<span id="d"></span>';
		unset( $markerArray );

		// Edit part schreiben
		$addscript = '';
		if ( $area_id ) {
			if ( ! ( $res = $db->exec_SELECTquery('description, link, type, color, param', 'tx_mwimagemap_area', 'id = '.$area_id) ) ) {
				$this->err .= 'select area: $db->sql_error(): '.$db->sql_error().'<br />';
			}
			elseif ( ( $row = $db->sql_fetch_row($res) ) ) {
				$markerArray['###L_DESCRIPTION###'] = $GLOBALS['LANG']->getLL('Description');
				$markerArray['###L_LINK###'] = $GLOBALS['LANG']->getLL('Link');
				$markerArray['###L_EDIT###'] = $GLOBALS['LANG']->getLL('save');
				$markerArray['###L_PARAM###'] = $GLOBALS['LANG']->getLL('param');
				$markerArray['###MID###'] = $map_id;
				$markerArray['###AID###'] = $area_id;
				$markerArray['###DESCRIPT###'] = $row[0];
				$markerArray['###LINK###'] = $row[1];
				$markerArray['###VTYPE###'] = $row[2];
				$markerArray['###PARAM###'] = str_replace('"', '&quot;', $row[4]);
					
				switch ( $row[2] ) {
					case MWIM_RECTANGLE:
						$markerArray['###FRAME###'] = $this->write_edit_frame( $frame_part, $addcol_part, $area_id );
						$markerArray['###FEOPTIONS###'] = $this->write_edit_feoptions( $fe_part, $area_id );
						$markerArray['###TYPE###'] = $GLOBALS['LANG']->getLL('Rectangle');
						$markerArray['###L_TYPE###'] = '<img id="area_shape_img" src="img/'.$row[2].'_1.gif" alt="'.$GLOBALS['LANG']->getLL('Type').': '.$GLOBALS['LANG']->getLL('Rectangle').'" title="'.$GLOBALS['LANG']->getLL('Type').': '.$GLOBALS['LANG']->getLL('Rectangle').'" style="background-color:'.$row[3].'" />';
					break;
					
					case MWIM_CIRCLE:
						$markerArray['###FRAME###'] = $this->write_edit_frame( $frame_part, $addcol_part, $area_id );
						$markerArray['###FEOPTIONS###'] = $this->write_edit_feoptions( $fe_part, $area_id );
						$markerArray['###TYPE###'] = $GLOBALS['LANG']->getLL('Circle');
						$markerArray['###L_TYPE###'] = '<img	id="area_shape_img" src="img/'.$row[2].'_1.gif" alt="'.$GLOBALS['LANG']->getLL('Type').': '.$GLOBALS['LANG']->getLL('Circle').'" title="'.$GLOBALS['LANG']->getLL('Type').': '.$GLOBALS['LANG']->getLL('Circle').'" style="background-color:'.$row[3].'" />';
					break;
					
					case MWIM_POLYGON:
						$markerArray['###FRAME###'] = $this->write_edit_frame( $frame_part, $addcol_part, $area_id );
						$markerArray['###FEOPTIONS###'] = $this->write_edit_feoptions( $fe_part, $area_id );
						$markerArray['###TYPE###'] = $GLOBALS['LANG']->getLL('Polygon');
						$markerArray['###L_TYPE###'] = '<img id="area_shape_img" src="img/'.$row[2].'_1.gif" alt="'.$GLOBALS['LANG']->getLL('Type').': '.$GLOBALS['LANG']->getLL('Polygon').'" title="'.$GLOBALS['LANG']->getLL('Type').': '.$GLOBALS['LANG']->getLL('Polygon').'" style="background-color:'.$row[3].'" />';
					break;
					
					case MWIM_DEFAULT:
						$markerArray['###FRAME###'] = '';
						$markerArray['###FEOPTIONS###'] = '';
						$markerArray['###TYPE###'] = $GLOBALS['LANG']->getLL('default');
						$markerArray['###L_TYPE###'] = '<img id="area_shape_img" src="img/'.$row[2].'_1.gif" alt="'.$GLOBALS['LANG']->getLL('Type').': '.$GLOBALS['LANG']->getLL('default').'" title="'.$GLOBALS['LANG']->getLL('Type').': '.$GLOBALS['LANG']->getLL('default').'" style="background-color:'.$row[3].'" />';
					break;
					
					default:
						$markerArray['###TYPE###'] = 'Error';
				}
				for ( $i=0;$i<MWIM_NUM_OBJ;++$i ) {
					$markerArray['###SELECT'.$i.'###'] = '';
				}
				$markerArray['###L_COLOR###'] = $GLOBALS['LANG']->getLL('color');
				$markerArray['###SELECT'.$row[3].'###'] = 'selected';
				$markerArray['###TYP_SPECIFIC1###'] = '';
				$markerArray['###L_BLINK###'] = $GLOBALS['LANG']->getLL('do_blink');
				$markerArray['###LINK_FUNC###'] = $this->createLinkToBrowseLinks('edit_form', 'link');
				$markerArray['###L_FE_HIDDEN###'] = $GLOBALS['LANG']->getLL('fe_hidden');
				$markerArray['###L_FE_VISIBLE###'] = $GLOBALS['LANG']->getLL('fe_visible');
				$markerArray['###L_FE_BORDERCOLOR###'] = $GLOBALS['LANG']->getLL('fe_bordercolor');
				$markerArray['###L_FE_BORDERTHICKNESS###'] = $GLOBALS['LANG']->getLL('fe_borderthickness');
				$markerArray['###L_FE_MOUSEOVER###'] = $GLOBALS['LANG']->getLL('fe_mouseover');
				$markerArray['###FE_OPTIONS###'] = $GLOBALS['LANG']->getLL('fe_options');
				$markerArray['###CONTENT_BOX###'] = $GLOBALS['LANG']->getLL('contentbox');
				$markerArray['###L_CB_ACTIVE###'] = $GLOBALS['LANG']->getLL('contentboxactivated');
				$markerArray['###L_CB_WIDTH###'] = $GLOBALS['LANG']->getLL('X Size');
				$markerArray['###L_CB_HEIGHT###'] = $GLOBALS['LANG']->getLL('Y Size');
				$markerArray['###L_CB_X###'] = $GLOBALS['LANG']->getLL('X Pos');
				$markerArray['###L_CB_Y###'] = $GLOBALS['LANG']->getLL('Y Pos');
				$markerArray['###L_CB_ID###'] = $GLOBALS['LANG']->getLL('contentboxid');
				$markerArray['###L_FE_BGCOLOR###'] = $GLOBALS['LANG']->getLL('fe_bgcolor');
				$markerArray['###DEL_ELEMENT###'] = $GLOBALS['LANG']->getLL('delelement');
				$markerArray['###ADD_ELEMENT###'] = $GLOBALS['LANG']->getLL('browseforelements');
				
				if ( ! isset($_SESSION['mwim_blink']) ) { $_SESSION['mwim_blink'] = 1; }
				if ( $_SESSION['mwim_blink'] ) { $markerArray['###BLINK###'] = 'checked'; }
				else { $markerArray['###BLINK###'] = ''; }
					
				switch ( intval($row[2]) ) {
					case MWIM_RECTANGLE:
						$markerArray['###TYP_SPECIFIC###'] = $this->write_edit_rect( $edit_rect_part, $area_id );
					break;

					case MWIM_CIRCLE:
						$markerArray['###TYP_SPECIFIC###'] = $this->write_edit_circle( $edit_circle_part, $area_id );
					break;

					case MWIM_POLYGON:
						if ( ! ( $res = $db->exec_SELECTquery('count(*)', 'tx_mwimagemap_point', 'aid = '.$area_id) ) || ! ( $row = $db->sql_fetch_row($res) ) ) {
							$this->err .= 'edit_poly: sql_error: '.$db->sql_error().'<br />';
						}
						$markerArray['###L_ADDPT###'] = $GLOBALS['LANG']->getLL('Add Point');
						$markerArray['###L_ADD###'] = $GLOBALS['LANG']->getLL('Add');
						$markerArray['###L_POS###'] = $GLOBALS['LANG']->getLL('Position');
						$markerArray['###L_BEGIN###'] = $GLOBALS['LANG']->getLL('First Position');
						$markerArray['###L_END###'] = $GLOBALS['LANG']->getLL('Last Position');
						$markerArray['###L_XPOS###'] = $GLOBALS['LANG']->getLL('X Pos');
						$markerArray['###L_YPOS###'] = $GLOBALS['LANG']->getLL('Y Pos');
						$markerArray['###L_MSTART###'] = $GLOBALS['LANG']->getLL('mark startpoint');
						$markerArray['###L_CLOSE###'] = $GLOBALS['LANG']->getLL('close');
						$markerArray['###L_MEND###'] = $GLOBALS['LANG']->getLL('mark endpoint');
						if ( $_SESSION['mwim_close'] ) { $markerArray['###CLOSE###'] = 'checked'; }
						else { $markerArray['###CLOSE###'] = ''; }
						if ( $_SESSION['mwim_spoint'] ) { $markerArray['###SPOINT###'] = 'checked'; }
						else { $markerArray['###SPOINT###'] = ''; }
						if ( $_SESSION['mwim_epoint'] ) { $markerArray['###EPOINT###'] = 'checked'; }
						else { $markerArray['###EPOINT###'] = ''; }
						$markerArray['###L_EDIT_PT###'] = $GLOBALS['LANG']->getLL('Edit Point');
						$markerArray['###L_DEL###'] = $GLOBALS['LANG']->getLL('Delete Point');
						$markerArray['###L_DEL1###'] = $GLOBALS['LANG']->getLL('Delete Point1');
						$markerArray['###L_X###'] = $GLOBALS['LANG']->getLL('X Pos');
						$markerArray['###L_Y###'] = $GLOBALS['LANG']->getLL('Y Pos');
						$markerArray['###L_POS###'] = $GLOBALS['LANG']->getLL('Position');
						$markerArray['###L_DELETED###'] = $GLOBALS['LANG']->getLL('deleted');
						$markerArray['###L_NUM###'] = $GLOBALS['LANG']->getLL('Point Number');
						$markerArray['###L_NUM1###'] = $GLOBALS['LANG']->getLL('Point Number1');
						$markerArray['###NUM###'] = $row[0];
						$markerArray['###TYP_SPECIFIC###'] = $this->cObj->substituteMarkerArray($edit_poly_part, $markerArray);
						$markerArray['###TYP_SPECIFIC1###'] = $this->write_edit_polygon( $edit_points_part, $area_id );
						$markerArray['###POLYCLICK###'] = $GLOBALS['LANG']->getLL('polyclick');
					break;

					case MWIM_DEFAULT:
						$markerArray['###TYP_SPECIFIC###'] = '<script type="text/javascript">document.getElementById("blink").disabled = true;</script>';
					break;

					default:
						$markerArray['###TYP_SPECIFIC###'] = '';
				}
				$markerArray['###PATHID###'] = t3lib_div::_GP('id');
				$markerArray['###L_EDITAREA###'] = $GLOBALS['LANG']->getLL('Edit Link Area:');
				$markerArray['###L_SHOWOPTIONS###'] = $GLOBALS['LANG']->getLL('show options');
				$markerArray['###L_HIDEOPTIONS###'] = $GLOBALS['LANG']->getLL('hide options');
				$markerArray['###L_BORDER###'] = $GLOBALS['LANG']->getLL('border');
				$markerArray['###L_ADDCOLOR###'] = $GLOBALS['LANG']->getLL('Add color');
				$markerArray['###L_NAME###'] = $GLOBALS['LANG']->getLL('name');
				$content .= $this->cObj->substituteMarkerArray($edit_part, $markerArray);
				unset( $markerArray );
			}
		}
		else { $addscript = '<script type="Text/Javascript">function check_chg_vars() { return true; }</script>'; }
			
		// Write the Add Link Section to Buffer
		$markerArray['###L_DESCRIPTION###'] = $GLOBALS['LANG']->getLL('Description');
		$markerArray['###L_TYPE###'] = $GLOBALS['LANG']->getLL('Type');
		$markerArray['###L_LINK###'] = $GLOBALS['LANG']->getLL('Link');
		$markerArray['###L_CIRCLE###'] = $GLOBALS['LANG']->getLL('Circle');
		$markerArray['###L_RECTANGLE###'] = $GLOBALS['LANG']->getLL('Rectangle');
		$markerArray['###L_POLYGON###'] = $GLOBALS['LANG']->getLL('Polygon');
		$markerArray['###L_DEFAULT###'] = $GLOBALS['LANG']->getLL('default');
		$markerArray['###L_ADD###'] = $GLOBALS['LANG']->getLL('Add');
		$markerArray['###LINK_FUNC###'] = $this->createLinkToBrowseLinks('add_form', 'link');
		$markerArray['###MID###'] = $map_id;
		$markerArray['###PATHID###'] = t3lib_div::_GP('id');
		$markerArray['###ADDLINKAREA###'] = $GLOBALS['LANG']->getLL('Add Link Area:');
		$markerArray['###L_SHOWOPTIONS###'] = $GLOBALS['LANG']->getLL('show options');
		$markerArray['###L_HIDEOPTIONS###'] = $GLOBALS['LANG']->getLL('hide options');
		$markerArray['###CHGSCRIPT###'] = $addscript;
			
		$content .= $this->cObj->substituteMarkerArray($add_part, $markerArray);

		// Write List of Areas to Buffer
		// and the JS draw instructions to draw the areas
		if ( ! ( $res = $db->exec_SELECTquery( 'id, description, link, type, color', 'tx_mwimagemap_area', 'mid = ' . $map_id ) ) ) {
			$this->err .= 'select list of areas: sql_error: '.$db->sql_error().'<br />';
		}
		elseif ( $db->sql_num_rows( $res ) > 0 ) {
			unset($markerArray);
			$markerArray['###MID###'] = $map_id;
			$markerArray['###MOVEX###'] = $GLOBALS['LANG']->getLL('movex');
			$markerArray['###MOVEY###'] = $GLOBALS['LANG']->getLL('movey');
			$markerArray['###MOVE###'] = $GLOBALS['LANG']->getLL('move');
			$markerArray['###PATHID###'] = t3lib_div::_GP('id');
			$js_mA['###L_DISCARD###'] = $GLOBALS['LANG']->getLL('discard');
			$js_mA['###AID###'] = $area_id;
			$js_mA['###MID###'] = $map_id;
			$js_mA['###W###'] = $img_size[0];
			$js_mA['###H###'] = $img_size[1];
			$js_mA['###PATHID###'] = t3lib_div::_GP('id');
			if ( $area_id ) { $js_mA['###IMGONCLICK###'] = 'imgonclick'; }
			else						{ $js_mA['###IMGONCLICK###'] = 'is_a_obj'; }
			$mA['###L_DEL###'] = $GLOBALS['LANG']->getLL('del');
			$mA['###L_AREA###'] = $GLOBALS['LANG']->getLL('area');
			$i = 0;
			while ( $row = $db->sql_fetch_row($res) ) {
				// The JS draw instructions
				switch( intval($row[3]) ) {
					case MWIM_RECTANGLE:
						$js_mA['###GLOBAL_JS_DRAW###'] .= $this->write_js_rect( $row[0], $row[4] );
					break;
						
					case MWIM_CIRCLE:
						$js_mA['###GLOBAL_JS_DRAW###'] .= $this->write_js_circle( $row[0], $row[4] );
					break;
						
					case MWIM_POLYGON:
						$js_mA['###GLOBAL_JS_DRAW###'] .= $this->write_js_polygon( $row[0], $row[4] );
					break;
				}
				if ( $row[0] == $area_id && $row[3] == MWIM_DEFAULT ) { $js_mA['###IMGONCLICK###'] = 'is_a_obj'; }
				$mA['###AID###'] = $row[0];
				$mA['###MID###'] = $map_id;
				$mA['###DESCRIPT###'] = $row[1];
				$mA['###LINK###'] = $row[2];
				$mA['###TYPE###'] = $GLOBALS['LANG']->getLL($type_array[$row[3]]);
				$mA['###XTYPE###'] = $row[3];
				$mA['###FCOLOR###'] = $row[4];
				$mA['###L_TYPE###'] = $GLOBALS['LANG']->getLL('Type');
				$mA['###L_LINK###'] = $GLOBALS['LANG']->getLL('Link');
				$mA['###NUM###'] = $i++;
				$mA['###PATHID###'] = t3lib_div::_GP('id');
				$mA['###DELETE###'] = t3lib_div::_GP('del');
				$markerArray['###ITEMS###'] .= $this->cObj->substituteMarkerArray( $list_item, $mA );
			}
			$markerArray['###AID###'] = $area_id;
			$markerArray['###AREALIST###'] = $GLOBALS['LANG']->getLL('Link Area List:');
			$markerArray['###L_SHOWOPTIONS###'] = $GLOBALS['LANG']->getLL('show options');
			$markerArray['###L_HIDEOPTIONS###'] = $GLOBALS['LANG']->getLL('hide options');
			$markerArray['###SELECTEDAREAS###'] = $GLOBALS['LANG']->getLL('selected areas');
				
			$content .= $this->cObj->substituteMarkerArray( $list_part, $markerArray );
			$content .= $this->cObj->substituteMarkerArray( $js_part, $js_mA );
		}

		// Write errors
		if ( $this->err ) { $this->content .= $this->doc->section($GLOBALS['LANG']->getLL('err'), $this->err, 0, 1); }

		// Write buffer
		$this->content .= $content;
		return TRUE;
	}

	function edit_rect( $aid ) {
		$db =& $GLOBALS['TYPO3_DB'];
		if ( ! $db->exec_UPDATEquery( 'tx_mwimagemap_point', 'aid = '.$aid.' AND num = 0',
		array( 'x' => intval(t3lib_div::_GP('xsize')),
		'y' => intval(t3lib_div::_GP('ysize')) ) ) ) {
			return 'edit_rect: sql_error: '.$db->sql_error().'<br />';
		}
		if ( ! $db->exec_UPDATEquery( 'tx_mwimagemap_point', 'aid = '.$aid.' AND num = 1',
		array( 'x' => intval(t3lib_div::_GP('xpos')), 'y' => intval(t3lib_div::_GP('ypos')) ) ) ) {
			return 'edit_rect: sql_error: '.$db->sql_error().'<br />';
		}
			return '';
	}

	function edit_circle( $aid ) {
		$db =& $GLOBALS['TYPO3_DB'];
			
		if ( ! $db->exec_UPDATEquery( 'tx_mwimagemap_point', 'aid = '.$aid.' AND num = 0', array( 'x' => intval(t3lib_div::_GP('radius')) ) ) ) {
			return 'edit_circle: sql_error: '.$db->sql_error().'<br />';
		}
			
		if ( ! $db->exec_UPDATEquery( 'tx_mwimagemap_point', 'aid = '.$aid.' AND num = 1',
		array( 'x' => intval(t3lib_div::_GP('xpos')), 'y' => intval(t3lib_div::_GP('ypos')) ) ) ) {
			return 'edit_circle: sql_error: '.$db->sql_error().'<br />';
		}
		return '';
	}

	function edit_polygon( $aid ) {
		$db =& $GLOBALS['TYPO3_DB'];

		if ( ! $db->exec_DELETEquery('tx_mwimagemap_point', 'aid = '.$aid) ) {
			return 'edit_polygon: exec_DELETE sql_error: '.$db->sql_error();
		}
		for ( $i = 1; $i <= t3lib_div::_GP('polynum'); $i++ ) {
			if ( ! $db->exec_INSERTquery('tx_mwimagemap_point', array( 'aid' => $aid, 'num' => $i, 'x' => t3lib_div::_GP('xpos'.$i), 'y' => t3lib_div::_GP('ypos'.$i) ) ) ) {
				return 'edit_polygon exec_INSERT a point sql_error: '.$db->sql_error();
			}
		}	
		return '';
	}
		
	function edit_colors( $mid ) {
		$db =& $GLOBALS['TYPO3_DB'];
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mwimagemap']);
		$addcols = explode(',', t3lib_div::_GP('addcols'));
		$addcolnames = explode(',', t3lib_div::_GP('addcolnames'));
		for($i = 0;$i<count($addcols);$i++) {
			if(strlen($addcols[$i]) != 0) {
				if ( ! $db->exec_INSERTquery('tx_mwimagemap_bcolors', array( 'mid' => $mid, 'colorname' => $addcolnames[$i], 'color' => $addcols[$i] ) ) ) {
					return 'insert a new color sql_error: '.$db->sql_error();
				}
			}
		}
		$delcols = explode(',', t3lib_div::_GP('delcols'));
		$delcolnames = explode(',', t3lib_div::_GP('delcolnames'));
		$colname = '#000000';
		$i = 1;
		while($i < 11) {
			if(strlen($extConf['def_color'.$i]) != 0) {
				$carr = explode('|', $extConf['def_color'.$i]);
				$colname = $carr[0];
				break;
			}
			$i++;
		}
			
		for($i=0;$i<count($delcols);$i++) {
			$update = 'mid = '.$mid.' AND color=\''.$delcols[$i].'\'';
			$delete = 'mid = '.$mid.' AND color=\''.$delcols[$i].'\' and colorname=\''.$delcolnames[$i].'\'';
				
			if($extConf['overall_colors'] == 1) {
				$update = 'color=\''.$delcols[$i].'\'';
				$delete = 'color=\''.$delcols[$i].'\' and colorname=\''.$delcolnames[$i].'\'';
			}
			
			if ( ! $db->exec_DELETEquery( 'tx_mwimagemap_bcolors', $delete ) ) {
				return 'delete colors sql error: '.$db->sql_error();
			}
			
			if ( ! $db->exec_UPDATEquery( 'tx_mwimagemap_area', $update, array( 'color' => $colname ) ) ) {
				return 'update area colors sql error: '.$db->sql_error();
			}
		}
	}
		
	function write_edit_feoptions( $tmpl, $aid ) {
		$db =& $GLOBALS['TYPO3_DB'];
			
		$mA['###L_FE_HIDDEN###'] = $GLOBALS['LANG']->getLL('fe_hidden');
		$mA['###L_FE_VISIBLE###'] = $GLOBALS['LANG']->getLL('fe_visible');
		$mA['###L_FE_BORDERCOLOR###'] = $GLOBALS['LANG']->getLL('fe_bordercolor');
		$mA['###L_FE_BORDERTHICKNESS###'] = $GLOBALS['LANG']->getLL('fe_borderthickness');
		$mA['###L_FE_MOUSEOVER###'] = $GLOBALS['LANG']->getLL('fe_mouseover');
		$mA['###FE_OPTIONS###'] = $GLOBALS['LANG']->getLL('fe_options');
		$mA['###CONTENT_BOX###'] = $GLOBALS['LANG']->getLL('contentbox');
		$mA['###L_CB_ACTIVE###'] = $GLOBALS['LANG']->getLL('contentboxactivated');
		$mA['###L_CB_WIDTH###'] = $GLOBALS['LANG']->getLL('X Size');
		$mA['###L_CB_HEIGHT###'] = $GLOBALS['LANG']->getLL('Y Size');
		$mA['###L_CB_X###'] = $GLOBALS['LANG']->getLL('X Pos');
		$mA['###L_CB_Y###'] = $GLOBALS['LANG']->getLL('Y Pos');
		$mA['###L_CB_ID###'] = $GLOBALS['LANG']->getLL('contentboxid');
		$mA['###L_FE_BGCOLOR###'] = $GLOBALS['LANG']->getLL('fe_bgcolor');
		$mA['###DEL_ELEMENT###'] = $GLOBALS['LANG']->getLL('delelement');
		$mA['###ADD_ELEMENT###'] = $GLOBALS['LANG']->getLL('browseforelements');
		
		$mA['###AID###'] = $aid;
		$mA['###L_CBWARNING_DISPLAY###'] = 'none';
		$mA['###CB_WARNING###'] = '';
		$mA['###CBCID###'] = '';
		$mA['###CHECK_CB_ACTIVE###'] = '';
		$mA['###CBW###'] = '';
		$mA['###CBH###'] = '';
		$mA['###CBX###'] = '';
		$mA['###CBY###'] = '';
		$mA['###CB_BCOL###'] = '';
		$mA['###CBT###'] = '';
		$mA['###CB_BGCOL###'] = '';
		$mA['###L_CB_TEXT###'] = '';
		
		if ( ! ( $res = $db->exec_SELECTquery('content_id,popup_width,popup_height,popup_x,popup_y,popup_bordercolor,popup_borderwidth,active,popup_backgroundcolor', 'tx_mwimagemap_contentpopup', 'aid = '.$aid) ) ) {
			$this->err .= 'select area: $db->sql_error(): '.$db->sql_error().'<br />';
		}
		elseif ( ( $row = $db->sql_fetch_row($res) ) ) {
			$btitle = '';
			if ( ! ( $res2 = $db->exec_SELECTquery('header,hidden,deleted', 'tt_content', 'uid = '.$row[0]) ) ) {
				$this->err .= 'select area: $db->sql_error(): '.$db->sql_error().'<br />';
			}
			elseif ( ( $row2 = $db->sql_fetch_row($res2) ) ) {
				$btitle = (strlen($row2[0]) > 0) ? $row2[0] : '['.$row[0].']';
				if($row2[1] == 1) {
					$mA['###L_CBWARNING_DISPLAY###'] = 'block';
					$mA['###CB_WARNING###'] = $GLOBALS['LANG']->getLL('elementhidden');
				}
				else if($row2[2] == 1) {
					$mA['###L_CBWARNING_DISPLAY###'] = 'block';
					$mA['###CB_WARNING###'] = $GLOBALS['LANG']->getLL('elementdeleted');
				}
				else {
					$mA['###L_CBWARNING_DISPLAY###'] = 'none';
					$mA['###CB_WARNING###'] = '';
				}
			}
			$mA['###CBCID###'] = $row[0];
			$mA['###CHECK_CB_ACTIVE###'] = ($row[7] == 1) ? 'checked ="checked"' : '';
			$mA['###CBW###'] = $row[1];
			$mA['###CBH###'] = $row[2];
			$mA['###CBX###'] = $row[3];
			$mA['###CBY###'] = $row[4];
			$mA['###CB_BCOL###'] = $row[5];
			$mA['###CBT###'] = $row[6];
			$mA['###CB_BGCOL###'] = $row[8];
			$mA['###L_CB_TEXT###'] = $btitle;
			$mA['###L_CB_DISPLAY###'] = 'block';
		}
		
		if ( ! ( $res = $db->exec_SELECTquery('fe_bordercolor, fe_borderthickness, fe_visible', 'tx_mwimagemap_area', 'id = '.$aid) ) ) {
			$this->err .= 'select area: $db->sql_error(): '.$db->sql_error().'<br />';
		}
		elseif ( ( $row = $db->sql_fetch_row($res) ) ) {
			$mA['###FE_BCOL###'] = $row[0];
			$mA['###BT###'] = $row[1];
			$mA['###CHECK_FE_VISIBLE'.$row[2].'###'] = "checked";
			for($i=0;$i<3;$i++) {
				if($i != $row[2]) { $mA['###CHECK_FE_VISIBLE'.$i.'###'] = ""; }
			}
		}
		return $this->cObj->substituteMarkerArray( $tmpl, $mA );
	}
			
	function write_edit_frame(&$tmpl, &$tmpl2, $aid) {
		$db =& $GLOBALS['TYPO3_DB'];
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mwimagemap']);
			
		$mA['###L_SHOWOPTIONS###'] = $GLOBALS['LANG']->getLL('show options');
		$mA['###L_HIDEOPTIONS###'] = $GLOBALS['LANG']->getLL('hide options');
		$mA['###L_BORDER###']			= $GLOBALS['LANG']->getLL('border');
		$mA['###ADDCOLOR###']			= ($extConf['add_colors'] == 1) ? $this->write_edit_fcol($tmpl2, $aid) : '';
			
		$color = '#000000';
		$mid = 0;
		if ( ! ( $res = $db->exec_SELECTquery( 'color,mid', 'tx_mwimagemap_area', 'id = ' . $aid ) ) ) {
			$this->err .= 'area color: sql_error: '.$db->sql_error().'<br />';
		}
		elseif ( $db->sql_num_rows( $res ) > 0 ) {
			while ( $row = $db->sql_fetch_row($res) ) {
				$color = $row[0];
				$mid	 = $row[1];
			}
		}
			
		$delvisibility = 'hidden';
		$coloptions = '';
		$ocols = 0;
		for($i=1;$i<11;$i++) {
			if(strlen($extConf['def_color'.$i]) != 0) {
				$carr = explode('|', $extConf['def_color'.$i]);
				$carr2 = explode(',', $carr[1]);
				$sel = ($carr[0] == $color) ? ' selected' : '';
				$colname = $carr[0];
				for($j=0;$j<count($carr2);$j++) {
					if(preg_match("/".$GLOBALS['LANG']->lang."\:/i", $carr2[$j])) { $colname = str_replace($GLOBALS['LANG']->lang.':', '', $carr2[$j]); }
				}
				$coloptions .= '<option value="'.$carr[0].'" '.$sel.'>'.$colname.'</option>'."\n";
				$ocols++;
			}
		}
		if($extConf['add_colors'] == 1) {
			$csel = array('color,colorname', 'tx_mwimagemap_bcolors', 'mid = ' . $mid);
			if($extConf['overall_colors'] == 1) { $csel = array('color,colorname', 'tx_mwimagemap_bcolors', ''); }
				
			if ( ! ( $res = $db->exec_SELECTquery($csel[0], $csel[1], $csel[2]) ) ) { $this->err .= 'added colors: sql_error: '.$db->sql_error().'<br />'; }
			elseif ( $db->sql_num_rows( $res ) > 0 ) {
				while ( $row = $db->sql_fetch_row($res) ) {
					$sel = ($row[0] == $color) ? ' selected' : '';
					if($row[0] == $color) { $delvisibility = 'visible'; }
					$coloptions .= '<option value="'.$row[0].'" '.$sel.'>'.$row[1].'</option>'."\n";
				}
			}
		}
		$mA['###VISIBLE###'] = $delvisibility;
		$mA['###COLORS###'] = $coloptions;
		$mA['###DELCOL###'] = $extConf['add_colors'];
		$mA['###OCOL###']	 = $ocols;
			
		return $this->cObj->substituteMarkerArray( $tmpl, $mA );
	}

	function write_edit_fcol(&$tmpl, $aid) {
		$db =& $GLOBALS['TYPO3_DB'];
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mwimagemap']);
			
		$mA['###DELETE###'] = $GLOBALS['LANG']->getLL('del');
		$mA['###L_ADDCOLOR###'] = $GLOBALS['LANG']->getLL('Add color');
		$mA['###L_NAME###'] = $GLOBALS['LANG']->getLL('name');
		$mA['###L_COLOR###'] = $GLOBALS['LANG']->getLL('color');
		$mA['###DELCONF###'] = ($extConf['overall_colors'] == 1) ? $GLOBALS['LANG']->getLL('delconf1') : $GLOBALS['LANG']->getLL('delconf2');
			
		return $this->cObj->substituteMarkerArray( $tmpl, $mA );
	}
		
	function write_edit_rect( &$tmpl, $aid ) {
		$db =& $GLOBALS['TYPO3_DB'];

		if ( ! ( $res = $db->exec_SELECTquery('x, y', 'tx_mwimagemap_point', 'aid = '.$aid, '', 'num') ) ) {
			$this->err .= 'write_edit_rect: sql_error: '.$db->sql_error().'<br />';
			return '';
		}
		if ( $db->sql_num_rows($res) != 2 ) {
			$this->err .= 'write_edit_rect: num_rows() == '.$db->sql_num_rows($res).'<br />';
			return '';
		}
		$row = $db->sql_fetch_row($res);
			
		$mA['###AID###'] = $aid;
		$mA['###L_XSIZE###'] = $GLOBALS['LANG']->getLL('X Size');
		$mA['###L_YSIZE###'] = $GLOBALS['LANG']->getLL('Y Size');
		$mA['###L_XPOS###'] = $GLOBALS['LANG']->getLL('X Pos');
		$mA['###L_YPOS###'] = $GLOBALS['LANG']->getLL('Y Pos');
		$mA['###L_SET_SIZE###'] = $GLOBALS['LANG']->getLL('setsize');
		$mA['###L_SET_POS###'] = $GLOBALS['LANG']->getLL('setpos');
		$mA['###L_SIZEPOS###'] = $GLOBALS['LANG']->getLL('sizepos');
		$mA['###XSIZE###'] = $row[0];
		$mA['###YSIZE###'] = $row[1];
		$row = $db->sql_fetch_row($res);
		$mA['###XPOS###'] = $row[0];
		$mA['###YPOS###'] = $row[1];

		return $this->cObj->substituteMarkerArray( $tmpl, $mA );
	}

	function write_edit_circle( &$tmpl, $aid ) {
		$db =& $GLOBALS['TYPO3_DB'];

		if ( ! ( $res = $db->exec_SELECTquery('x, y', 'tx_mwimagemap_point', 'aid = '.$aid, '', 'num') ) ) {
			$this->err .= 'write_edit_rect: sql_error: '.$db->sql_error().'<br />';
			return '';
		}
		if ( $db->sql_num_rows($res) != 2 ) {
			$this->err .= 'write_edit_rect: num_rows() == '.$db->sql_num_rows($res).'<br />';
			return '';
		}
	 
		$r = 0;
		$x = 0;
		$y = 0;
		$c = 0;

		while ( $row = $db->sql_fetch_row($res) ) {
			if($c == 0) { $r = $row[0]; }
			else {
				$x = $row[0];
				$y = $row[1];
			}
			$c++;
		}
		
		$mA['###AID###'] = $aid;
		$mA['###L_RADIUS###'] = 'Radius';
		$mA['###L_XPOS###'] = $GLOBALS['LANG']->getLL('X Pos');
		$mA['###L_YPOS###'] = $GLOBALS['LANG']->getLL('Y Pos');
		$mA['###L_SETRADIUS###'] = $GLOBALS['LANG']->getLL('ocsr');
		$mA['###L_SET_POS###'] = $GLOBALS['LANG']->getLL('ocscc');
		$mA['###L_SIZEPOS###'] = $GLOBALS['LANG']->getLL('sizepos');
		$mA['###RADIUS###'] = $r;
		$mA['###XPOS###'] = $x;
		$mA['###YPOS###'] = $y;

		return $this->cObj->substituteMarkerArray( $tmpl, $mA );
	}

	function write_edit_polygon( &$tmpl_points, $aid ) {
		$db =& $GLOBALS['TYPO3_DB'];

		if ( ! ( $res = $db->exec_SELECTquery('x, y', 'tx_mwimagemap_point', 'aid = '.$aid, '', 'num') ) ) {
			$this->err .= 'write_edit_rect: sql_error: '.$db->sql_error().'<br />';
			return '';
		}

		$ret = '<a href="Javascript:a_toggle(\'points\');"><h3 class="uppercase"><img src="img/minus.gif" border="0" id="pointstoggle" alt="'.$GLOBALS['LANG']->getLL('show options').'" title="'.$GLOBALS['LANG']->getLL('show options').'" /> '.$GLOBALS['LANG']->getLL('editpoint').' </h3></a>
			<input type="hidden" name="actpoint" id="actpoint" value="" />
			<table id="points" cellpadding="0" cellspacing="0" style="display:block;border:1px solid #999999; margin:5px 0px 10px 0px; width:100%;">
				<tr>
					<td align="center" style="width:448px;">
						<table cellspacing="8" align="center" style="width:20%;height:100px;"><tbody id="poly_tbl"><tr>';
		$mA_pt['###L_POINT###'] = $GLOBALS['LANG']->getLL('Point Number');
		$mA_pt['###L_DEL###'] = $GLOBALS['LANG']->getLL('Delete Point');
		$mA_pt['###L_ONCLK###'] = $GLOBALS['LANG']->getLL('Edit Point');
		$mA_pt['###L_XPOS###'] = $GLOBALS['LANG']->getLL('X Pos');
		$mA_pt['###L_YPOS###'] = $GLOBALS['LANG']->getLL('Y Pos');
		$i = 1;
		$j = 1;
		while ( $row = $db->sql_fetch_row($res) ) {
			if ( $i != 1 && $i % 5 == 1 ) { $ret .= '</tr><tr>'; }
			$mA_pt['###XPOS###'] = $row[0];
			$mA_pt['###YPOS###'] = $row[1];
			$mA_pt['###NUM###'] = $i++;
			$ret .= $this->cObj->substituteMarkerArray( $tmpl_points, $mA_pt );
		}
		return $ret.'</tr></tbody></table></td></tr></table>';
	}

	function write_js_rect( $aid, $color ) {
		$db =& $GLOBALS['TYPO3_DB'];
		if ( ! ( $res = $db->exec_SELECTquery( 'x, y', 'tx_mwimagemap_point', 'aid = '.$aid, '', 'num' ) ) ) {
			$this->err .= 'write_js_rect: sql_error: '.$db->sql_error().'<br />';
			return '';
		}
		if ( $db->sql_num_rows($res) != 2 ) {
			$this->err .= 'write_js_rect: sql_num_rows == '.$db->sql_num_rows($res).'<br />';
			return '';
		}
		$row = $db->sql_fetch_row( $res );
		$w = $row[0];
		$h = $row[1];
		$row = $db->sql_fetch_row( $res );
			
		return 'objs["'.$aid.'"] = new rectangle('.$row[0].','.$row[1].','.$w.','.$h.','.$aid.',"'.$color."\");\n";
	}

	function write_js_circle( $aid, $color ) {
		$db =& $GLOBALS['TYPO3_DB'];
		if ( ! ( $res = $db->exec_SELECTquery( 'x, y', 'tx_mwimagemap_point', 'aid = '.$aid, '', 'num' ) ) ) {
			$this->err .= 'write_js_circle: sql_error: '.$db->sql_error().'<br />';
			return '';
		}
		if ( $db->sql_num_rows($res) != 2 ) {
			$this->err .= 'write_js_circle: sql_num_rows == '.$db->sql_num_rows($res).'<br />';
			return '';
		}
		$row = $db->sql_fetch_row( $res );
		$r = $row[0];
		$row = $db->sql_fetch_row( $res );
		return 'objs["'.$aid.'"] = new circle('.$row[0].','.$row[1].','.$r.','.$aid.',"'.$color."\");\n";
	}

	function write_js_polygon( $aid, $color ) {
		$db =& $GLOBALS['TYPO3_DB'];
		if ( ! ( $res = $db->exec_SELECTquery( 'x, y', 'tx_mwimagemap_point', 'aid = '.$aid, '', 'num' ) ) ) {
			$this->err .= 'write_js_polygon: sql_error: '.$db->sql_error().'<br />';
			return '';
		}

		$xa = '';
		$ya = '';
		while ( $row = $db->sql_fetch_row($res) ) {
			$xa .= ($xa!=''?',':'').$row[0];
			$ya .= ($ya!=''?',':'').$row[1];
		}
		return 'objs["'.$aid.'"] = new polygon( new Array('.$xa.'), new Array('.$ya.'), '.$aid.',"'.$color.'",'.intval($_SESSION['mwim_close']).','.intval($_SESSION['mwim_spoint']).','.intval($_SESSION['mwim_epoint']).");\n";
	}

	function createLinkToBrowseLinks($form, $field) {
		$browseLinksFile = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('wizard_element_browser');
		$params = array(
			'act' => 'page',
			'mode' => 'wizard',
			'table' => 'tx_mlsurprisecalendar_prizes',
			'field' => $field,
			'P[returnUrl]' => t3lib_div::linkThisScript(),
			'P[formName]' => $form,
			'P[itemName]' => $field,
			'P[fieldChangeFunc][focus]' => 'focus()',
		);
		$linkToScript = t3lib_div::linkThisUrl($browseLinksFile, $params);
		$link = '<a href="#" onclick="this.blur(); vHWin=window.open(\''.$linkToScript.'\',\'\',\'height=300,width=500,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;"><img src="'.$this->doc->backPath.'gfx/link_popup.gif" /></a>'."\n";

		return $link;
	}
		 
	function createFePics($mid, $aid) {
		$db =& $GLOBALS['TYPO3_DB'];
		$af1 = time().'.png';
		$af2 = str_replace('.png', '.gif', $af1);
		$img = PATH_site;
		$oldimg1 = '';
		$oldimg2 = '';
		$res = $db->exec_SELECTquery( 'folder,file,alt_file', 'tx_mwimagemap_map', 'id = '.$mid, '', '');
		while ( $row = $db->sql_fetch_row($res) ) {
			$img	 .= $row[0].$row[1];
			$oldimg1 = PATH_site.'uploads/tx_mwimagemap/'.$row[2];
			$oldimg2 = str_replace('.png', '.gif', $oldimg1);
		}
		$imgsize = getimagesize($img);
		$ext = ".".array_pop(explode(".", $img));
		$apic1 = PATH_site.'uploads/tx_mwimagemap/'.$af1;
		$apic2 = PATH_site.'uploads/tx_mwimagemap/'.$af2;
		
		if($this -> makeFePics($apic2, $af2, $mid, $img, $ext, $imgsize, $oldimg2)) {
			$this -> makeFePics($apic1, $af1, $mid, $img, $ext, $imgsize, $oldimg1);
		}
	}
	
	function makeFePics($pic, $af, $mid, $img, $ext, $imgsize, $oldimg) {
		$db =& $GLOBALS['TYPO3_DB'];
		$res = $db->exec_SELECTquery( 'id,fe_visible,fe_bordercolor,fe_borderthickness,type,fe_altfile', 'tx_mwimagemap_area', 'mid = '.$mid.' and (fe_visible=1)', '', '');
			
		if ( $db->sql_num_rows($res) != 0 ) {
			while ( $row = $db->sql_fetch_row($res) ) {
				$adata = $db->exec_SELECTquery('x, y', 'tx_mwimagemap_point', 'aid = '.$row[0], '', 'num');
					
				// Converting the hex value of a color to rgb;
				$bc = $this->convertToRGB($row[2]);
					
				switch( intval($row[1]) ) {
					case 1:
						$simg = $pic;
						if (!is_file($pic)) {
							$simg = PATH_site.'typo3conf/ext/mwimagemap/pi1/canvas.png -resize '.$imgsize[0].'!x'.$imgsize[1].'!';
							if(preg_match("/WIN/", PHP_OS)) { $simg = ' "'.PATH_site.'typo3conf/ext/mwimagemap/pi1/canvas.png" -resize '.$imgsize[0].'!x'.$imgsize[1].'!'; }
						}
						if(preg_match("/\.gif/i", $pic)) { $simg = str_replace('canvas.png', 'canvas.gif', $simg); }
						switch( intval($row[4]) ) {
							
							// Rectangle
							case 0:
								if(preg_match("/WIN/", PHP_OS) && !preg_match("/\"/", $simg)) { $simg = '"'.$simg.'"'; }
								$points = array();
								while ( $row2 = $db->sql_fetch_row($adata) ) {
									$points[] = $row2[0];
									$points[] = $row2[1];
								}
								
								if(preg_match("/WIN/", PHP_OS)) { exec('"'.rtrim($this->impath).'" convert -quality 100 '.$simg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " rectangle '.$points[2].','.$points[3].','.($points[2]+$points[0]).','.($points[3]+$points[1]).'" "'.$pic.'"'); }
								else { exec($this->impath.'convert -quality 100 '.$simg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " rectangle '.$points[2].','.$points[3].','.($points[2]+$points[0]).','.($points[3]+$points[1]).'" '.$pic); }
							break;
								
							// Circle
							case 1:
								if(preg_match("/WIN/", PHP_OS) && !preg_match("/\"/", $simg)) { $simg = '"'.$simg.'"'; }
								$points = array();
								while ( $row2 = $db->sql_fetch_row($adata) ) {
									$points[] = $row2[0];
									$points[] = $row2[1];
								}
								
								if(preg_match("/WIN/", PHP_OS)) { exec('"'.rtrim($this->impath).'" convert -quality 100 '.$simg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " circle '.$points[2].','.$points[3].','.($points[2]+$points[0]).','.($points[3]+$points[1]).'" "'.$pic.'"'); }
								else { exec($this->impath.'convert -quality 100 '.$simg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " circle '.$points[2].','.$points[3].','.($points[2]+$points[0]).','.($points[3]+$points[1]).'" '.$pic); }
							break;
								
							// Polygon
							case 2:
								if(preg_match("/WIN/", PHP_OS) && !preg_match("/\"/", $simg)) { $simg = '"'.$simg.'"'; }
								$points = '';
								while ( $row2 = $db->sql_fetch_row($adata) ) {
									$points .= (strlen($points) == 0) ? $row2[0].','.$row2[1] : ','.$row2[0].','.$row2[1];
								}
								
								if(preg_match("/WIN/", PHP_OS)) { exec('"'.rtrim($this->impath).'" convert -quality 100 '.$simg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " polygon '.$points.'" "'.$pic.'"'); }
								else { exec($this->impath.'convert -quality 100 '.$simg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " polygon '.$points.'" '.$pic); }
							break;
						}
						
						if(is_file($pic)) {
							if(is_file($oldimg)) { unlink($oldimg); }
							if(preg_match("/\.png/i", $simg) ) { $db->exec_UPDATEquery( 'tx_mwimagemap_map', 'id = '.$mid, array( 'alt_file' => $af ) ); }
						}
						else {
							if(is_file($oldimg)) { unlink($oldimg); }
							$db->exec_UPDATEquery( 'tx_mwimagemap_map', 'id = '.$mid, array( 'alt_file' => '' ) );
						}
					break;
				}
			}
		}
		else {
			if(is_file($oldimg)) { unlink($oldimg); }
			$db->exec_UPDATEquery( 'tx_mwimagemap_map', 'id = '.$mid, array( 'alt_file' => '' ) );
		}
		
		$res	= $db->exec_SELECTquery( 'id,fe_visible,fe_bordercolor,fe_borderthickness,type,fe_altfile', 'tx_mwimagemap_area', ' fe_visible=2 and mid=\''.$mid.'\'', '', '');
		if ( $db->sql_num_rows($res) != 0 ) {
			while ( $row = $db->sql_fetch_row($res) ) {
				$xpic = $pic;
				$ypic = PATH_site.'uploads/tx_mwimagemap/'.$row[0].'_'.$af;
				$timg = $xpic;
				if (!is_file($xpic)) {
					$timg = PATH_site.'typo3conf/ext/mwimagemap/pi1/canvas.png -resize '.$imgsize[0].'!x'.$imgsize[1].'!';
					if(preg_match("/WIN/", PHP_OS)) { $timg = ' "'.PATH_site.'typo3conf/ext/mwimagemap/pi1/canvas.png" -resize '.$imgsize[0].'!x'.$imgsize[1].'!'; }
				}
				if(preg_match("/\.gif/i", $ypic)) { $timg = str_replace('canvas.png', 'canvas.gif', $timg); }
				$oldimg = PATH_site.'uploads/tx_mwimagemap/'.$row[5];
				if(preg_match("/\.gif/i", $af)) { $oldimg = str_replace('.png', '.gif', $oldimg); }
				$adata = $db->exec_SELECTquery('x, y', 'tx_mwimagemap_point', 'aid = '.$row[0], '', 'num');
				
				// Converting the hex value of a color to rgb;
				$bc = $this->convertToRGB($row[2]);
				switch( intval($row[4]) ) {

					// Rectangle
					case 0:
						if(preg_match("/WIN/", PHP_OS) && !preg_match("/\"/", $timg)) { $timg = '"'.$timg.'"'; }
						$points = array();
						while ( $row2 = $db->sql_fetch_row($adata) ) {
							$points[] = $row2[0];
							$points[] = $row2[1];
						}
						
						if(preg_match("/WIN/", PHP_OS)) { exec('"'.rtrim($this->impath).'" convert -quality 100 '.$timg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " rectangle '.$points[2].','.$points[3].','.($points[2]+$points[0]).','.($points[3]+$points[1]).'" "'.$ypic.'"'); }
						else { exec($this->impath.'convert -quality 100 '.$timg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " rectangle '.$points[2].','.$points[3].','.($points[2]+$points[0]).','.($points[3]+$points[1]).'" '.$ypic); }
						
	 					if(is_file($ypic)) {
							if(is_file($oldimg)) { unlink($oldimg); }
							if(preg_match("/\.png/i", $ypic)) {
								$db->exec_UPDATEquery( 'tx_mwimagemap_area', 'id = '.$row[0], array( 'fe_altfile' => $row[0].'_'.$af ) );
							}
						}
						else { $db->exec_UPDATEquery( 'tx_mwimagemap_area', 'id = '.$row[0], array( 'fe_altfile' => '' ) ); }
					break;
								
					// Circle
					case 1:
						if(preg_match("/WIN/", PHP_OS) && !preg_match("/\"/", $timg)) { $timg = '"'.$timg.'"'; }
						$points = array();
						while ( $row2 = $db->sql_fetch_row($adata) ) {
							$points[] = $row2[0];
							$points[] = $row2[1];
						}
						
						if(preg_match("/WIN/", PHP_OS)) { exec('"'.rtrim($this->impath).'" convert -quality 100 '.$timg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " circle '.$points[2].','.$points[3].','.($points[2]+$points[0]).','.($points[3]+$points[1]).'" "'.$ypic.'"'); }
						else { exec($this->impath.'convert -quality 100 '.$timg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " circle '.$points[2].','.$points[3].','.($points[2]+$points[0]).','.($points[3]+$points[1]).'" '.$ypic); }
								
	 					if(is_file($ypic)) {
							if(is_file($oldimg)) { unlink($oldimg); }
							if(preg_match("/\.png/i", $ypic)) {
								$db->exec_UPDATEquery( 'tx_mwimagemap_area', 'id = '.$row[0], array( 'fe_altfile' => $row[0].'_'.$af ) );
							}
						}
						else { $db->exec_UPDATEquery( 'tx_mwimagemap_area', 'id = '.$row[0], array( 'fe_altfile' => '' ) ); }
					break;
								
					// Polygon
					case 2:
						if(preg_match("/WIN/", PHP_OS) && !preg_match("/\"/", $timg)) { $timg = '"'.$timg.'"'; }
						$points = '';
						while ( $row2 = $db->sql_fetch_row($adata) ) {
							$points .= (strlen($points) == 0) ? $row2[0].','.$row2[1] : ','.$row2[0].','.$row2[1];
						}
							
						if(preg_match("/WIN/", PHP_OS)) { exec('"'.rtrim($this->impath).'" convert -quality 100 '.$timg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " polygon '.$points.'" "'.$ypic.'"'); }
						else { exec($this->impath.'convert -quality 100 '.$timg.' -stroke "rgb('.$bc.')" -strokewidth '.$row[3].' -fill none -draw " polygon '.$points.'" '.$ypic); }
								
	 					if(is_file($ypic)) {
							if(is_file($oldimg)) { unlink($oldimg); }
							if(preg_match("/\.png/i", $ypic)) {
								$db->exec_UPDATEquery( 'tx_mwimagemap_area', 'id = '.$row[0], array( 'fe_altfile' => $row[0].'_'.$af ) );
							}
						}
						else { $db->exec_UPDATEquery( 'tx_mwimagemap_area', 'id = '.$row[0], array( 'fe_altfile' => '' ) ); }
					break;
				}
			}
		}
		return TRUE;
	}
	
	function convertToRGB($col) {
		$bc = str_replace('#', '', $col);
		$r = hexdec(substr($bc, 0, 2));
		$g = hexdec(substr($bc, 2, 2));
		$b = hexdec(substr($bc, 4, 2));
		return $r.','.$g.','.$b;
	}
		
	function deleteFePics($mid) {
		$db =& $GLOBALS['TYPO3_DB'];
			
		// Delete the main overlay
		if ( ! ( $res = $db->exec_SELECTquery( 'alt_file', 'tx_mwimagemap_map', 'id = \''.$mid.'\'') ) ) {
			$content .= 'Fatal db error:<br />sql_error:'.$db->sql_error().'<br />';
			break;
		}
		if ( $db->sql_num_rows($res) == 1	) { 
			while ( $row = $db->sql_fetch_row($res) ) {
				if(is_file(PATH_site.'uploads/tx_mwimagemap/'.$row[0])) {
					unlink(PATH_site.'uploads/tx_mwimagemap/'.$row[0]);
				}
			}
		}
			
		// Delete the area overlays
		if ( ! ( $res = $db->exec_SELECTquery( 'fe_altfile', 'tx_mwimagemap_area', 'mid = \''.$mid.'\'') ) ) {
			$content .= 'Fatal db error:<br />sql_error:'.$db->sql_error().'<br />';
			break;
		}
		while ( $row = $db->sql_fetch_row($res) ) {
			if(is_file(PATH_site.'uploads/tx_mwimagemap/'.$row[0])) {
				unlink(PATH_site.'uploads/tx_mwimagemap/'.$row[0]);
			}
		}
	}
	
	function checkFecache() {
		if($this->extConf['fe_clearpagecache'] == '1') {
			$tce = t3lib_div::makeInstance('t3lib_TCEmain');
			$tce->start(Array(), Array());
			$tce->clear_cacheCmd('all');
		}
	}
	
	function leading_zero( $aNumber, $intPart, $floatPart=NULL, $dec_point=NULL, $thousands_sep=NULL) { //Note: The $thousands_sep has no real function because it will be "disturbed" by plain leading zeros -> the main goal of the function
		$formattedNumber = $aNumber;
		if (!is_null($floatPart)) {		//without 3rd parameters the "float part" of the float shouldn't be touched
			$formattedNumber = number_format($formattedNumber, $floatPart, $dec_point, $thousands_sep);
		}
		$formattedNumber = str_repeat('0', ($intPart + -1 - floor(log10($formattedNumber)))).$formattedNumber;
		return $formattedNumber;
	}
}
	
// Make instance:
$SOBE = t3lib_div::makeInstance('tx_mwimagemap_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();
/*
if (defined('TYPO3_MODE') && $GLOBALS['$TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mwimagemap/mod1/index.php'])	{
	include_once($GLOBALS['$TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/mwimagemap/mod1/index.php']);
}
*/
?>
