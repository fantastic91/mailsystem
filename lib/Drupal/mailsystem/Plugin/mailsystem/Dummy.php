<?php
/**
* @file
* Contains \Drupal\mailsystem\Plugin\mailsystem\Dummy.
*/

namespace Drupal\mailsystem\Plugin\mailsystem;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Mail\MailInterface;

/**
 * Provides a 'Dummy' plugin to send emails.
 *
 * @Plugin(
 *   id = "mailsystem_dummy",
 *   label = @Translation("Dummy Mailsystem Plugin")
 * )
 */
class Dummy extends PluginBase implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // TODO: Implement format() method.
    dpm($message, 'format');
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // TODO: Implement mail() method.
    dpm($message, 'mail');
    return TRUE;
  }
}