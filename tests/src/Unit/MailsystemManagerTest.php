<?php
/**
 * @file
 * Contains \Drupal\Tests\mailsystem\Unit\MailsystemManagerTest.
 */

namespace Drupal\Tests\mailsystem\Unit;

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
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The mailsystem manager.
   *
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
          'module1' => array(
            'none' => array(
              MailsystemManager::MAILSYSTEM_TYPE_FORMATTING => 'mailsystem_test',
              MailsystemManager::MAILSYSTEM_TYPE_SENDING => 'mailsystem_test',
            )
          ),
          'module2' => array(
            'mail_key' => array(
              MailsystemManager::MAILSYSTEM_TYPE_FORMATTING => 'mailsystem_test',
              MailsystemManager::MAILSYSTEM_TYPE_SENDING => 'mailsystem_test',
            )
          ),
        ),
      ),
    ));

    $logger_factory = $this->getMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $string_translation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');

    $this->mailManager = new MailsystemManager($this->getMock('\Traversable'), $this->getMock('\Drupal\Core\Cache\CacheBackendInterface'), $this->getMock('\Drupal\Core\Extension\ModuleHandlerInterface'), $this->configFactory, $logger_factory, $string_translation);
  }

  public function testGetInstances_Default() {

  }

}
