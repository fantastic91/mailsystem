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
  protected $pluginManager;

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
    return new Adapter(
      $this->getFormatterPlugin($id),
      $this->getSenderPlugin($id)
    );
  }

  /**
   * Returns an object which can be used to format the mail before sending it.
   *
   * @param string $id
   *   ID from the systems which wants to call the format()-function.
   *
   * @return \Drupal\Core\Mail\MailInterface
   *   The Object to format the mail before sending it.
   */
  protected function getFormatterPlugin($id) {
    $plugin = $this->mailConfig->get('plugins.' . $id . '.formatter');
    if (count($this->pluginManager->getDefinition($plugin))) {
      return $this->pluginManager->createInstance($plugin);
    }
    return $this->pluginManager->createInstance($this->mailConfig->get('defaults.formatter'));
  }

  /**
   * Returns an object which can be used to send the mail through.
   *
   * @param string $id
   *   ID from the systems which wants to call the mail()-function.
   *
   * @return \Drupal\Core\Mail\MailInterface
   *   The Object to send the mail through.
   */
  protected function getSenderPlugin($id) {
    $plugin = $this->mailConfig->get('plugins.' . $id . '.sender');
    if (count($this->pluginManager->getDefinition($plugin))) {
      return $this->pluginManager->createInstance($plugin);
    }
    return $this->pluginManager->createInstance($this->mailConfig->get('defaults.sender'));
  }
}
