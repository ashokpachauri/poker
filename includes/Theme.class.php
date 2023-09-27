<?php
class OPSTheme
{
	public $theme;
	public $defaultTheme;

	public $parentDir;
	public $themeDir;
	public $defaultThemeDir;

	public $pagename;
	public $pagepath;

	public $cssFiles = array();
	public $jsFiles  = array('header' => array(), 'footer' => array());

	public $content;

	public function __construct()
	{
		$this->theme           = THEME;
		$this->defaultTheme    = DEFTHEME;
		$this->parentDir       = __DIR__ . '/..';
		$this->themeDir        = $this->parentDir . "/themes/{$this->theme}";
		$this->defaultThemeDir = $this->parentDir . "/themes/{$this->defaultTheme}";
	}

	public function addVariable($name, $value)
	{
		if (is_object($value))
			$value = (array) $value;

		if (! isset($GLOBALS['themeVars']))
			$GLOBALS['themeVars'] = array();

		global $themeVars;
		$themeVars[ $name ] = $value;
	}

	public function addCss($name, $d = false)
	{
		if (preg_match('/^(https\:\/\/|http\:\/\/|\/\/)/i', $name))
		{
			$this->cssFiles[] = $name;
			return true;
		}

		$name     = str_replace('.css', '', strtolower($name));
		$filepath = "{$this->themeDir}/css/{$name}.css";

		if ($this->theme !== $this->defaultTheme && !file_exists($filepath))
			$filepath = "{$this->defaultThemeDir}/css/{$name}.css";

		if ( $d && !file_exists($filepath) ):
			$filepath = str_replace(
				[ $this->theme, $this->defaultTheme ],
				$d . '/theme',
				$filepath
			);
		endif;

		if ( !file_exists($filepath) )
			return false;

		$this->cssFiles[] = $filepath;
		return true;
	}

	public function getCss()
	{
		$css = '';
		foreach ($this->cssFiles as $cssFile)
		{
			$eTime   = (preg_match('/^(https\:\/\/|http\:\/\/|\/\/)/i', $cssFile)) ? '' : '?t=' . filemtime($cssFile);
			$cssLink = $cssFile . $eTime;
			$cssLink = preg_replace('/.*?\/(themes|includes\/addons)\/(.*?)/i', "$1/$2", $cssLink);
			$css    .= '<link rel="stylesheet" href="' . $cssLink . '">';
		}
		return $css;
	}

	public function addJs($name, $location = 'header', $d = false)
	{
		if (! isset($this->jsFiles[$location]))
			$this->jsFiles[$location] = array();

		if (preg_match('/^(https\:\/\/|http\:\/\/|\/\/)/i', $name))
		{
			$this->jsFiles[$location][] = $name;
			return true;
		}

		$name     = str_replace('.js', '', strtolower($name));
		$filepath = "{$this->themeDir}/js/{$name}.js";

		if ($this->theme !== $this->defaultTheme && ! file_exists($filepath))
			$filepath = "{$this->defaultThemeDir}/js/{$name}.js";

		if ( $d && !file_exists($filepath))
		{
			$filepath = str_replace(
				[ $this->themeDir, $this->defaultThemeDir ],
				$d . '/theme',
				$filepath
			);
		}

		if ( !file_exists($filepath) )
			return false;

		$this->jsFiles[$location][] = $filepath;
		return true;
	}

	public function getJs($location = 'header')
	{
		if (! isset($this->jsFiles[$location]))
			return '';

		$js = '';
		foreach ($this->jsFiles[$location] as $jsFile)
		{
			$eTime  = (preg_match('/^(https\:\/\/|http\:\/\/|\/\/)/i', $jsFile)) ? '' : '?t=' . filemtime($jsFile);
			$jsLink = $jsFile . $eTime;
			$jsLink = preg_replace('/.*?\/(themes|includes\/addons)\/(.*?)/i', "$1/$2", $jsLink);
			$js    .= '<script type="text/javascript" src="' . $jsLink . '"></script>';
		}
		return $js;
	}

	public function viewPage($pagename, $d = false, $default = false)
	{
		$this->pagename = $pagename;
		$this->pagepath = "{$this->themeDir}/html/pages/{$this->pagename}.html";

		if ($this->theme !== $this->defaultTheme && !file_exists($this->pagepath))
			$this->pagepath = "{$this->defaultThemeDir}/html/pages/{$this->pagename}.html";

		if ( $d && !file_exists($this->pagepath) ) {
			$this->pagepath = str_replace(
				[ $this->themeDir, $this->defaultThemeDir ],
				$d . '/theme',
				$this->pagepath
			);
		}
		elseif ( $default && !file_exists($this->pagepath) )
			$this->pagepath = "{$this->defaultThemeDir}/html/parts/{$default}.html";

		if ( !file_exists($this->pagepath) )
			return '';

		$open = fopen($this->pagepath, 'r');
		$this->content = @fread($open, filesize($this->pagepath));
		fclose($open);

		$this->processVariables( $d );
		return \ops_minify_html($this->content);
	}

	public function viewPart($partname, $d = false, $default = false)
	{
		$this->pagename = $partname;
		$this->pagepath = "{$this->themeDir}/html/parts/{$this->pagename}.html";

		if ( $this->theme !== $this->defaultTheme && !file_exists($this->pagepath))
			$this->pagepath = "{$this->defaultThemeDir}/html/parts/{$this->pagename}.html";

		if ( $d && !file_exists($this->pagepath) ) {
			$this->pagepath = str_replace(
				[ $this->themeDir, $this->defaultThemeDir ],
				$d . '/theme',
				$this->pagepath
			);
		}
		elseif ( $default && !file_exists($this->pagepath) )
			$this->pagepath = "{$this->defaultThemeDir}/html/parts/{$default}.html";

		if ( !file_exists($this->pagepath) )
			return '';

		$open  = fopen($this->pagepath, 'r');
		$fsize = filesize($this->pagepath);

		$this->content = $fsize > 0 ? @fread($open, filesize($this->pagepath)) : '';
		fclose($open);

		$this->processVariables( $d );
		return \ops_minify_html($this->content);
	}
	
	public function getVariable($var)
	{
		$name = trim($var);

		if (preg_match('/[^a-zA-Z0-9._]/', $name))
			return '';

		if (preg_match('/^[A-Z_]+$/', $name) && defined($name))
			return constant($name);

		$arrayKeys = explode('.', $name);

		if (! isset($GLOBALS['themeVars']))
			$GLOBALS['themeVars'] = array();

		global $themeVars;

		if (count($arrayKeys) === 1)
		{
			$themeVar = $themeVars[ $name ];

			if (is_array($themeVar))
				$themeVar = json_encode($themeVar);
			
			return $themeVar;
		}

		$arrayVars = $themeVars;

		foreach ($arrayKeys as $key)
		{
			if (!isset($arrayVars[$key]))
			{
				$arrayVars = '';
				break;
			}

			$arrayVars = $arrayVars[$key];
		}

		if (is_array($arrayVars))
			$arrayVars = json_encode($arrayVars);

		return $arrayVars;
	}

	public function processVariables( $d = false )
	{
		$this->content = preg_replace_callback(
			'/\{\s*\$(.*?)\s*\}/',

			function ($matches)
			{
				$var = explode('<', $matches[1]);
				$name = trim($var[0]);

				if (preg_match('/[^a-zA-Z0-9._]/', $name))
					return $matches[0];

				if (preg_match('/^[A-Z_]+$/', $name) && defined($name))
					return constant($name);

				$arrayKeys = explode('.', $name);

				if (! isset($GLOBALS['themeVars']))
					$GLOBALS['themeVars'] = array();

				global $themeVars;

				if (count($arrayKeys) === 1)
				{
					if (! isset($themeVars[ $name ]))
						return '';
					
					$themeVar = $themeVars[ $name ];

					if (is_array($themeVar))
						$themeVar = json_encode($themeVar);
					
					return $themeVar;
				}

				$arrayVars = $themeVars;

				foreach ($arrayKeys as $key)
				{
					if (!isset($arrayVars[$key]))
					{
						$arrayVars = '';
						break;
					}

					$arrayVars = $arrayVars[$key];
				}

				if (is_array($arrayVars))
					$arrayVars = json_encode($arrayVars);

				return $arrayVars;
			},

			$this->content
		);

		if ( $d )
		{
			if ( strpos( str_replace('\\', '/', $d), 'includes/addons/' ) !== false )
				$c = 'addon-' . basename($d);
		}

		if (! isset($c))
			$c = 'theme-' . $this->theme;

		$this->content = preg_replace_callback(
			'/\{\_\_\((.*?)\)\}/s',

			function ($matches) use ($c)
			{
				$str = $matches[1];
				return __( $str, $c );
			},

			$this->content
		);

		$this->content = preg_replace_callback(
			'/<\s*ops-func\s*(.*?)\s*>\s*(.*?)\s*<\s*\/\s*ops-func\s*>/i',

			function ($matches) use ($c)
			{
				extract( $GLOBALS );
				
				$xml = $matches[0];
				$obj = simplexml_load_string($xml);

				$attributes = array();
				$data 		= array();
				foreach ($obj->attributes() as $key => $value)
				{
					$attributes[ $key ] = (string) $value;

					if (stripos( $key, 'data-' ) !== false)
						$data[ str_ireplace('data-', '', $key) ] = (string) $value;
				}

				if ( !isset($attributes['name']) )
					return;

				$func = $attributes['name'];
				$type = isset($attributes['type']) ? $attributes['type'] : 'array';

				if ( empty($data) )
					return call_user_func( $func );

				if ( $type == 'array' )
					return call_user_func_array( $func, array($data) );
				else
					return call_user_func_array( $func, $data );
			},

			$this->content
		);
	}
}

$opsTheme = new OPSTheme();
$opsTheme->addVariable('get',   $_GET);
$opsTheme->addVariable('post',  $_POST);
$opsTheme->addVariable('config', $confs);
$opsTheme->addVariable('theme', array(
	'id' => THEME,
	'url' => 'themes/' . THEME
));
