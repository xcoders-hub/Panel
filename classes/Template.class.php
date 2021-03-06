<?php
	namespace fruithost;
	use \fruithost\Database;
	
	class Template extends TemplateDefaults {
		private $core;
		private $theme		= null;
		private $assigns	= [];
		private $files		= null;
		private $navigation	= null;
		
		public function __construct($core) {
			$this->core			= $core;
			$this->files		= new TemplateFiles();
			$this->navigation	= new TemplateNavigation($this->core);
			$this->theme		= $this->core->getHooks()->applyFilter('theme_name', 'default');
			
			ob_start('ob_gzhandler');
			
			$this->core->getHooks()->addAction('html_head', [ $this, 'head_robots' ]);
			$this->core->getHooks()->addAction('html_head', [ $this, 'head_scripts' ]);
			$this->core->getHooks()->addAction('html_head', [ $this, 'theme_color' ]);
			$this->core->getHooks()->addAction('html_head', [ $this, 'favicon' ]);
			$this->core->getHooks()->addAction('html_foot', [ $this, 'foot_modals' ]);
			$this->core->getHooks()->addAction('html_foot', [ $this, 'foot_scripts' ]);
			
			$this->files->addStylesheet('bootstrap', $this->url('css/bootstrap/bootstrap.min.css'), '4.3.1');
			$this->files->addStylesheet('jquery-ui', $this->url('css/jquery-ui/jquery-ui.css'), '1.12.1');
			$this->files->addStylesheet('material-icons', $this->url('fonts/material-icons/material-icons.css'), '3.0.1', [ 'bootstrap' ]);
			$this->files->addJavascript('popper', $this->url('js/popper/popper.min.js'), '3.3.1', [ 'jquery' ], TemplateFiles::FOOTER);
			$this->files->addJavascript('jquery', $this->url('js/jquery/jquery-3.3.1.min.js'), '3.3.1', [], TemplateFiles::FOOTER);
			$this->files->addJavascript('bootstrap', $this->url('js/bootstrap/bootstrap.bundle.min.js'), '4.3.1', [ 'jquery', 'popper' ], TemplateFiles::FOOTER);
			
			$this->navigation->addCategory('account', 'Account');
			$this->navigation->addCategory('database', 'Databases');
			$this->navigation->addCategory('domain', 'Domains');
			$this->navigation->addCategory('mail', 'E-Mails');
			$this->navigation->addCategory('ftp', 'FTP');
			$this->navigation->addCategory('hosting', 'Hosting');
			$this->navigation->addCategory('extended', 'Extended');
			$this->navigation->addCategory('support', 'Support');
			$this->navigation->addCategory('admin', 'Administration');
			
			$this->assign('topbar',		$this->navigation->getCategory('account'));
			$this->assign('navigation', $this->navigation);
		}
		
		public function getCore() {
			return $this->core;
		}
		
		public function getAssigns() {
			return $this->assigns;
		}
		
		public function getFiles() {
			return $this->files;
		}
		
		public function assign($name, $value) {
			$this->assigns[$name] = $value;
		}
		
		public function display($file, $arguments = [], $basedir = true) {
			$template	= $this;
			
			foreach($arguments AS $name => $value) {
				$this->assigns[$name] = $value;
			}
			
			if($basedir) {
				$path		= sprintf('%1$s%2$sthemes%2$s%4$s%2$s%3$s.php', dirname(PATH), DS, $file, $this->theme);
			} else {
				$path		= $file;
			}
			
			$handler	= sprintf('%1$s%2$shandler%2$s%3$s.php', PATH, DS, $file);
			
			foreach($this->assigns AS $name => $value) {
				${$name} = $value;
			}
			
			if(file_exists($handler)) {
				require_once($handler);
				
				foreach($this->assigns AS $name => $value) {
					${$name} = $value;
				}
			}
			
			if(file_exists($path)) {
				require_once($path);
			} else {
				if(!Auth::isLoggedIn()) {
					$this->getFiles()->addStylesheet('login', $this->url('css/login.css'), '1.0.0', [ 'bootstrap', 'material-icons' ]);
				} else {
					$this->getFiles()->addStylesheet('style', $this->url('css/style.css'), '1.0.0', [ 'bootstrap', 'material-icons' ]);
					$this->getFiles()->addJavascript('jquery-ui', $this->url('js/jquery-ui/jquery-ui.min.js'), '1.12.1', [ 'jquery' ], TemplateFiles::FOOTER);
					$this->getFiles()->addJavascript('ui', $template->url('js/ui.js'), '1.0.0', [ 'jquery', 'jquery-ui', 'popper', 'bootstrap' ], TemplateFiles::FOOTER);
				}
				
				$path = sprintf('%1$s%2$sdefault%2$s%3$s.php', PATH, DS, $file);
				
				if(file_exists($path)) {
					require_once($path);
				}
			}
		}
		
		public function header() {
			$template	= $this;
			$path		= sprintf('%1$s%2$sthemes%2$s%4$s%2$s%3$s.php', dirname(PATH), DS, 'header', $this->theme);
			
			foreach($this->assigns AS $name => $value) {
				${$name} = $value;
			}
			
			if(file_exists($path)) {
				require_once($path);
			} else {
				$path = sprintf('%1$s%2$sdefault%2$s%3$s.php', PATH, DS, 'header');
				
				if(file_exists($path)) {
					require_once($path);
				}
			}
		}
		
		public function footer() {
			$template	= $this;
			$path		= sprintf('%1$s%2$sthemes%2$s%4$s%2$s%3$s.php', dirname(PATH), DS, 'footer', $this->theme);
			
			foreach($this->assigns AS $name => $value) {
				${$name} = $value;
			}
			
			if(file_exists($path)) {
				require_once($path);
			} else {
				$path = sprintf('%1$s%2$sdefault%2$s%3$s.php', PATH, DS, 'footer');
				
				if(file_exists($path)) {
					require_once($path);
				}
			}
		}
		
		public function getLanguage($short = false) {
			// @ToDo
			if($short) {
				return 'en';
			}
			
			return 'en_US';
		}
		
		public function head() {
			$this->core->getHooks()->runAction('html_head');
		}
		
		public function foot() {
			$this->core->getHooks()->runAction('html_foot');
		}
		
		public function url($path) {
			$scheme = 'http';
			
			if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
				$scheme = 'https';
			}
			
			if($path === true) {
				$path = $_SERVER['REQUEST_URI'];
			}
			
			if(substr($path, 0, 1) === '/') {
				$path = substr($path, 1);
			}
			
			if(!empty($parameters)) {
				if(strpos($path, '?')) {
					$path	= explode('?', $path);
					$path	= $path[0];
				}
				
				$path .= '?' . http_build_query(array_merge($_GET, $parameters));
			}
			
			return $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . $path;
		}
	}
?>