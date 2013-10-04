<?php
/**
* @file
* Contains Drupal\mailsystem\Plugin\MailsystemPluginManager.
*/

namespace Drupal\mailsystem\Plugin;

use Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for Mailsystem plugins.
 * @package Drupal\mailsystem\Plugin
 */
class MailsystemPluginManager extends DefaultPluginManager {

  /**
   * Constructor.
   * @param \Traversable $namespaces
   */
  public function __construct(\Traversable $namespaces) {
    parent::__construct('Plugin/mailsystem', $namespaces);
    $this->discovery = new StaticDiscoveryDecorator($this->discovery, array($this, 'registerDefinitions'));
  }

  /**
   * Callback for registering definitions for default Mailsystem classes.
   *
   * @see MailsystemPluginManager::__construct()
   */
  public function registerDefinitions() {
    $this->discovery->setDefinition('php_mail', array(
      'id' => 'php_mail',
      'label' => t('PhpMail'),
      'class' => '\Drupal\Core\Mail\PhpMail',
      'provider' => 'core',
    ));
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception If no class was found.
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // First try to create a BasePlugin based Mailplugin, if this fails,
    // use the default method from \Drupal\Core\Mail\MailFactory::get()
    $definition = $this->getDefinition($plugin_id);
    $reflection = new \ReflectionClass($definition['class']);
    if ($reflection->implementsInterface('Drupal\Core\Mail\MailInterface')) {
      if ($reflection->isSubclassOf('Drupal\Component\Plugin\PluginBase')) {
        return parent::createInstance($plugin_id, $configuration);
      }
      else {
        return $reflection->newInstance();
      }
    }
    throw new \Exception(String::format('Class %class does not implement interface %interface', array('%class' => $plugin_id, '%interface' => 'Drupal\Core\Mail\MailInterface')));
  }
}