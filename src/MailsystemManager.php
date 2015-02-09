<?php

/**
 * @file
 * Contains \Drupal\mailsystem\MailsystemManager.
 */

namespace Drupal\mailsystem;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\String;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailInterface;
use Drupal\Core\Mail\MailManager;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Factory for creating mail system objects based on BasePlugin's.
 */
class MailsystemManager extends MailManager {

  /**
   * Constants used for the configuration.
   */
  const MAILSYSTEM_TYPE_SENDING = 'sender';
  const MAILSYSTEM_TYPE_FORMATTING = 'formatter';
  const MAILSYSTEM_MODULES_CONFIG = 'modules';

  /**
   * Config object for mailsystem configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mailsystemConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, TranslationInterface $string_translation) {
    parent::__construct($namespaces, $cache_backend, $module_handler, $config_factory, $logger_factory, $string_translation);
    $this->mailsystemConfig = $config_factory->get('mailsystem.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    $module = isset($options['module']) ? $options['module'] : 'default';
    $key = isset($options['key']) ? $options['key'] : '';

    return new Adapter(
      $this->getPluginInstance($module, $key, self::MAILSYSTEM_TYPE_FORMATTING),
      $this->getPluginInstance($module, $key, self::MAILSYSTEM_TYPE_SENDING)
    );
  }

  /**
   * Get a Mail-Plugin instance and return it.
   *
   * @param string $module
   *   Module name which is going to send and email.
   * @param string $key
   *   (optional) The ID if the email which is being sent.
   * @param string $type
   *   (optional) A subtype, like 'sending' or 'formatting'.
   *   Use \Drupal\mailsystem\MailsystemManager\MAILSYSTEM_TYPE_SENDING and
   *   \Drupal\mailsystem\MailsystemManager\MAILSYSTEM_TYPE_FORMATTING.
   *
   * @return \Drupal\Core\Mail\MailInterface
   *   A mail plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getPluginInstance($module, $key = '', $type = '') {
    $plugin_id = NULL;

    // List of message ids which can be configured.
    $message_id_list = array(
      self::MAILSYSTEM_MODULES_CONFIG . '.' . $module . '.' . $key . '.' . $type,
      self::MAILSYSTEM_MODULES_CONFIG . '.' . $module . '.none.' . $type,
      self::MAILSYSTEM_MODULES_CONFIG . '.' . $module . '.' . $type,
      'defaults.' . $type,
      'defaults'
    );

    foreach($message_id_list as $message_id) {
      $plugin_id = $this->mailsystemConfig->get($message_id);
      if (!is_null($plugin_id)) {
        break;
      }
    }

    // If there is no instance cached, try to create one.
    if (empty($this->instances[$plugin_id])) {
      $plugin = $this->createInstance($plugin_id);
      if ($plugin instanceof MailInterface) {
        $this->instances[$plugin_id] = $plugin;
      }
      else {
        throw new InvalidPluginDefinitionException($plugin_id,
          String::format('Class %class does not implement interface %interface',
            array('%class' => get_class($plugin), '%interface' => 'Drupal\Core\Mail\MailInterface')
          )
        );
      }
    }
    return $this->instances[$plugin_id];
  }

}
