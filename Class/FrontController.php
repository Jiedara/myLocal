<?php
/**
 * FrontController
 *
 * @package GuildPanel
 * @subpackage class
 */
class FrontController extends Controller
{
	private $templateList;
	private $userConfig;
	private $configID;
	private $cacheID;
	
	/**
	 * __construct moduleController
	 * @author Golga
	 * @since 0.2
	 * @return boolean
	 */
	public function __construct( $smarty, $yourSettingsTxt )
	{
		$this->configID = sha1( $yourSettingsTxt );
		$this->cacheID = $this->configID;
		parent::__construct( $smarty );
		return true;
	}

	/**
	 * __destruct
	 *
	 * @author Golga
	 * @since 0.2
	 * @param none
	 * @return boolean
	*/
	public function __destruct()
	{
		parent::__destruct();
		unset( $this->templateList );
		return true; 
	}

	/**
	 * catchGlobData
	 * @author Golga
	 * @since 0.2
	 * @param array     $templateList
	 * @param array     $hookData
	 * @return boolean
	 */
	public function catchGlobData( $debug = false )
	{
		$glob = array( 
			'get'       => $_GET,
			'post'      => $_POST,
			'selfUrl'   => "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]",
			'prevUrl'   => ( isset($_SERVER["HTTP_REFERER"]) ) ? $_SERVER["HTTP_REFERER"] : null ,
			'host'      => ( isset($_SERVER["HTTP_HOST"]) ) ? $_SERVER["HTTP_HOST"] : null ,
			'request'   => ( isset($_SERVER["REQUEST_URI"]) ) ? $_SERVER["REQUEST_URI"] : null ,
			'templateList'  => $this->templateList
		);

		if ( $debug )
		{
			throw new Exception( "Display debuging informations: \n<br />" . print_r( $this->hookData ,1 ) . "\n<hr />" . print_r( $glob, 1 ) );
		}
		else
		{
			$this->smartyAssign( $glob );

			if (empty( $this->userConfig ))
			{
				$this->catchUserConfig();
			}
			$this->smartyAssign( array('userConfig' => $this->userConfig ) );
		}
	}

	public function catchUserConfig( )
	{
		$customData = array();
		if ( file_exists( CUSTOM_FOLD . "/custom-user.css" ) )
		{
			$customData["css"] = file_get_contents(CUSTOM_FOLD . "/custom-user.css");
		}
		if ( file_exists( CUSTOM_FOLD . "/custom-user.js" ) )
		{
			$customData["js"] = file_get_contents(CUSTOM_FOLD . "/custom-user.js");
		}
		if ( file_exists( CUSTOM_FOLD . "/config-user.json" ) )
		{
			$customData["json"] = file_get_contents(CUSTOM_FOLD . "/config-user.json");
		}
		
		$this->userConfig = $customData;
		return $this->userConfig;
	}

	/**
	 * displayTpl
	 * @author Golga
	 * @since 0.2
	 * @param array
	 * @return boolean
	 */
	public function displayTpl( $templateList, $repoScan = true, $allowParentLink = false )
	{
		$this->templateList = $templateList;
		if (empty($_POST["method"]))
		{
			$this->getList( $repoScan, $allowParentLink );
			$this->smartyDisplay( $templateList, sha1( $this->cacheID ) );
		}
		else
		{
			switch ($_POST["method"])
			{
				case 'getConfForm':
					$this->displayConfForm();
					break;
				case 'submitConfig':
					$this->submitConfig();
					break;
				default:
					throw new Exception("Error: undefined method: " . $_POST["method"] . " in FrontController!", 1);
					break;
			}
		}
	}

	/**
	 * getList
	 * @author Golga
	 * @since 0.2
	 * @return void
	 */
	public function getList( $repoScan = true, $allowParentLink = false )
	{
		$files = scandir(SCAN_DIR);
		$siteList = array();

		foreach ($files as $key => $value)
		{
			$crawled = $this->folderCrawler($value);
			if($crawled){
				$siteList[] = $crawled;
			}
		}
		$this->smartyAssign( array('datas' => $siteList ) );
	}

	/**
	 *[folderCrawler description]
	 *@author Jiedara <mvillanove@adneom.com>
	 *@param  [type] $file     [description]
	 *@return [type] [description]
	 */
	private function folderCrawler($file, $repoScan = true, $allowParentLink = false )
	{
		$site = array();
		$childFolders = glob($file . '/*', GLOB_ONLYDIR); 
		$childFiles = array_filter(glob($file . '/*'), 'is_file'); 
		$realpath = str_replace(array('/', '\\'),'',explode( ":",realpath('.') )[0]);
		
		//The directory don't have any file in it (only folder)
		//It's a package of other app
		//we include them inside the $folders
		if(!count($childFiles)){
			foreach ($childFolders as $key => $childFile) {
				$crawled = $this->folderCrawler($childFile);
				if($crawled){
					$site['folder'][$key] = $crawled;
				}
			}
		}

		$this->cacheID .= $file;
		if (
			isset( $file )
			&&  $file != "."
			&&  ( $file != ".." || $allowParentLink )
			&&  !is_file( $file )
			&&  $file != MAIN_FOLDER_NAME
			&&  $file != "xampp"
			&&  $file != "mampp"
			&&  $file != "lampp"
			&&  $file != "wampp"
			&&  $file != "dashboard"
			&&  $file != "lampp"
			&&  $file != "mampp"
			&&  $file != "webalizer"
			&&  $file != "img"
			&&  $file != "js"
			&&  $file != "image"
			&&  $file != "cache"
			&&  ( $realpath != "xampphtdocs" || $file != "dashboard" )
			&&  ( $realpath != "xampphtdocs" || $file != "forbidden" )
			&&  ( $realpath != "xampphtdocs" || $file != "restricted" )
			&&  ( $realpath != "xampphtdocs" || $file != "xampp" )
			)
		{
			$site["name"] = $file;
			if ( SCAN_DIR != "./" )
			{
				$site["local_link"] = ROOT_LOCAL . SCAN_DIR . "/" . $file;
				$xmlfile = SCAN_DIR . "/" . $file . "/.git/sourcetreeconfig";
				$confFile = SCAN_DIR . "/" . $file . "/.git/config";
				$confSvn = SCAN_DIR . "/" . $file . "/.svn/";
			}
			else
			{
				$site["local_link"] = ROOT_LOCAL . $file;
				$xmlfile = $file . "/.git/sourcetreeconfig";
				$confFile = $file . "/.git/config";
				$confSvn = $file . "/.svn/";
			}
			// reset var
			$site["img"] = "";
			$site["baseUrl"] = "";
			$site["identifier"] = "";
			$site["link"] = "";
			$site["linkType"] = "";
			$site["commitKey"] = "";
			
			$site["img"] = $this->getBck( $site["name"] );
			$site["commitKey"] = $this->getComitKey( $site["name"] );
			if ( file_exists($xmlfile) )
			{
				$xmlparser = xml_parser_create();
				$fp = fopen($xmlfile, 'r');
				$xmldata = fread($fp, 4096);
				xml_parse_into_struct($xmlparser,$xmldata,$values);
				xml_parser_free($xmlparser);
				$conffBitBucket = $values;
				foreach ($conffBitBucket as $k => $conff)
				{
					if ($conff["tag"] == "BASEURL")
					{
						$baseUrl = $conff["value"];
					}
					elseif($conff["tag"] == "IDENTIFIER")
					{
						$identifier = $conff["value"];//empty identifier if clone by url
					}
				}
			}
		
			// could cause error login if clone by url
			if( file_exists($confFile) && empty($identifier) && $repoScan )
			{
				$fp = fopen($confFile, 'r');
				$data = fread($fp, 4096);
				$conffGitSplit = array();
				$data = str_replace(array(' ','&lt;br/&gt;','&quot;', ' ', '\nl', '\r', '\rn', '\r\n', '\n\r','"', ']','['), '', $data);
				$data = nl2br($data);
				$data = explode('\nl', $data);
				$conff = "";
				foreach ( $data as $k => $val )
				{
					$conff .= $val;
				}
				$conffGit = explode('<br />', $conff);
				foreach ( $conffGit as $k => $v )
				{
					$v = explode('=',$v);
				
					if ( !empty($v[0]) && !empty($v[1]) )
					{
						$v[0] = preg_replace("/[^A-Za-z0-9 ]/", '',$v[0]);
						$conffGitSplit[$v[0]] = $v[1];
					}
				}
				if ( !empty($conffGitSplit["url"]) )
				{
					$site["link"] = $conffGitSplit["url"];
					$isSSH = explode( "@", $site["link"] );
					
					if ( isset( explode( "github", $site["link"] )[1] ) )
					{
						$site["linkType"] = "github";
						if ( isset( $isSSH[1] ) )
						{
							$site["link"] = $isSSH[1];
							$site["link"] = str_replace( ':', '/', $site["link"] );
							$site["link"] = "https://" . str_replace( '.git', '', $site["link"] );
						}
					}
					elseif ( isset( explode( "bitbucket", $site["link"] )[1] ) )
					{
						$site["linkType"] = "bitbucket";
					}
					else
					{
						$site["linkType"] = "git";
					}
				}   
			}
			elseif ( file_exists($confSvn) )
			{
				$site["linkType"] = "svn";
				$site["link"] = "#svn";
			}

			return $site;

		}else {
			return false;
		}
	}

	public function displayConfForm()
	{
		$this->smartyDisplay( "config", $this->configID );
	}

	public function getBck( $siteName )
	{
		$folder = array(SHOOT_DIR, $siteName."/img", $siteName);
		$name = array($siteName, "screen", "screenshot", "logo", "maquette");
		$ext = array("png", "jpg", "svg", "gif");

		foreach ($folder as $k1 => $fileFold) 
		{
			foreach ($name as $k2 => $fileName)
			{
				foreach ($ext as $k3 => $fileExt)
				{
					if ( file_exists($fileFold . '/' . $fileName . '.' . $fileExt) )
					{
						$img = ROOT_LOCAL . $fileFold . '/' . $fileName . '.' . $fileExt;
						return $img;
						break;
					}
				}
			}
		}
		return false;
	}

	public function getComitKey($checkFolder)
	{
		// TODO FETCH HEAD PARSING
		$confFile = $checkFolder."/.git/FETCH_HEAD";
		if (file_exists($confFile))
		{
			$fp = fopen($confFile, 'r');
			$data = fread($fp, 4096);
			$key = explode("    ",$data)[0];
			return $key;
		}
		else
		{
			return false;
		}
	}

	public function submitConfig()
	{
		$myfile = fopen( CUSTOM_FOLD . "/config-user.json", "w" ) or die("Unable to open config json file! <br/> please check that you have granted your PHP file access ( " . ROOT_DIR . "/config-user/custom-user.css )");
		
		$txt = $_POST['customJSON'];
		fwrite($myfile, $txt);
		fclose($myfile);

		$myfile = fopen( CUSTOM_FOLD . "/custom-user.css", "w" ) or die("Unable to open custom css file! <br/> please check that you have granted your PHP file access ( " . ROOT_DIR . "/config-user/custom-user.css )");
		
		$txt = $_POST['customCSS'];
		fwrite($myfile, $txt);
		fclose($myfile);

		$myfile = fopen( CUSTOM_FOLD . "/custom-user.js", "w" ) or die("Unable to open custom js file! <br/> please check that you have granted your PHP file access ( " . ROOT_DIR . "/config-user/custom-user.css )");
		$txt = $_POST['customJS'];
		fwrite($myfile, $txt);
		fclose($myfile);
		header("Refresh:0");
	}
}
