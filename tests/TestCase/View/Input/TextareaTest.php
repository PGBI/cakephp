<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Input;

use Cake\TestSuite\TestCase;
use Cake\View\Input\Textarea;
use Cake\View\StringTemplate;

/**
 * Textarea input test.
 */
class TextareaTest extends TestCase {

/**
 * setup
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$templates = [
			'textarea' => '<textarea name="{{name}}"{{attrs}}>{{value}}</textarea>',
		];
		$this->templates = new StringTemplate($templates);
	}

/**
 * Test render in a simple case.
 *
 * @return void
 */
	public function testRenderSimple() {
		$input = new Textarea($this->templates);
		$result = $input->render(['name' => 'comment']);
		$expected = [
			'textarea' => ['name' => 'comment'],
			'/textarea',
		];
		$this->assertTags($result, $expected);
	}

/**
 * Test render with a value
 *
 * @return void
 */
	public function testRenderWithValue() {
		$input = new Textarea($this->templates);
		$data = ['name' => 'comment', 'data-foo' => '<val>', 'val' => 'some <html>'];
		$result = $input->render($data);
		$expected = [
			'textarea' => ['name' => 'comment', 'data-foo' => '&lt;val&gt;'],
			'some &lt;html&gt;',
			'/textarea',
		];
		$this->assertTags($result, $expected);

		$data['escape'] = false;
		$result = $input->render($data);
		$expected = [
			'textarea' => ['name' => 'comment', 'data-foo' => '<val>'],
			'some <html>',
			'/textarea',
		];
		$this->assertTags($result, $expected);
	}

}