<?php
/**
* @file
* Contains \Drupal\mailsystem\Plugin\mailsystem\Dummy.
*/

namespace Drupal\mailsystem\Plugin\mailsystem;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;
use Drupal\mailsystem\FormatterInterface;

/**
 * Provides a 'Dummy' plugin to format emails.
 *
 * @Plugin(
 *   id = "mailsystem_dummyformatter",
 *   label = @Translation("Dummy Mailsystem formatter Plugin")
 * )
 */
class DummyFormatter extends PluginBase implements FormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // TODO: Implement format() method.
    dpm($message, 'dummy-format');
    return $message;
  }
}