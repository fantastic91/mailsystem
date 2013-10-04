<?php

/**
 * @file
 * Contains \Drupal\mailsystem\MailsystemServiceProvider.
 */

namespace Drupal\mailsystem;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines the Mailsystem service provider.
 */
class MailsystemServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

 /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides mail-factory class to use own mail plugins.
    $definition = $container->getDefinition('mail.factory');
    $definition->addArgument(new Reference('plugin.manager.mailsystem'));
    $definition->setClass('Drupal\mailsystem\MailsystemFactory');
  }

}

