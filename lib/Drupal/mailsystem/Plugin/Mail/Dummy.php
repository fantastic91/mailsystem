<?php
/**
* @file
* Contains \Drupal\mailsystem\Plugin\mailsystem\Dummy.
*/

namespace Drupal\mailsystem\Plugin\Mail;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Mail\MailInterface;

/**
 * Provides a 'Dummy' plugin to send emails.
 *
 * @Plugin(
 *   id = "mailsystem_dummy",
 *   label = @Translation("Dummy Mail-Plugin"),
 *   description = @Translation("Dummy Plugin to debug the complete email on formatting and sending.")
 * )
 */
class Dummy extends PluginBase implements MailInterface {

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    // TODO: Implement format() method.
    \debug(array(
      'Subject' => $message['subject'],
      'Body' => $message['body'],
      'Headers' => $message['headers'],
    ), 'Dummy: format()');
    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function mail(array $message) {
    // TODO: Implement mail() method.
    \debug(array(
      'Subject' => $message['subject'],
      'Body' => $message['body'],
      'Headers' => $message['headers'],
    ), 'Dummy: mail()');
    return TRUE;
  }
}