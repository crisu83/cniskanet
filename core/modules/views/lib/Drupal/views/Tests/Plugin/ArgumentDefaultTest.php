<?php

/**
 * @file
 * Definition of Drupal\views\Tests\Plugin\ArgumentDefaultTest.
 */

namespace Drupal\views\Tests\Plugin;

use Drupal\views_test_data\Plugin\views\argument_default\ArgumentDefaultTest as ArgumentDefaultTestPlugin;


/**
 * Basic test for pluggable argument default.
 */
class ArgumentDefaultTest extends PluginTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('views_ui');

  /**
   * A random string used in the default views.
   *
   * @var string
   */
  protected $random;

  public static function getInfo() {
    return array(
      'name' => 'Argument default',
      'description' => 'Tests pluggable argument_default for views.',
      'group' => 'Views Plugins'
    );
  }

  protected function setUp() {
    $this->random = $this->randomString();

    parent::setUp();

    $this->enableViewsTestModule();
  }

  /**
   * Tests the argument default test plugin.
   *
   * @see Drupal\views_test_data\Plugin\views\argument_default\ArgumentDefaultTest
   */
  public function testArgumentDefaultPlugin() {
    $view = views_get_view('test_view');

    // Add a new argument and set the test plugin for the argument_default.
    $options = array(
      'default_argument_type' => 'argument_default_test',
      'default_argument_options' => array(
        'value' => 'John'
      ),
      'default_action' => 'default'
    );
    $id = $view->addItem('default', 'argument', 'views_test_data', 'name', $options);
    $view->initHandlers();
    $plugin = $view->argument[$id]->get_plugin('argument_default');
    $this->assertTrue($plugin instanceof ArgumentDefaultTestPlugin, 'The correct argument default plugin is used.');

    // Check that the value of the default argument is as expected.
    $this->assertEqual($view->argument[$id]->get_default_argument(), 'John', 'The correct argument default value is returned.');
    // Don't pass in a value for the default argument and make sure the query
    // just returns John.
    $this->executeView($view);
    $this->assertEqual($view->argument[$id]->get_value(), 'John', 'The correct argument value is used.');
    $expected_result = array(array('name' => 'John'));
    $this->assertIdenticalResultset($view, $expected_result, array('views_test_data_name' => 'name'));

    // Pass in value as argument to be sure that not the default value is used.
    $view->destroy();
    $this->executeView($view, array('George'));
    $this->assertEqual($view->argument[$id]->get_value(), 'George', 'The correct argument value is used.');
    $expected_result = array(array('name' => 'George'));
    $this->assertIdenticalResultset($view, $expected_result, array('views_test_data_name' => 'name'));
  }


  /**
   * Tests the use of a default argument plugin that provides no options.
   */
  function testArgumentDefaultNoOptions() {
    $admin_user = $this->drupalCreateUser(array('administer views', 'administer site configuration'));
    $this->drupalLogin($admin_user);

    // The current_user plugin has no options form, and should pass validation.
    $argument_type = 'current_user';
    $edit = array(
      'options[default_argument_type]' => $argument_type,
    );
    $this->drupalPost('admin/structure/views/nojs/config-item/test_argument_default_current_user/default/argument/uid', $edit, t('Apply'));

    // Note, the undefined index error has two spaces after it.
    $error = array(
      '%type' => 'Notice',
      '!message' => 'Undefined index:  ' . $argument_type,
      '%function' => 'views_handler_argument->validateOptionsForm()',
    );
    $message = t('%type: !message in %function', $error);
    $this->assertNoRaw($message, t('Did not find error message: !message.', array('!message' => $message)));
  }

  /**
   * Tests fixed default argument.
   */
  function testArgumentDefaultFixed() {
    $view = $this->getView();
    $view->preExecute();
    $view->initHandlers();

    $this->assertEqual($view->argument['null']->get_default_argument(), $this->random, 'Fixed argument should be used by default.');

    // Make sure that a normal argument provided is used
    $view = $this->getView();

    $random_string = $this->randomString();
    $view->executeDisplay('default', array($random_string));

    $this->assertEqual($view->args[0], $random_string, 'Provided argument should be used.');
  }

  /**
   * @todo Test php default argument.
   */
  //function testArgumentDefaultPhp() {}

  /**
   * @todo Test node default argument.
   */
  //function testArgumentDefaultNode() {}

  /**
   * Overrides Drupal\views\Tests\ViewTestBase::getBasicView().
   */
  protected function getBasicView() {
    $view = $this->createViewFromConfig('test_argument_default_fixed');
    $view->displayHandlers['default']->display['display_options']['arguments']['null']['default_argument_options']['argument'] = $this->random;
    return $view;
  }

}
