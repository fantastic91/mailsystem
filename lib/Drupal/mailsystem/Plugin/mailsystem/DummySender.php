<?php
/**
* @file
* Contains \Drupal\mailsystem\Plugin\mailsystem\Dummy.
*/

namespace Drupal\mailsystem\Plugin\mailsystem;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;
use Drupal\mailsystem\SenderInterface;

/**
 * Provides a 'Dummy' plugin to format emails.
 *
 * @Plugin(
 *   id = "mailsystem_dummysender",
 *   label = @Translation("Dummy Mailsystem sender Plugin")
 * )
 */
class DummySender extends PluginBase implements SenderInterface {

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // TODO: Implement format() method.
    dpm($message, 'dummy-send');
    return $message;
  }
}