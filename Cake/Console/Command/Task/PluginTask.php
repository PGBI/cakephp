<?php
/**
 * The Plugin Task handles creating an empty plugin, ready to be used
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Command\Task;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Utility\File;
use Cake\Utility\Folder;
use Cake\Utility\Inflector;

/**
 * The Plugin Task handles creating an empty plugin, ready to be used
 *
 */
class PluginTask extends Shell {

/**
 * path to plugins directory
 *
 * @var array
 */
	public $path = null;

/**
 * Path to the bootstrap file. Changed in tests.
 *
 * @var string
 */
	public $bootstrap = null;

/**
 * initialize
 *
 * @return void
 */
	public function initialize() {
		$this->path = current(App::path('Plugin'));
		$this->bootstrap = APP . 'Config/bootstrap.php';
	}

/**
 * Execution method always used for tasks
 *
 * @return void
 */
	public function execute() {
		if (isset($this->args[0])) {
			$plugin = Inflector::camelize($this->args[0]);
			$pluginPath = $this->_pluginPath($plugin);
			if (is_dir($pluginPath)) {
				$this->out(__d('cake_console', 'Plugin: %s already exists, no action taken', $plugin));
				$this->out(__d('cake_console', 'Path: %s', $pluginPath));
				return false;
			}
			$this->_interactive($plugin);
		} else {
			return $this->_interactive();
		}
	}

/**
 * Interactive interface
 *
 * @param string $plugin
 * @return void
 */
	protected function _interactive($plugin = null) {
		while ($plugin === null) {
			$plugin = $this->in(__d('cake_console', 'Enter the name of the plugin in CamelCase format'));
		}

		if (!$this->bake($plugin)) {
			$this->error(__d('cake_console', "An error occurred trying to bake: %s in %s", $plugin, $this->path . $plugin));
		}
	}

/**
 * Bake the plugin, create directories and files
 *
 * @param string $plugin Name of the plugin in CamelCased format
 * @return boolean
 */
	public function bake($plugin) {
		$pathOptions = App::path('Plugin');
		if (count($pathOptions) > 1) {
			$this->findPath($pathOptions);
		}
		$this->hr();
		$this->out(__d('cake_console', "<info>Plugin Name:</info> %s", $plugin));
		$this->out(__d('cake_console', "<info>Plugin Directory:</info> %s", $this->path . $plugin));
		$this->hr();

		$looksGood = $this->in(__d('cake_console', 'Look okay?'), ['y', 'n', 'q'], 'y');

		if (strtolower($looksGood) === 'y') {
			$Folder = new Folder($this->path . $plugin);
			$directories = [
				'Config/Schema',
				'Model/Behavior',
				'Model/Datasource',
				'Console/Command/Task',
				'Controller/Component',
				'Lib',
				'View/Helper',
				'Test/Case/Controller/Component',
				'Test/Case/View/Helper',
				'Test/Case/Model/Behavior',
				'Test/Fixture',
				'vendor',
				'webroot'
			];

			foreach ($directories as $directory) {
				$dirPath = $this->path . $plugin . DS . $directory;
				$Folder->create($dirPath);
				new File($dirPath . DS . 'empty', true);
			}

			foreach ($Folder->messages() as $message) {
				$this->out($message, 1, Shell::VERBOSE);
			}

			$errors = $Folder->errors();
			if (!empty($errors)) {
				foreach ($errors as $message) {
					$this->error($message);
				}
				return false;
			}

			$controllerFileName = $plugin . 'AppController.php';

			$out = "<?php\n\n";
			$out .= "App::uses('AppController', 'Controller');\n\n";
			$out .= "class {$plugin}AppController extends AppController {\n\n";
			$out .= "}\n";
			$this->createFile($this->path . $plugin . DS . 'Controller/' . $controllerFileName, $out);

			$modelFileName = $plugin . 'AppModel.php';

			$out = "<?php\n\n";
			$out .= "App::uses('AppModel', 'Model');\n\n";
			$out .= "class {$plugin}AppModel extends AppModel {\n\n";
			$out .= "}\n";
			$this->createFile($this->path . $plugin . DS . 'Model/' . $modelFileName, $out);

			$this->_modifyBootstrap($plugin);

			$this->hr();
			$this->out(__d('cake_console', '<success>Created:</success> %s in %s', $plugin, $this->path . $plugin), 2);
		}

		return true;
	}

/**
 * Update the app's bootstrap.php file.
 *
 * @param string $plugin Name of plugin
 * @return void
 */
	protected function _modifyBootstrap($plugin) {
		$bootstrap = new File($this->bootstrap, false);
		$contents = $bootstrap->read();
		if (!preg_match("@\n\s*Plugin::loadAll@", $contents)) {
			$bootstrap->append("\nPlugin::load('$plugin', ['bootstrap' => false, 'routes' => false]);\n");
			$this->out('');
			$this->out(__d('cake_dev', '%s modified', $this->bootstrap));
		}
	}

/**
 * find and change $this->path to the user selection
 *
 * @param array $pathOptions
 * @return void
 */
	public function findPath($pathOptions) {
		$valid = false;
		foreach ($pathOptions as $i => $path) {
			if (!is_dir($path)) {
				array_splice($pathOptions, $i, 1);
			}
		}
		$max = count($pathOptions);
		while (!$valid) {
			foreach ($pathOptions as $i => $option) {
				$this->out($i + 1 . '. ' . $option);
			}
			$prompt = __d('cake_console', 'Choose a plugin path from the paths above.');
			$choice = $this->in($prompt, null, 1);
			if (intval($choice) > 0 && intval($choice) <= $max) {
				$valid = true;
			}
		}
		$this->path = $pathOptions[$choice - 1];
	}

/**
 * Gets the option parser instance and configures it.
 *
 * @return ConsoleOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		$parser->description(__d('cake_console',
			'Create the directory structure, AppModel and AppController classes for a new plugin. ' .
			'Can create plugins in any of your bootstrapped plugin paths.'
		))->addArgument('name', [
			'help' => __d('cake_console', 'CamelCased name of the plugin to create.')
		]);

		return $parser;
	}

}