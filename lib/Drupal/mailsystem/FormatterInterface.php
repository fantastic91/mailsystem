<?php

/**
 * @file
 * Definition of \Drupal\mailsystem\FormatterInterface.
 */

namespace Drupal\mailsystem;

/**
 * Defines an interface for pluggable mail back-ends.
 */
interface FormatterInterface {

  /**
   * Formats a message composed by drupal_mail() prior sending.
   *
   * Allows to preprocess, format, and postprocess a mail message before it is
   * passed to the sending system. By default, all messages may contain HTML and
   * are converted to plain-text by the Drupal\Core\Mail\PhpMail implementation.
   * For example, an alternative implementation could override the default
   * implementation and additionally sanitize the HTML for usage in a
   * MIME-encoded e-mail, but still invoking the Drupal\Core\Mail\PhpMail
   * implementation to generate an alternate plain-text version for sending.
   *
   * @param array $message
   *   A message array, as described in hook_mail_alter().
   *
   * @return array
   *   The formatted $message.
   */
  public function format(array $message);
}
