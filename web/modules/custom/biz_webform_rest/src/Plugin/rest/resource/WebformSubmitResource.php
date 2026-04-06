<?php

namespace Drupal\biz_webform_rest\Plugin\rest\resource;

use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformSubmissionForm;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Psr\Log\LoggerInterface;
use Drupal\biz_business_rules\Controller\BusinessRulesFunctions;

/**
 * Creates a resource for submitting a webform.
 *
 * @RestResource(
 *   id = "biz_webform_rest_submit",
 *   label = @Translation("Webform Submit"),
 *   uri_paths = {
 *     "create" = "/webform_rest/submit"
 *   }
 * )
 */
class WebformSubmitResource extends ResourceBase {

  protected EntityTypeManagerInterface $entityTypeManager;
  protected RequestStack $requestStack;
  protected ConfigFactoryInterface $configFactory;
  protected LanguageManagerInterface $languageManager;
  protected MailManagerInterface $mailManager;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeManagerInterface $entityTypeManager,
    RequestStack $request_stack,
    ConfigFactoryInterface $config_factory,
    LanguageManagerInterface $language_manager,
    MailManagerInterface $mail_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->entityTypeManager = $entityTypeManager;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->mailManager = $mail_manager;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('biz_webform_rest'),
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('language_manager'),
      $container->get('plugin.manager.mail')
    );
  }

  public function post() {

    $request = $this->requestStack->getCurrentRequest();
    $content = $request->getContent();

    if (empty($content)) {
      return new ModifiedResourceResponse([
        'error' => ['message' => $this->t('No data has been submitted.')]
      ], 400);
    }

    $webform_data = json_decode($content, TRUE);

    if (empty($webform_data['webform_id'])) {
      throw new BadRequestHttpException("Missing required webform_id value.");
    }

    $webform_id = $webform_data['webform_id'];

    $webform = Webform::load($webform_id);
    if (!$webform) {
      throw new BadRequestHttpException('Invalid webform_id value.');
    }

    if (!WebformSubmissionForm::isOpen($webform)) {
      throw new AccessDeniedHttpException('This webform is closed.');
    }

    if (isset($webform_data['custom_address']['postal_code'])) {
      $webform_data['custom_address']['postal_code'] =
        strtoupper($webform_data['custom_address']['postal_code']);
    }

    $values = [
      'webform_id' => $webform_id,
      'entity_type' => NULL,
      'entity_id' => NULL,
      'in_draft' => FALSE,
      'uri' => '/webform/' . $webform_id . '/api',
      'data' => $webform_data,
    ];

    unset($values['data']['webform_id']);

    $errors = WebformSubmissionForm::validateFormValues($values);

    if (!empty($errors)) {
      return new ModifiedResourceResponse([
        'message' => 'Submitted Data contains validation errors.',
        'error' => $errors,
      ], 400);
    }

    $webform_submission = WebformSubmissionForm::submitFormValues($values);

    return new ModifiedResourceResponse([
      'sid' => $webform_submission->id(),
    ]);
  }

}
