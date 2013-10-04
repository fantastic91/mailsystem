<?php

/**
 * @file
 * Contains \Drupal\mailsystem\MailsystemFactory.
 */

namespace Drupal\mailsystem;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Mail\MailFactory;
use Drupal\mailsystem\Plugin\MailsystemPluginManager;

/**
 * Factory for creating mail system objects based on BasePlugin's.
 */
class MailsystemFactory extends MailFactory {

  /**
   * Plugin-Manager.
   *
   * @var \Drupal\mailsystem\Plugin\MailsystemPluginManager
   */
  protected $manager;

  /**
   * Constructs a MailFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The configuration factory.
   */
  public function __construct(ConfigFactory $configFactory, MailsystemPluginManager $manager) {
    $this->mailConfig = $configFactory->get('mailsystem.settings');
    $this->pluginManager = $manager;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception If no class was found.
   */
  public function get($module, $key) {
    $id = $module . '_' . $key;

    // Create a new instance within the PluginManager.
    if (empty($this->instances[$id])) {
      // @todo
      $mailsystem = $this->mailConfig->get('defaults.mailsystem');
      $instance = $this->pluginManager->createInstance($mailsystem);
      if ($instance instanceof \Drupal\Core\Mail\MailInterface) {
        $this->instances[$id] = $instance;
      }
    }
    return $this->instances[$id];
  }

}
