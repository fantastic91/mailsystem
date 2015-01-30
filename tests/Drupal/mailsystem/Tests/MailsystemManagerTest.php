<?php
/**
 * @file
 * Contains \Drupal\mailsystem\Tests\MailsystemManagerTest.
 */

namespace Drupal\mailsystem\Tests;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\mailsystem\AdminForm;
use Drupal\mailsystem\MailsystemManager;
use Drupal\Tests\UnitTestCase;

/**
 * Test the MailsystemManager to return valid plugin instances based on teh configuration.
 *
 * @group mailsystem
 */
class MailsystemManagerTest extends UnitTestCase {
  /**
   * Stores the configuration factory to test with.
   *
   * @var \PHPUnit_Framework_MockObject_MockBuilder
   */
  protected $configFactory;

  /**
   * @var \Drupal\mailsystem\MailsystemManager
   */
  protected $mailManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    // Create a configuration mock.
    $this->configFactory = $this->getConfigFactoryStub(array(
      'mailsystem.settings' => array(
        'defaults' => array(
          MailsystemManager::MAILSYSTEM_TYPE_FORMATTING => 'mailsystem_test',
          MailsystemManager::MAILSYSTEM_TYPE_SENDING => 'mailsystem_test',
        ),
        MailsystemManager::MAILSYSTEM_MODULES_CONFIG => array(
          'module1' => array('none' => array(
            MailsystemManager::MAILSYSTEM_TYPE_FORMATTING => 'mailsystem_test',
            MailsystemManager::MAILSYSTEM_TYPE_SENDING => 'mailsystem_test',
          )),
          'module2' => array('mail_key' => array(
            MailsystemManager::MAILSYSTEM_TYPE_FORMATTING => 'mailsystem_test',
            MailsystemManager::MAILSYSTEM_TYPE_SENDING => 'mailsystem_test',
          )),
        ),
      ),
    ));

    $this->mailManager = new MailsystemManager($this->getMock('\Traversable'), $this->getMock('\Drupal\Core\Cache\CacheBackendInterface'), $this->getMock('\Drupal\Core\Language\LanguageManagerInterface'), $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface'), $this->configFactory);
  }

  public function testGetInstances_Default() {

  }
}
